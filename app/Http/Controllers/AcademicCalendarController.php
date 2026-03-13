<?php

namespace App\Http\Controllers;

use App\Models\AcademicCalendar;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AcademicCalendarController extends Controller
{
    public function index(Request $request)
    {
        $query = AcademicCalendar::with('creator')
            ->when($request->search, fn($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->academic_year, fn($q) => $q->where('academic_year', $request->academic_year));

        $academicYears = AcademicCalendar::distinct()->pluck('academic_year')->sort()->reverse();
        $calendars = $query->orderBy('start_date')->paginate(20)->withQueryString();

        return Inertia::render('AcademicCalendar/Index', [
            'calendars' => $calendars,
            'academicYears' => $academicYears,
            'types' => ['holiday', 'exam', 'term', 'event', 'semester', 'break', 'other'],
        ]);
    }

    public function create()
    {
        return Inertia::render('AcademicCalendar/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => 'required|in:holiday,exam,term,event,semester,break,other',
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'academic_year' => 'required|string',
            'is_all_day' => 'boolean',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $data['created_by'] = auth()->id();
        $calendar = AcademicCalendar::create($data);
        AuditLog::log('create', 'AcademicCalendar', $calendar->id, [], $data);

        return redirect()->route('academic-calendar.index')
            ->with('success', 'Calendar event created successfully');
    }

    public function edit(AcademicCalendar $calendar)
    {
        return Inertia::render('AcademicCalendar/Edit', [
            'calendar' => $calendar->load('creator'),
        ]);
    }

    public function update(Request $request, AcademicCalendar $calendar)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => 'required|in:holiday,exam,term,event,semester,break,other',
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'academic_year' => 'required|string',
            'is_all_day' => 'boolean',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $oldValues = $calendar->getAttributes();
        $calendar->update($data);
        AuditLog::log('update', 'AcademicCalendar', $calendar->id, $oldValues, $data);

        return redirect()->route('academic-calendar.index')
            ->with('success', 'Calendar event updated successfully');
    }

    public function destroy(AcademicCalendar $calendar)
    {
        AuditLog::log('delete', 'AcademicCalendar', $calendar->id, $calendar->getAttributes(), []);
        $calendar->delete();

        return back()->with('success', 'Calendar event deleted successfully');
    }

    public function calendar(Request $request)
    {
        $year = $request->year ?? now()->year;
        $month = $request->month ?? now()->month;
        $academicYear = $request->academic_year;

        $calendars = AcademicCalendar::when($academicYear, fn($q) => $q->where('academic_year', $academicYear))
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->with('creator')
            ->get();

        $academicYears = AcademicCalendar::distinct()->pluck('academic_year')->sort()->reverse();

        return Inertia::render('AcademicCalendar/Calendar', [
            'calendars' => $calendars,
            'academicYears' => $academicYears,
            'year' => $year,
            'month' => $month,
            'academicYear' => $academicYear,
        ]);
    }
}
