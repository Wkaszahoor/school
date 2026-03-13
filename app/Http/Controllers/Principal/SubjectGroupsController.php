<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{SubjectGroup, Subject, Student, AuditLog, SubjectGroupTeacher, User};
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubjectGroupsController extends Controller
{
    public function index()
    {
        // Load groups with subjects and teachers
        $groups = SubjectGroup::with('subjects', 'teachers.teacher')
            ->get()
            ->map(fn($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'group_name' => $g->group_name,
                'group_slug' => $g->group_slug,
                'stream' => $g->stream,
                'description' => $g->description,
                'is_active' => $g->is_active,
                'education_level' => $g->education_level,
                'subject_count' => $g->subjects->count(),
                'student_count' => $g->students()->count(),  // Count only, don't load
                'subjects' => $g->subjects->map(fn($s) => [
                    'id' => $s->id,
                    'subject_name' => $s->subject_name,
                    'subject_type' => $s->pivot->subject_type,
                ]),
                'teachers' => $g->teachers->map(fn($t) => [
                    'id' => $t->id,
                    'user_id' => $t->user_id,
                    'teacher_name' => $t->teacher->name,
                    'teacher_email' => $t->teacher->email,
                    'role' => $t->role,
                    'subject_id' => $t->subject_id,
                    'subject_name' => $t->subject?->subject_name,
                ]),
                'students' => [],  // Load on demand
            ]);

        $allSubjects = Subject::where('is_active', 1)->get(['id', 'subject_name', 'subject_code']);
        $allStudents = Student::where('is_active', true)
            ->with('class')
            ->whereIn('class_id', [46, 47, 48, 49])
            ->get(['id', 'admission_no', 'full_name', 'class_id', 'stream'])
            ->map(fn($s) => [
                'id' => $s->id,
                'admission_no' => $s->admission_no,
                'full_name' => $s->full_name,
                'class' => $s->class?->class,
                'section' => $s->class?->section,
                'class_id' => $s->class_id,
                'stream' => $s->stream,
            ]);

        // Get all teachers (users with role teacher)
        $allTeachers = User::where('role', 'teacher')
            ->where('is_active', true)
            ->get(['id', 'name', 'email'])
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'email' => $t->email,
            ]);

        return Inertia::render('Principal/SubjectGroups/Index', compact('groups', 'allSubjects', 'allStudents', 'allTeachers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_name' => 'required|string|max:120|unique:subject_groups,group_name',
            'stream' => 'nullable|string|max:60',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $group = SubjectGroup::create([
            'group_name' => $validated['group_name'],
            'stream' => $validated['stream'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', "Subject Group '{$group->name}' created successfully.");
    }

    public function update(Request $request, SubjectGroup $group)
    {
        $validated = $request->validate([
            'group_name' => 'required|string|max:120|unique:subject_groups,group_name,' . $group->id,
            'stream' => 'nullable|string|max:60',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $group->update($validated);

        return back()->with('success', "Subject Group '{$group->name}' updated successfully.");
    }

    public function show(SubjectGroup $group)
    {
        // Load group with all related data including students
        $groupData = [
            'id' => $group->id,
            'name' => $group->name,
            'group_name' => $group->group_name,
            'stream' => $group->stream,
            'description' => $group->description,
            'is_active' => $group->is_active,
            'education_level' => $group->education_level,
            'subjects' => $group->subjects->map(fn($s) => [
                'id' => $s->id,
                'subject_name' => $s->subject_name,
                'subject_type' => $s->pivot->subject_type,
            ]),
            'students' => $group->students()->with('class')->get()->map(fn($s) => [
                'id' => $s->id,
                'admission_no' => $s->admission_no,
                'full_name' => $s->full_name,
                'class' => $s->class?->class,
                'section' => $s->class?->section,
                'class_id' => $s->class_id,
                'stream' => $s->stream,
            ]),
        ];

        return response()->json($groupData);
    }

    public function destroy(SubjectGroup $group)
    {
        if ($group->students()->count() > 0) {
            return back()->with('error', "Cannot delete group '{$group->name}' — it has {$group->students()->count()} student(s) assigned.");
        }

        $name = $group->name;
        $group->delete();

        return back()->with('success', "Subject Group '{$name}' deleted successfully.");
    }

    public function addSubject(Request $request, SubjectGroup $group)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'subject_type' => 'required|in:compulsory,optional,major',
        ]);

        // Check if subject already attached
        if ($group->subjects()->where('subject_id', $validated['subject_id'])->exists()) {
            return back()->with('error', 'Subject is already added to this group.');
        }

        $subject = Subject::find($validated['subject_id']);
        $group->subjects()->attach($validated['subject_id'], ['subject_type' => $validated['subject_type']]);

        return back()->with('success', "Subject '{$subject->subject_name}' added to '{$group->name}'.");
    }

    public function removeSubject(Request $request, SubjectGroup $group, Subject $subject)
    {
        $group->subjects()->detach($subject->id);

        return back()->with('success', "Subject '{$subject->subject_name}' removed from '{$group->name}'.");
    }

    public function updateSubjectType(Request $request, SubjectGroup $group, Subject $subject)
    {
        $validated = $request->validate([
            'subject_type' => 'required|in:compulsory,optional,major',
        ]);

        $group->subjects()->updateExistingPivot($subject->id, ['subject_type' => $validated['subject_type']]);

        return back()->with('success', "Subject '{$subject->subject_name}' updated to '{$validated['subject_type']}'.");
    }

    public function addStudent(Request $request, SubjectGroup $group)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $student = Student::find($validated['student_id']);

        // Check if student already in this group
        if ($student->subject_group_id === $group->id) {
            return back()->with('error', "'{$student->full_name}' is already in this group.");
        }

        $oldGroupId = $student->subject_group_id;
        $oldStream = $student->stream;

        // Auto-sync student's stream with the group's stream
        $student->subject_group_id = $group->id;
        $student->stream = $group->stream;
        $student->save();

        AuditLog::log('assign_group', 'Student', $student->id,
            ['subject_group_id' => $oldGroupId, 'stream' => $oldStream],
            ['subject_group_id' => $group->id, 'stream' => $group->stream]);

        return back()->with('success', "'{$student->full_name}' added to '{$group->name}'.");
    }

    public function removeStudent(Request $request, SubjectGroup $group, Student $student)
    {
        if ($student->subject_group_id !== $group->id) {
            return back()->with('error', "'{$student->full_name}' is not in this group.");
        }

        $oldGroupId = $student->subject_group_id;
        $oldStream = $student->stream;

        // Clear both subject group and stream when removing from group
        $student->subject_group_id = null;
        $student->stream = null;
        $student->save();

        AuditLog::log('remove_group', 'Student', $student->id,
            ['subject_group_id' => $oldGroupId, 'stream' => $oldStream],
            ['subject_group_id' => null, 'stream' => null]);

        return back()->with('success', "'{$student->full_name}' removed from '{$group->name}'.");
    }

    public function addTeacher(Request $request, SubjectGroup $group)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:class_teacher,subject_teacher',
            'subject_id' => 'nullable|exists:subjects,id',
        ]);

        // Check if teacher already assigned to this group
        $existing = SubjectGroupTeacher::where('subject_group_id', $group->id)
            ->where('user_id', $validated['user_id'])
            ->where('subject_id', $validated['subject_id'])
            ->first();

        if ($existing) {
            return back()->with('error', 'Teacher is already assigned to this group.');
        }

        SubjectGroupTeacher::create([
            'subject_group_id' => $group->id,
            'user_id' => $validated['user_id'],
            'role' => $validated['role'],
            'subject_id' => $validated['subject_id'],
        ]);

        $teacher = User::find($validated['user_id']);
        return back()->with('success', "Teacher '{$teacher->name}' assigned to '{$group->name}'.");
    }

    public function removeTeacher(Request $request, SubjectGroup $group, SubjectGroupTeacher $teacher)
    {
        if ($teacher->subject_group_id !== $group->id) {
            return back()->with('error', 'Teacher is not assigned to this group.');
        }

        $teacherName = $teacher->teacher->name;
        $teacher->delete();

        return back()->with('success', "Teacher '{$teacherName}' removed from '{$group->name}'.");
    }

    public function updateStudentStream(Request $request, Student $student)
    {
        $validated = $request->validate([
            'stream' => 'nullable|in:ICS,Pre-Medical',
        ]);

        $student->update(['stream' => $validated['stream']]);

        return response()->json([
            'success' => true,
            'message' => "Student stream updated to " . ($validated['stream'] ?? 'None'),
            'stream' => $student->stream,
        ]);
    }
}
