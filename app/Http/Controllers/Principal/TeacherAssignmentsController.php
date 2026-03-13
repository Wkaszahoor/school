<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{TeacherAssignment, TeacherProfile, SchoolClass, Subject, SubjectGroup, AuditLog};
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeacherAssignmentsController extends Controller
{
    public function index(Request $request)
    {
        // If filtering by class_teacher, get from SchoolClass table
        if ($request->assignment_type === 'class_teacher') {
            $query = SchoolClass::with(['classTeacher:id,name,email'])
                ->whereNotNull('class_teacher_id')
                ->when($request->class_id, fn($q) => $q->where('id', $request->class_id))
                ->when($request->teacher_id, fn($q) => $q->where('class_teacher_id', $request->teacher_id));

            $classTeachers = $query->latest()->paginate(25)->withQueryString();

            // Convert to TeacherAssignment format for consistent display, preserving pagination
            $assignments = $classTeachers->through(function($class) {
                return (object)[
                    'id' => 'ct-' . $class->id,
                    'teacher_id' => $class->class_teacher_id,
                    'class_id' => $class->id,
                    'subject_id' => null,
                    'assignment_type' => 'class_teacher',
                    'academic_year' => date('Y'),
                    'teacher' => $class->classTeacher,
                    'class' => $class,
                    'subject' => (object)['id' => null, 'subject_name' => '(Class Teacher)'],
                    'group' => null,
                ];
            });
        } else {
            // Get subject teacher assignments from TeacherAssignment table
            $assignments = TeacherAssignment::with([
                'teacher:id,name,email',
                'teacherProfile.user:id,name,email',
                'class:id,class,section',
                'subject:id,subject_name',
                'group:id,group_name,stream'
            ])
                ->when($request->teacher_id, fn($q) => $q->where('teacher_id', $request->teacher_id))
                ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
                ->when($request->assignment_type, fn($q) => $q->where('assignment_type', $request->assignment_type))
                ->latest()
                ->paginate(25)
                ->withQueryString();
        }

        $teachers = TeacherProfile::with('user')
            ->where('is_active', true)
            ->get(['id', 'user_id'])
            ->load('user:id,name');

        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);
        $subjects = Subject::where('is_active', true)->get(['id', 'subject_name', 'subject_code']);
        $groups = SubjectGroup::where('is_active', true)->get();

        return Inertia::render('Principal/TeacherAssignments/Index', compact(
            'assignments', 'teachers', 'classes', 'subjects', 'groups'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_id'      => 'required|exists:users,id',
            'class_id'        => 'required|exists:classes,id',
            'subject_id'      => 'required|exists:subjects,id',
            'academic_year'   => 'required|string',
            'assignment_type' => 'required|in:class_teacher,subject_teacher',
            'group_id'        => 'nullable|exists:subject_groups,id',
        ]);

        // Check if assignment already exists
        $exists = TeacherAssignment::where('teacher_id', $data['teacher_id'])
            ->where('class_id', $data['class_id'])
            ->where('subject_id', $data['subject_id'])
            ->where('academic_year', $data['academic_year'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => 'This assignment already exists.']);
        }

        $assignment = TeacherAssignment::create($data);
        AuditLog::log('assign_teacher', 'TeacherAssignment', $assignment->id, null, $data);

        return back()->with('success', 'Teacher assigned to class and subject successfully.');
    }

    public function update(Request $request, TeacherAssignment $assignment)
    {
        $data = $request->validate([
            'teacher_id'      => 'required|exists:users,id',
            'class_id'        => 'required|exists:classes,id',
            'subject_id'      => 'required|exists:subjects,id',
            'academic_year'   => 'required|string',
            'assignment_type' => 'required|in:class_teacher,subject_teacher',
            'group_id'        => 'nullable|exists:subject_groups,id',
        ]);

        // Check if updated assignment conflicts with existing ones (excluding current)
        $exists = TeacherAssignment::where('teacher_id', $data['teacher_id'])
            ->where('class_id', $data['class_id'])
            ->where('subject_id', $data['subject_id'])
            ->where('academic_year', $data['academic_year'])
            ->where('id', '!=', $assignment->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => 'This assignment already exists.']);
        }

        $oldValues = $assignment->getAttributes();
        $assignment->update($data);
        AuditLog::log('update_teacher_assignment', 'TeacherAssignment', $assignment->id, $oldValues, $data);

        return back()->with('success', 'Assignment updated successfully.');
    }

    public function destroy(TeacherAssignment $assignment)
    {
        $oldValues = $assignment->getAttributes();
        $assignment->delete();
        AuditLog::log('unassign_teacher', 'TeacherAssignment', $assignment->id, $oldValues, null);

        return back()->with('success', 'Assignment removed successfully.');
    }
}
