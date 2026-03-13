<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\{AttendanceCriteria, TeacherAssignment, Subject};
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceCriteriaController extends Controller
{
    public function index(Request $request)
    {
        $teacher = auth()->user()->teacherProfile;
        $academicYear = $request->academic_year ?? date('Y');

        // Get all subject assignments for this teacher
        $assignments = TeacherAssignment::where('teacher_id', $teacher?->user_id)
            ->with(['class', 'subject'])
            ->get();

        if ($assignments->isEmpty()) {
            return Inertia::render('Teacher/AttendanceCriteria/Index', [
                'groupedAssignments' => [],
                'criteria' => [],
                'academicYear' => $academicYear,
                'message' => 'No subjects assigned to you.'
            ]);
        }

        // Group assignments by class and list their subjects
        $groupedAssignments = $assignments->groupBy(fn($a) => $a->class_id)->map(fn($group) => [
            'classId' => $group->first()->class_id,
            'className' => $group->first()->class->class,
            'classSection' => $group->first()->class->section,
            'subjects' => $group->map(fn($a) => [
                'id' => $a->subject_id,
                'name' => $a->subject->subject_name,
            ])->unique('id')->values()->toArray()
        ])->values()->toArray();

        // Get existing criteria for all teacher's assignments
        $classIds = $assignments->pluck('class_id')->unique();
        $subjectIds = $assignments->pluck('subject_id')->unique();

        $criteria = AttendanceCriteria::where('academic_year', $academicYear)
            ->where('criteria_type', 'subject')
            ->whereIn('class_id', $classIds)
            ->whereIn('subject_id', $subjectIds)
            ->with(['schoolClass', 'subject'])
            ->get()
            ->keyBy(fn($c) => $c->class_id . '_' . $c->subject_id);

        return Inertia::render('Teacher/AttendanceCriteria/Index', [
            'groupedAssignments' => $groupedAssignments,
            'criteria' => $criteria,
            'academicYear' => $academicYear,
        ]);
    }

    public function store(Request $request)
    {
        $teacher = auth()->user()->teacherProfile;
        $academicYear = $request->academic_year ?? date('Y');

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'min_attendance_percent' => 'required|integer|min:0|max:100',
            'max_allowed_absences' => 'nullable|integer|min:0',
        ]);

        // Verify teacher is assigned to this class-subject combination
        $assignment = TeacherAssignment::where('teacher_id', $teacher?->user_id)
            ->where('class_id', $validated['class_id'])
            ->where('subject_id', $validated['subject_id'])
            ->first();

        if (!$assignment) {
            return back()->with('error', 'You are not assigned to this subject in this class.');
        }

        try {
            AttendanceCriteria::updateOrCreate(
                [
                    'class_id' => $validated['class_id'],
                    'subject_id' => $validated['subject_id'],
                    'academic_year' => $academicYear,
                ],
                [
                    'criteria_type' => 'subject',
                    'min_attendance_percent' => $validated['min_attendance_percent'],
                    'max_allowed_absences' => $validated['max_allowed_absences'],
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]
            );

            return back()->with('success', 'Attendance criteria for ' . $assignment->subject->subject_name . ' saved successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error saving criteria: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        $teacher = auth()->user()->teacherProfile;
        $academicYear = $request->academic_year ?? date('Y');

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        // Verify teacher is assigned to this subject
        $assignment = TeacherAssignment::where('teacher_id', $teacher?->user_id)
            ->where('class_id', $validated['class_id'])
            ->where('subject_id', $validated['subject_id'])
            ->first();

        if (!$assignment) {
            return back()->with('error', 'You are not assigned to this subject in this class.');
        }

        AttendanceCriteria::where('class_id', $validated['class_id'])
            ->where('subject_id', $validated['subject_id'])
            ->where('academic_year', $academicYear)
            ->delete();

        return back()->with('success', 'Attendance criteria deleted successfully.');
    }
}
