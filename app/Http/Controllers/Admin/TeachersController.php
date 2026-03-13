<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{User, TeacherProfile, SchoolClass, Subject, TeacherReport};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class TeachersController extends Controller
{
    public function index(Request $request)
    {
        $teachers = TeacherProfile::with('user')
            ->when($request->search, fn($q) =>
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Add class teacher information
        $teachers->through(function($teacher) {
            $classTeacherClasses = SchoolClass::where('class_teacher_id', $teacher->user_id)->get(['id', 'class', 'section']);
            $teacher->class_teacher_classes = $classTeacherClasses->map(fn($c) =>
                "{$c->class}" . ($c->section ? "-{$c->section}" : '')
            )->toArray();
            return $teacher;
        });

        return Inertia::render('Admin/Teachers/Index', compact('teachers'));
    }

    public function show(TeacherProfile $teacher)
    {
        $teacher->load(['user', 'assignments.class', 'assignments.subject', 'lessonPlans', 'leaveRequests']);

        // Get class teacher assignments
        $classTeacherClasses = SchoolClass::where('class_teacher_id', $teacher->user_id)->get(['id', 'class', 'section']);
        $teacher->class_teacher_classes = $classTeacherClasses->map(fn($c) =>
            "{$c->class}" . ($c->section ? "-{$c->section}" : '')
        )->toArray();

        // Get reports received by this teacher from class teachers
        $reportsReceived = TeacherReport::where('subject_teacher_id', $teacher->user_id)
            ->with(['classTeacher', 'class'])
            ->orderByDesc('created_at')
            ->get();

        // Get teacher devices
        $devices = $teacher->user->devices()->get();

        return Inertia::render('Admin/Teachers/Show', compact('teacher', 'reportsReceived', 'devices'));
    }

    public function create()
    {
        $classes  = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);
        $subjects = Subject::where('is_active', true)->get(['id', 'subject_name', 'subject_code']);
        return Inertia::render('Admin/Teachers/Create', compact('classes', 'subjects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users',
            'password'       => 'required|min:8',
            'employee_id'    => 'required|unique:teacher_profiles',
            'phone'          => 'nullable|string',
            'qualification'  => 'nullable|string',
            'specialisation' => 'nullable|string',
            'date_joined'    => 'nullable|date',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'teacher',
        ]);

        $profile = TeacherProfile::create([
            'user_id'        => $user->id,
            'employee_id'    => $request->employee_id,
            'phone'          => $request->phone,
            'qualification'  => $request->qualification,
            'specialisation' => $request->specialisation,
            'date_joined'    => $request->date_joined,
        ]);

        $user->update(['teacher_profile_id' => $profile->id]);

        return redirect()->route('admin.teachers.show', $profile)
            ->with('success', 'Teacher created successfully.');
    }

    public function update(Request $request, TeacherProfile $teacher)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'phone'          => 'nullable|string',
            'qualification'  => 'nullable|string',
            'specialisation' => 'nullable|string',
        ]);

        $teacher->user->update(['name' => $request->name]);
        $teacher->update($request->only(['phone', 'qualification', 'specialisation', 'is_active']));

        return back()->with('success', 'Teacher updated successfully.');
    }

    public function destroy(TeacherProfile $teacher)
    {
        // Soft delete the user (archives the teacher)
        $teacher->user->delete();

        return redirect()->route('admin.teachers.index')
            ->with('success', "Teacher '{$teacher->user->name}' has been archived successfully.");
    }
}
