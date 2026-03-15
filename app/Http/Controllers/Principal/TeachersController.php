<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{User, TeacherProfile, SchoolClass};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

        // Add class teacher information and active device count
        $teachers->through(function($teacher) {
            $classTeacherClasses = SchoolClass::where('class_teacher_id', $teacher->user_id)->get(['id', 'class', 'section']);
            $teacher->class_teacher_classes = $classTeacherClasses->map(fn($c) =>
                "{$c->class}" . ($c->section ? "-{$c->section}" : '')
            )->toArray();

            $teacher->active_devices_count = $teacher->user->devices()->whereNull('unassigned_at')->count();

            return $teacher;
        });

        return Inertia::render('Principal/Teachers/Index', compact('teachers'));
    }

    public function show(TeacherProfile $teacher)
    {
        $teacher->load(['user', 'assignments.class', 'assignments.subject']);

        // Get class teacher assignments
        $classTeacherClasses = SchoolClass::where('class_teacher_id', $teacher->user_id)->get(['id', 'class', 'section']);
        $teacher->class_teacher_classes = $classTeacherClasses->map(fn($c) =>
            "{$c->class}" . ($c->section ? "-{$c->section}" : '')
        )->toArray();

        // Get subject teacher assignments
        $subjectAssignments = $teacher->assignments()
            ->with(['class', 'subject'])
            ->where('assignment_type', 'subject_teacher')
            ->get();

        // Get teacher devices
        $devices = $teacher->user->devices()->get();

        return Inertia::render('Principal/Teachers/Show', compact('teacher', 'devices', 'subjectAssignments'));
    }

    public function edit(TeacherProfile $teacher)
    {
        $teacher->load('user');
        return Inertia::render('Principal/Teachers/Edit', compact('teacher'));
    }

    public function update(Request $request, TeacherProfile $teacher)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $teacher->user_id,
            'phone' => 'nullable|string|max:20',
            'qualification' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'cnic' => 'nullable|string|max:20',
            'experience_years' => 'nullable|integer|min:0',
            'bio' => 'nullable|string',
            'previous_school' => 'nullable|string|max:255',
            'achievements' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        // Update user data
        $teacher->user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            if ($teacher->photo) {
                Storage::disk('public')->delete($teacher->photo);
            }
            $data['photo'] = $request->file('photo')->store('teachers', 'public');
        }

        // Update teacher profile
        unset($data['name'], $data['email']);
        $teacher->update($data);

        return back()->with('success', 'Teacher updated successfully');
    }

    public function destroy(TeacherProfile $teacher)
    {
        $teacher->delete();
        return back()->with('success', 'Teacher has been archived');
    }

    public function restore(TeacherProfile $teacher)
    {
        $teacher->restore();
        return back()->with('success', 'Teacher has been restored');
    }
}
