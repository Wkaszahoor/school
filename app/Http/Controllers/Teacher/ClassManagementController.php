<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\{SchoolClass, TeacherAssignment, Student};
use Inertia\Inertia;

class ClassManagementController extends Controller
{
    public function index()
    {
        $teacher = auth()->user()->teacherProfile;
        $userId = $teacher?->user_id;

        // Get the class this teacher is a class teacher for
        $classTeacherClass = SchoolClass::where('class_teacher_id', $userId)->first();

        // Get all subject teachers assigned to this class
        $subjectTeachers = [];
        if ($classTeacherClass) {
            $subjectTeachers = TeacherAssignment::where('class_id', $classTeacherClass->id)
                ->where('assignment_type', 'subject_teacher')
                ->with(['teacher' => function ($q) {
                    $q->select('id', 'name');
                }, 'subject' => function ($q) {
                    $q->select('id', 'subject_name');
                }])
                ->get()
                ->map(function ($assignment) {
                    return [
                        'id' => $assignment->teacher->id,
                        'name' => $assignment->teacher->name,
                        'subject' => $assignment->subject->subject_name,
                        'assignment_id' => $assignment->id,
                    ];
                })
                ->toArray();
        }

        return Inertia::render('Teacher/ClassManagement', compact('classTeacherClass', 'subjectTeachers'));
    }
}
