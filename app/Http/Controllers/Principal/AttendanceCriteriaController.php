<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{AttendanceCriteria, SchoolClass, Subject};
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceCriteriaController extends Controller
{
    public function index(Request $request)
    {
        $academicYear = $request->get('academic_year', config('school.current_academic_year', '2025-26'));

        $criteria = AttendanceCriteria::with(['schoolClass', 'subject', 'createdBy'])
            ->where('academic_year', $academicYear)
            ->orderBy('class_id')
            ->paginate(20);

        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);
        $subjects = Subject::where('is_active', true)->get(['id', 'subject_name']);

        return Inertia::render('Principal/AttendanceCriteria/Index', [
            'criteria' => $criteria,
            'classes' => $classes,
            'subjects' => $subjects,
            'academicYear' => $academicYear,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'criteria_type' => 'required|in:class,subject',
            'min_attendance_percent' => 'required|numeric|min:0|max:100',
            'max_allowed_absences' => 'nullable|numeric|min:0',
            'academic_year' => 'required|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        AttendanceCriteria::create($validated);

        return redirect()->route('principal.attendance-criteria.index')
            ->with('success', 'Attendance criteria created successfully');
    }

    public function update(Request $request, AttendanceCriteria $criterion)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'criteria_type' => 'required|in:class,subject',
            'min_attendance_percent' => 'required|numeric|min:0|max:100',
            'max_allowed_absences' => 'nullable|numeric|min:0',
            'academic_year' => 'required|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $criterion->update($validated);

        return redirect()->route('principal.attendance-criteria.index')
            ->with('success', 'Attendance criteria updated successfully');
    }

    public function destroy(AttendanceCriteria $criterion)
    {
        $criterion->delete();

        return redirect()->route('principal.attendance-criteria.index')
            ->with('success', 'Attendance criteria deleted successfully');
    }
}
