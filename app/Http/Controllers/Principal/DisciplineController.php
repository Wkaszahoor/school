<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{DisciplineRecord, Student, SchoolClass, AuditLog};
use Illuminate\Http\Request;
use Inertia\Inertia;

class DisciplineController extends Controller
{
    public function index(Request $request)
    {
        $records = DisciplineRecord::with(['student.class', 'recordedBy', 'actions'])
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->class_id, fn($q) => $q->whereHas('student', fn($s) => $s->where('class_id', $request->class_id)))
            ->latest('incident_date')
            ->paginate(20)
            ->withQueryString();

        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);

        return Inertia::render('Principal/Discipline/Index', compact('records', 'classes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'    => 'required|exists:students,id',
            'type'          => 'required|in:warning,achievement,suspension,note',
            'description'   => 'required|string',
            'action_taken'  => 'nullable|string',
            'incident_date' => 'required|date',
        ]);

        $data['recorded_by'] = auth()->id();
        $record = DisciplineRecord::create($data);
        AuditLog::log('create', 'DisciplineRecord', $record->id, null, $data);

        return back()->with('success', 'Discipline record added.');
    }

    public function update(Request $request, DisciplineRecord $discipline)
    {
        $data = $request->validate([
            'type'          => 'required|in:warning,achievement,suspension,note',
            'description'   => 'required|string',
            'action_taken'  => 'nullable|string',
            'resolved'      => 'boolean',
        ]);

        $oldValues = $discipline->getAttributes();
        $discipline->update($data);
        AuditLog::log('update', 'DisciplineRecord', $discipline->id, $oldValues, $data);
        return back()->with('success', 'Record updated.');
    }
}
