<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function global(Request $request)
    {
        $query = $request->query('q', '');
        $user = auth()->user();

        if (!$query || strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [];

        // Search Students
        $students = Student::where('full_name', 'like', "%$query%")
            ->orWhere('admission_no', 'like', "%$query%")
            ->limit(10)
            ->get()
            ->map(function ($student) {
                return [
                    'type' => 'student',
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'class' => $student->class?->class ?? 'N/A',
                    'url' => route('principal.students.show', $student->id),
                ];
            });

        // Search Teachers
        $teachers = User::where('role', 'teacher')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%$query%")
                  ->orWhere('email', 'like', "%$query%");
            })
            ->limit(10)
            ->get()
            ->map(function ($teacher) {
                // Get the subject they teach
                $subject = $teacher->teacherAssignments()->first()?->subject?->subject_name ?? 'Multiple subjects';

                // Get TeacherProfile ID for the route
                $profileId = $teacher->teacher_profile_id ?? $teacher->id;

                return [
                    'type' => 'teacher',
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'subject' => $subject,
                    'url' => route('principal.teachers.show', $profileId),
                ];
            });

        // Search Classes
        $classes = SchoolClass::where('class', 'like', "%$query%")
            ->orWhere('section', 'like', "%$query%")
            ->limit(10)
            ->get()
            ->map(function ($class) {
                $section = $class->section ?? 'N/A';
                return [
                    'type' => 'class',
                    'id' => $class->id,
                    'name' => $class->class,
                    'info' => "Section: $section",
                    'url' => route('principal.classes.show', $class->id),
                ];
            });

        $results = [
            ...$students,
            ...$teachers,
            ...$classes,
        ];

        // Limit total results
        $results = array_slice($results, 0, 15);

        return response()->json(['results' => $results]);
    }
}
