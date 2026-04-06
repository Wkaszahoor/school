<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\{CoScholasticGrade, SchoolClass, Student};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CoScholasticGradesController extends Controller
{
    const ACTIVITIES = [
        'uniform'    => 'Uniform & Discipline',
        'activities' => 'Activities & Sports',
        'digital'    => 'Digital Literacy',
        'written'    => 'Written Skills',
        'speaking'   => 'Speaking Skills',
    ];

    public function index(Request $request)
    {
        $teacher = auth()->user()->teacherProfile;
        $myClass = SchoolClass::where('class_teacher_id', $teacher?->user_id)
            ->first(['id', 'class', 'section']);

        if (!$myClass) {
            return Inertia::render('Teacher/CoScholasticGrades/Index', [
                'myClass'      => null,
                'students'     => [],
                'grades'       => [],
                'activities'   => self::ACTIVITIES,
                'academicYears' => [],
                'filters'      => [],
            ]);
        }

        $academicYears = CoScholasticGrade::distinct()
            ->pluck('academic_year')
            ->merge([Carbon::now()->year . '-' . (Carbon::now()->year + 1)])
            ->unique()->sort()->reverse()->values();

        $filters = $request->only(['academic_year', 'exam_type', 'term']);
        $filters['academic_year'] = $filters['academic_year'] ?? ($academicYears->first() ?? '');
        $filters['exam_type']     = $filters['exam_type']     ?? '';
        $filters['term']          = $filters['term']          ?? '';

        $students = Student::where('class_id', $myClass->id)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'admission_no']);

        // Load existing grades as nested map: [student_id][activity] => grade row
        $grades = [];
        if ($filters['academic_year'] && $filters['exam_type'] && $filters['term']) {
            $existing = CoScholasticGrade::where('class_id', $myClass->id)
                ->where('academic_year', $filters['academic_year'])
                ->where('exam_type', $filters['exam_type'])
                ->where('term', $filters['term'])
                ->get();

            foreach ($existing as $g) {
                $grades[$g->student_id][$g->activity] = [
                    'term1_grade' => $g->term1_grade,
                    'term2_grade' => $g->term2_grade,
                ];
            }
        }

        return Inertia::render('Teacher/CoScholasticGrades/Index', [
            'myClass'       => $myClass,
            'students'      => $students,
            'grades'        => $grades,
            'activities'    => self::ACTIVITIES,
            'academicYears' => $academicYears,
            'examTypes'     => config('school.exam_types'),
            'terms'         => config('school.terms'),
            'filters'       => $filters,
        ]);
    }

    public function store(Request $request)
    {
        $teacher = auth()->user()->teacherProfile;
        $myClass = SchoolClass::where('class_teacher_id', $teacher?->user_id)->first();

        if (!$myClass) {
            return back()->with('error', 'You are not assigned as a class teacher.');
        }

        $request->validate([
            'academic_year' => 'required|string|max:20',
            'exam_type'     => 'required|string|max:30',
            'term'          => 'required|string|max:20',
            'grades'        => 'required|array',
        ]);

        $userId = auth()->id();
        $saved  = 0;

        foreach ($request->grades as $studentId => $activities) {
            // Verify student belongs to this class
            $student = Student::where('id', $studentId)
                ->where('class_id', $myClass->id)
                ->first();
            if (!$student) continue;

            foreach ($activities as $activity => $gradeData) {
                if (!array_key_exists($activity, self::ACTIVITIES)) continue;

                CoScholasticGrade::updateOrCreate(
                    [
                        'student_id'    => $studentId,
                        'class_id'      => $myClass->id,
                        'academic_year' => $request->academic_year,
                        'exam_type'     => $request->exam_type,
                        'term'          => $request->term,
                        'activity'      => $activity,
                    ],
                    [
                        'term1_grade' => $gradeData['term1'] ?: null,
                        'term2_grade' => $gradeData['term2'] ?: null,
                        'entered_by'  => $userId,
                    ]
                );
                $saved++;
            }
        }

        return back()->with('success', "Co-scholastic grades saved for {$myClass->class}" . ($myClass->section ? "-{$myClass->section}" : '') . '.');
    }
}
