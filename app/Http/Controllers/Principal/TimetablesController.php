<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use App\Models\TimeSlot;
use App\Models\AuditLog;
use App\Services\TimetableGeneratorService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TimetablesController extends Controller
{
    public function index(Request $request)
    {
        $timetables = Timetable::when($request->search, fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->academic_year, fn($q) => $q->where('academic_year', $request->academic_year))
            ->when($request->term, fn($q) => $q->where('term', $request->term))
            ->with('creator')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Principal/Timetables/Index', [
            'timetables' => $timetables,
            'statuses' => ['draft', 'generating', 'generated', 'published', 'archived'],
            'terms' => ['spring', 'summer', 'autumn'],
            'academicYears' => Timetable::distinct()->pluck('academic_year')->sort()->reverse(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Principal/Timetables/Create', [
            'timeSlots' => TimeSlot::active()->orderBy('period_number')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'academic_year' => 'required|string|max:20',
            'term' => 'required|in:spring,summer,autumn',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'total_days' => 'required|integer|min:5|max:6',
            'notes' => 'nullable|string',
        ]);

        $data['created_by'] = auth()->id();
        $data['status'] = 'draft';

        $timetable = Timetable::create($data);

        AuditLog::log('create', 'Timetable', $timetable->id, [], $data);

        return redirect()->route('principal.timetables.show', $timetable->id)
            ->with('success', 'Timetable created successfully');
    }

    public function show(Timetable $timetable)
    {
        $timetable->load('creator', 'entries', 'conflicts');

        return Inertia::render('Principal/Timetables/Show', [
            'timetable' => $timetable,
            'entries' => $timetable->entries()->with('schoolClass', 'subject', 'teacher', 'room', 'timeSlot')->get(),
            'conflicts' => $timetable->conflicts,
            'timeSlots' => TimeSlot::active()->orderBy('period_number')->get(),
            'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        ]);
    }

    public function generate(Request $request, Timetable $timetable, TimetableGeneratorService $generatorService)
    {
        $result = $generatorService->generate($timetable->id);

        if ($result['success']) {
            AuditLog::log('generate', 'Timetable', $timetable->id, ['status' => 'draft'], ['status' => 'generated', 'entries' => $result['entries'], 'conflicts' => $result['conflicts']]);
            return back()->with('success', $result['message'] . ' (' . $result['entries'] . ' entries, ' . $result['conflicts'] . ' conflicts)');
        } else {
            AuditLog::log('generate_error', 'Timetable', $timetable->id, [], ['error' => $result['error']]);
            return back()->withErrors(['error' => $result['error'] ?? 'Generation failed']);
        }
    }

    public function publish(Request $request, Timetable $timetable)
    {
        if ($timetable->conflict_count > 0) {
            return back()->withErrors(['error' => 'Cannot publish: There are unresolved conflicts']);
        }
        $timetable->update(['status' => 'published', 'published_at' => now()]);
        AuditLog::log('publish', 'Timetable', $timetable->id, [], ['status' => 'published']);
        return back()->with('success', 'Timetable published successfully');
    }

    public function update(Request $request, Timetable $timetable)
    {
        if ($timetable->status !== 'draft') {
            return back()->withErrors(['error' => 'Cannot edit non-draft timetables']);
        }
        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'notes' => 'nullable|string',
        ]);
        $oldValues = $timetable->getAttributes();
        $timetable->update($data);
        AuditLog::log('update', 'Timetable', $timetable->id, $oldValues, $data);
        return back()->with('success', 'Timetable updated');
    }

    public function destroy(Timetable $timetable)
    {
        AuditLog::log('delete', 'Timetable', $timetable->id, $timetable->getAttributes(), []);
        $timetable->delete();
        return redirect()->route('principal.timetables.index')->with('success', 'Timetable deleted');
    }

    public function archive(Request $request, Timetable $timetable)
    {
        $timetable->update(['status' => 'archived']);
        AuditLog::log('archive', 'Timetable', $timetable->id, [], ['status' => 'archived']);
        return back()->with('success', 'Timetable archived');
    }
}
