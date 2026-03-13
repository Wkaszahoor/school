<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{SchoolClass, Student};
use Illuminate\Http\Request;
use Inertia\Inertia;

class UniversityStudentsController extends Controller
{
    public function index()
    {
        $universityClass = SchoolClass::where('class', 'University Student')->firstOrFail();
        $students = $universityClass->students()
            ->orderBy('full_name')
            ->paginate(20);

        return Inertia::render('Admin/UniversityStudents/Index', [
            'universityClass' => $universityClass,
            'students' => $students,
        ]);
    }

    public function show(Student $student)
    {
        // Ensure student is in university class
        $universityClass = SchoolClass::where('class', 'University Student')->firstOrFail();
        if ($student->class_id !== $universityClass->id) {
            abort(404);
        }

        return Inertia::render('Admin/UniversityStudents/Show', [
            'student' => $student,
            'universityClass' => $universityClass,
        ]);
    }
}
