<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AcademicYearsController extends Controller
{
    public function index(Request $request)
    {
        $academicYears = AcademicYear::query()
            ->when($request->search, fn($q) =>
                $q->where('year', 'like', "%{$request->search}%")
                   ->orWhere('description', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Principal/AcademicYears/Index', compact('academicYears'));
    }

    public function create()
    {
        return Inertia::render('Principal/AcademicYears/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'year' => 'required|string|max:20|unique:academic_years,year',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        AcademicYear::create($data);

        return redirect()->route('principal.academic-years.index')
            ->with('success', 'Academic year created successfully');
    }

    public function show(AcademicYear $academicYear)
    {
        // Load related data
        $academicYear->load(['schoolClasses']);

        $classesCount = $academicYear->schoolClasses()->count();
        $studentsCount = $academicYear->students()->count();

        return Inertia::render('Principal/AcademicYears/Show', compact('academicYear', 'classesCount', 'studentsCount'));
    }

    public function edit(AcademicYear $academicYear)
    {
        return Inertia::render('Principal/AcademicYears/Edit', compact('academicYear'));
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $data = $request->validate([
            'year' => 'required|string|max:20|unique:academic_years,year,' . $academicYear->id,
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        $academicYear->update($data);

        return back()->with('success', 'Academic year updated successfully');
    }

    public function destroy(AcademicYear $academicYear)
    {
        $academicYear->delete();

        return back()->with('success', 'Academic year has been archived');
    }

    public function restore(AcademicYear $academicYear)
    {
        $academicYear->restore();

        return back()->with('success', 'Academic year has been restored');
    }
}
