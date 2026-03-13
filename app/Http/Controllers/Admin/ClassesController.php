<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{SchoolClass, Subject};
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassesController extends Controller
{
    public function index()
    {
        $classes = SchoolClass::withCount('students')->latest()->paginate(20);
        return Inertia::render('Admin/Classes/Index', compact('classes'));
    }

    public function show(SchoolClass $class)
    {
        $class->load('students');
        return Inertia::render('Admin/Classes/Show', [
            'class' => $class,
            'students' => $class->students,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:100',
            'section'       => 'nullable|string|max:50',
            'academic_year' => 'required|string|max:20',
        ]);

        SchoolClass::create($request->validated());
        return back()->with('success', 'Class created successfully.');
    }

    public function update(Request $request, SchoolClass $class)
    {
        $request->validate([
            'name'          => 'required|string|max:100',
            'section'       => 'nullable|string|max:50',
            'academic_year' => 'required|string|max:20',
            'is_active'     => 'boolean',
        ]);

        $class->update($request->validated());
        return back()->with('success', 'Class updated.');
    }

    public function destroy(SchoolClass $class)
    {
        // Only allow deletion if class has no students
        if ($class->students()->count() > 0) {
            return back()->with('error', 'Cannot delete a class with students. Remove all students first.');
        }

        $className = $class->name;
        $class->delete();
        return back()->with('success', "Class '{$className}' deleted successfully.");
    }

    public function subjects(SchoolClass $class)
    {
        $class->load('subjects');
        $allSubjects = Subject::where('is_active', true)->get();
        return Inertia::render('Admin/Classes/Subjects', compact('class', 'allSubjects'));
    }

    public function syncSubjects(Request $request, SchoolClass $class)
    {
        $request->validate(['subject_ids' => 'array', 'subject_ids.*' => 'exists:subjects,id']);
        $class->subjects()->sync($request->subject_ids ?? []);
        return back()->with('success', 'Subjects updated.');
    }
}
