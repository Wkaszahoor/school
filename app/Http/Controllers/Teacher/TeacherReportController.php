<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\{TeacherReport, User, SchoolClass, TeacherAssignment};
use Illuminate\Http\Request;

class TeacherReportController extends Controller
{
    public function store(Request $request)
    {
        $classTeacher = auth()->user();
        $classTeacherId = $classTeacher->id;

        $request->validate([
            'subject_teacher_id' => 'required|integer|exists:users,id',
            'class_id' => 'required|integer|exists:classes,id',
            'report_type' => 'required|in:general,performance,conduct,attendance',
            'notes' => 'required|string|min:10|max:1000',
        ]);

        // Verify that the authenticated user is a class teacher for this class
        $class = SchoolClass::find($request->class_id);
        if (!$class || $class->class_teacher_id !== $classTeacherId) {
            return back()->with('error', 'You are not the class teacher for this class.');
        }

        // Verify that the subject teacher is assigned to this class
        $subjectTeacher = User::find($request->subject_teacher_id);
        if (!$subjectTeacher || $subjectTeacher->role !== 'teacher') {
            return back()->with('error', 'Invalid subject teacher.');
        }

        $isAssigned = TeacherAssignment::where('teacher_id', $request->subject_teacher_id)
            ->where('class_id', $request->class_id)
            ->where('assignment_type', 'subject_teacher')
            ->exists();

        if (!$isAssigned) {
            return back()->with('error', 'This teacher is not assigned to this class.');
        }

        // Create the report
        TeacherReport::create([
            'subject_teacher_id' => $request->subject_teacher_id,
            'class_teacher_id' => $classTeacherId,
            'class_id' => $request->class_id,
            'report_type' => $request->report_type,
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'Report submitted successfully.');
    }

    public function destroy(TeacherReport $report)
    {
        $classTeacher = auth()->user();

        // Verify that only the class teacher who submitted the report can delete it
        if ($report->class_teacher_id !== $classTeacher->id) {
            return back()->with('error', 'You can only delete your own reports.');
        }

        $report->delete();

        return back()->with('success', 'Report deleted successfully.');
    }
}
