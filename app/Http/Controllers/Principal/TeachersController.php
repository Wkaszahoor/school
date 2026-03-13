<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{User, TeacherProfile, SchoolClass};
use Illuminate\Http\Request;
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

        // Get teacher devices
        $devices = $teacher->user->devices()->get();

        return Inertia::render('Principal/Teachers/Show', compact('teacher', 'devices'));
    }
}
