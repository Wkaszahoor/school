<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\AcademicCalendar;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AcademicCalendarsController extends Controller
{
    public function index(Request $request)
    {
        $query = AcademicCalendar::query();

        // Search by title or description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by academic year
        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        $events = $query->orderBy('start_date', 'asc')
                       ->paginate(15);

        $academicYears = AcademicCalendar::distinct()
                                         ->orderBy('academic_year', 'desc')
                                         ->pluck('academic_year')
                                         ->toArray();

        return Inertia::render('Principal/AcademicCalendars/Index', [
            'events' => $events,
            'academicYears' => $academicYears,
        ]);
    }

    public function create()
    {
        $academicYears = AcademicCalendar::distinct()
                                         ->orderBy('academic_year', 'desc')
                                         ->pluck('academic_year')
                                         ->toArray();

        if (empty($academicYears)) {
            $currentYear = now()->year;
            $academicYears = [(string)$currentYear . '-' . ($currentYear + 1)];
        }

        return Inertia::render('Principal/AcademicCalendars/Create', [
            'academicYears' => $academicYears,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:holiday,exam,term,event,semester,break,other',
            'color' => 'required|regex:/^#[0-9A-F]{6}$/i',
            'academic_year' => 'required|string',
            'is_all_day' => 'boolean',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        AcademicCalendar::create($validated);

        return redirect()->route('principal.academic-calendars.index')
                       ->with('success', 'Academic event created successfully');
    }

    public function edit(AcademicCalendar $academicCalendar)
    {
        $academicYears = AcademicCalendar::distinct()
                                         ->orderBy('academic_year', 'desc')
                                         ->pluck('academic_year')
                                         ->toArray();

        return Inertia::render('Principal/AcademicCalendars/Edit', [
            'event' => $academicCalendar,
            'academicYears' => $academicYears,
        ]);
    }

    public function update(Request $request, AcademicCalendar $academicCalendar)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:holiday,exam,term,event,semester,break,other',
            'color' => 'required|regex:/^#[0-9A-F]{6}$/i',
            'academic_year' => 'required|string',
            'is_all_day' => 'boolean',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $academicCalendar->update($validated);

        return redirect()->route('principal.academic-calendars.index')
                       ->with('success', 'Academic event updated successfully');
    }

    public function destroy(AcademicCalendar $academicCalendar)
    {
        $academicCalendar->delete();

        return redirect()->route('principal.academic-calendars.index')
                       ->with('success', 'Academic event deleted successfully');
    }

    public function calendar(Request $request)
    {
        $year = $request->input('year', now()->year);
        $academicYear = $request->input('academic_year', null);

        $query = AcademicCalendar::query();

        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        }

        $events = $query->get();

        $academicYears = AcademicCalendar::distinct()
                                         ->orderBy('academic_year', 'desc')
                                         ->pluck('academic_year')
                                         ->toArray();

        return Inertia::render('Principal/AcademicCalendars/Calendar', [
            'events' => $events,
            'year' => $year,
            'academicYear' => $academicYear,
            'academicYears' => $academicYears,
        ]);
    }
}
