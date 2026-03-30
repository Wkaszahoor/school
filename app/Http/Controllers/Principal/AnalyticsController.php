<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{SchoolClass, Result, User, LessonPlan, TeachingResource};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // STUDENT RESULTS ANALYTICS
    // ──────────────────────────────────────────────────────────────────────────

    public function studentResults(Request $request)
    {
        $classes = SchoolClass::where('is_active', true)->orderByRaw('CAST(class AS UNSIGNED), section')->get(['id', 'class', 'section']);
        $selectedId = $request->class_id;

        // Use years actually present in data; fall back to computed current year
        $years = Result::select('academic_year')->distinct()->orderByDesc('academic_year')->pluck('academic_year');
        $latestYear = $years->first() ?? $this->currentYear();
        $academicYear = $request->academic_year ?? $latestYear;

        $analytics = null;

        if ($selectedId) {
            $class = SchoolClass::findOrFail($selectedId);
            $analytics = $this->computeClassAnalytics($class, $academicYear);
        }

        return Inertia::render('Principal/Analytics/StudentResults', [
            'classes'      => $classes,
            'selectedId'   => $selectedId ? (int) $selectedId : null,
            'academicYear' => $academicYear,
            'years'        => $years,
            'analytics'    => $analytics,
        ]);
    }

    private function computeClassAnalytics(SchoolClass $class, string $year): array
    {
        // All students in this class with their results
        $students = DB::table('students as s')
            ->where('s.class_id', $class->id)
            ->where('s.is_active', true)
            ->select('s.id', 's.full_name', 's.admission_no', 's.email')
            ->get();

        if ($students->isEmpty()) {
            return ['class' => $class->class . ' ' . $class->section, 'students' => [], 'top3' => [], 'weak' => [], 'stream_recommendations' => [], 'subject_averages' => []];
        }

        $studentIds = $students->pluck('id');

        // Exam results (weighted 70%) — if year filter returns nothing, fall back to all years
        $examQuery = Result::where('class_id', $class->id)
            ->whereIn('student_id', $studentIds)
            ->where('academic_year', $year);

        if ($examQuery->count() === 0) {
            $examQuery = Result::where('class_id', $class->id)
                ->whereIn('student_id', $studentIds);
        }

        $examResults = $examQuery
            ->select('student_id', 'subject_id', DB::raw('AVG(percentage) as avg_pct'))
            ->groupBy('student_id', 'subject_id')
            ->get()
            ->groupBy('student_id');

        // Weekly test results (weighted 30%)
        $weeklyResults = DB::table('weekly_test_results')
            ->where('class_id', $class->id)
            ->whereIn('student_id', $studentIds)
            ->selectRaw('student_id, subject_id, AVG(obtained_marks / total_marks * 100) as avg_pct')
            ->groupBy('student_id', 'subject_id')
            ->get()
            ->groupBy('student_id');

        // Subject map
        $subjects = DB::table('subjects')->pluck('subject_name', 'id');
        $subjectCodes = DB::table('subjects')->pluck('subject_code', 'id');

        // Compute each student's composite score and per-subject breakdown
        $studentData = [];
        foreach ($students as $student) {
            $subjectScores = [];

            // Merge exam and weekly per subject
            $allSubjectIds = collect();
            if (isset($examResults[$student->id])) {
                $allSubjectIds = $allSubjectIds->merge($examResults[$student->id]->pluck('subject_id'));
            }
            if (isset($weeklyResults[$student->id])) {
                $allSubjectIds = $allSubjectIds->merge($weeklyResults[$student->id]->pluck('subject_id'));
            }
            $allSubjectIds = $allSubjectIds->unique();

            foreach ($allSubjectIds as $subjectId) {
                $examPct   = optional($examResults[$student->id] ?? collect())->firstWhere('subject_id', $subjectId)?->avg_pct ?? 0;
                $weeklyPct = optional($weeklyResults[$student->id] ?? collect())->firstWhere('subject_id', $subjectId)?->avg_pct ?? 0;

                $hasExam   = isset($examResults[$student->id]) && $examResults[$student->id]->firstWhere('subject_id', $subjectId);
                $hasWeekly = isset($weeklyResults[$student->id]) && $weeklyResults[$student->id]->firstWhere('subject_id', $subjectId);

                if ($hasExam && $hasWeekly) {
                    $composite = ($examPct * 0.70) + ($weeklyPct * 0.30);
                } elseif ($hasExam) {
                    $composite = $examPct;
                } else {
                    $composite = $weeklyPct;
                }

                $subjectScores[$subjectId] = [
                    'subject_name' => $subjects[$subjectId] ?? 'Unknown',
                    'subject_code' => $subjectCodes[$subjectId] ?? '',
                    'exam_pct'     => round($examPct, 1),
                    'weekly_pct'   => round($weeklyPct, 1),
                    'composite'    => round($composite, 1),
                ];
            }

            $overallComposite = count($subjectScores) > 0
                ? round(collect($subjectScores)->avg('composite'), 1)
                : 0;

            $studentData[] = [
                'id'           => $student->id,
                'name'         => $student->full_name,
                'admission_no' => $student->admission_no,
                'subjects'     => $subjectScores,
                'overall'      => $overallComposite,
                'is_weak'      => $overallComposite < 50 && count($subjectScores) > 0,
                'weak_subjects' => collect($subjectScores)->filter(fn($s) => $s['composite'] < 50)->map(fn($s) => $s['subject_name'])->values()->toArray(),
            ];
        }

        // Sort by overall descending
        usort($studentData, fn($a, $b) => $b['overall'] <=> $a['overall']);

        // Assign positions
        foreach ($studentData as $i => &$s) {
            $s['position'] = $i + 1;
        }
        unset($s);

        $top3 = array_slice($studentData, 0, 3);
        $weak = array_filter($studentData, fn($s) => $s['is_weak']);

        // Stream recommendations for class 8 (or configurable)
        $classNum = (int) preg_replace('/[^0-9]/', '', $class->class);
        $streamRecs = [];
        if ($classNum >= 8) {
            $streamRecs = $this->computeStreamRecommendations($studentData, $subjectCodes->flip());
        }

        // Class-wide subject averages
        $subjectAverages = [];
        $allSubjects = collect($studentData)->flatMap(fn($s) => array_keys($s['subjects']))->unique();
        foreach ($allSubjects as $subId) {
            $scores = collect($studentData)->map(fn($s) => $s['subjects'][$subId]['composite'] ?? null)->filter()->values();
            if ($scores->count() > 0) {
                $subjectAverages[] = [
                    'subject_id'   => $subId,
                    'subject_name' => $subjects[$subId] ?? 'Unknown',
                    'subject_code' => $subjectCodes[$subId] ?? '',
                    'avg'          => round($scores->avg(), 1),
                    'min'          => round($scores->min(), 1),
                    'max'          => round($scores->max(), 1),
                    'below_50'     => $scores->filter(fn($v) => $v < 50)->count(),
                    'total'        => $scores->count(),
                ];
            }
        }
        usort($subjectAverages, fn($a, $b) => $a['avg'] <=> $b['avg']); // weakest first

        return [
            'class'                  => $class->class . ' ' . $class->section,
            'class_num'              => $classNum,
            'total_students'         => count($studentData),
            'students'               => $studentData,
            'top3'                   => $top3,
            'weak'                   => array_values($weak),
            'stream_recommendations' => $streamRecs,
            'subject_averages'       => $subjectAverages,
            'academic_year'          => $year,
        ];
    }

    private function computeStreamRecommendations(array $studentData, $codeToId): array
    {
        // Stream → required subject codes with weight
        $streams = [
            'Pre-Medical'    => ['PHY' => 0.30, 'CHE' => 0.30, 'BIO' => 0.40],
            'Pre-Engineering'=> ['PHY' => 0.30, 'CHE' => 0.30, 'MATH' => 0.40],
            'ICS'            => ['MATH' => 0.35, 'CS' => 0.35, 'PHY' => 0.30],
            'Arts/FA'        => ['ENG' => 0.30, 'URD' => 0.30, 'HIST' => 0.20, 'GEO' => 0.20],
            'Commerce'       => ['ECO' => 0.35, 'MATH' => 0.35, 'ENG' => 0.30],
        ];

        // Build code-alias map (handle slight differences like MATH vs MAT, HIST vs HIS)
        $codeAliases = [
            'MATH' => ['MATH', 'MAT', 'AMAT'],
            'HIST' => ['HIST', 'HIS', 'HISTORY'],
            'CS'   => ['CS', 'COM', 'COMP'],
            'GEO'  => ['GEO', 'GEOG'],
        ];

        $recs = [];
        foreach ($studentData as $student) {
            // Build code → score map for this student
            $scoreByCode = [];
            foreach ($student['subjects'] as $subId => $sub) {
                $code = strtoupper($sub['subject_code']);
                $scoreByCode[$code] = $sub['composite'];
            }

            $streamScores = [];
            foreach ($streams as $streamName => $weights) {
                $totalWeight = 0;
                $weightedSum = 0;
                $hasData = false;

                foreach ($weights as $code => $weight) {
                    $aliases = $codeAliases[$code] ?? [$code];
                    $score   = null;
                    foreach ($aliases as $alias) {
                        if (isset($scoreByCode[$alias])) {
                            $score = $scoreByCode[$alias];
                            break;
                        }
                    }
                    if ($score !== null) {
                        $weightedSum += $score * $weight;
                        $totalWeight += $weight;
                        $hasData = true;
                    }
                }

                if ($hasData && $totalWeight > 0) {
                    $streamScores[$streamName] = round($weightedSum / $totalWeight, 1);
                }
            }

            if (empty($streamScores)) continue;

            arsort($streamScores);
            $recommended = array_key_first($streamScores);
            $topScore    = $streamScores[$recommended];

            $confidence = match(true) {
                $topScore >= 70 => 'high',
                $topScore >= 50 => 'medium',
                default         => 'low',
            };

            $recs[] = [
                'student_id'   => $student['id'],
                'name'         => $student['name'],
                'admission_no' => $student['admission_no'],
                'recommended'  => $recommended,
                'score'        => $topScore,
                'confidence'   => $confidence,
                'all_scores'   => $streamScores,
                'overall'      => $student['overall'],
            ];
        }

        // Sort by recommended stream, then score
        usort($recs, fn($a, $b) => [$a['recommended'], $b['score']] <=> [$b['recommended'], $a['score']]);
        return $recs;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // TEACHER PERFORMANCE ANALYTICS
    // ──────────────────────────────────────────────────────────────────────────

    public function teacherPerformance(Request $request)
    {
        $latestYear   = Result::select('academic_year')->distinct()->orderByDesc('academic_year')->value('academic_year') ?? $this->currentYear();
        $academicYear = $request->academic_year ?? $latestYear;
        $teachers     = User::where('role', 'teacher')->orderBy('name')->get(['id', 'name', 'email']);

        $scores = [];
        foreach ($teachers as $teacher) {
            $scores[] = $this->computeTeacherScore($teacher, $academicYear);
        }

        // Sort by total score descending
        usort($scores, fn($a, $b) => $b['total_score'] <=> $a['total_score']);
        foreach ($scores as $i => &$s) { $s['rank'] = $i + 1; }
        unset($s);

        $years = Result::select('academic_year')->distinct()->orderByDesc('academic_year')->pluck('academic_year');

        return Inertia::render('Principal/Analytics/TeacherPerformance', [
            'teachers'     => $scores,
            'academicYear' => $academicYear,
            'years'        => $years,
        ]);
    }

    private function computeTeacherScore(User $teacher, string $year): array
    {
        // ── 1. Lesson Plans (25 pts) ─────────────────────────────────────────
        $totalPlans    = LessonPlan::where('teacher_id', $teacher->id)->count();
        $approvedPlans = LessonPlan::where('teacher_id', $teacher->id)->where('approval_status', 'Approved')->count();
        $pendingPlans  = LessonPlan::where('teacher_id', $teacher->id)->where('approval_status', 'Pending')->count();

        $planScore = $totalPlans > 0
            ? min(25, round(($approvedPlans / $totalPlans) * 25, 1))
            : 0;

        // ── 2. Weekly Tests Conducted (20 pts) ───────────────────────────────
        $weeklyTestCount = DB::table('weekly_test_results')
            ->where('teacher_id', $teacher->id)
            ->distinct()
            ->selectRaw('COUNT(DISTINCT CONCAT(class_id, subject_id, test_date)) as cnt')
            ->value('cnt') ?? 0;

        $weeklyScore = min(20, round($weeklyTestCount * 0.5, 1));

        // ── 3. Lectures Delivered (20 pts) ───────────────────────────────────
        // = distinct (class, subject, date) combos where teacher marked attendance
        $lectureCount = DB::table('attendance')
            ->where('marked_by', $teacher->id)
            ->selectRaw('COUNT(DISTINCT CONCAT(class_id, \'-\', subject_id, \'-\', attendance_date)) as cnt')
            ->value('cnt') ?? 0;

        $lectureScore = min(20, round($lectureCount * 0.2, 1));

        // ── 4. Teaching Resources Uploaded (15 pts) ──────────────────────────
        $resourceCount    = TeachingResource::where('created_by', $teacher->id)->count();
        $resourceDownloads = TeachingResource::where('created_by', $teacher->id)->sum('download_count');
        $resourceScore    = min(15, ($resourceCount * 2) + ($resourceDownloads * 0.1));
        $resourceScore    = round($resourceScore, 1);

        // ── 5. Student Exam Performance (20 pts) ─────────────────────────────
        $avgStudentScore = Result::where('teacher_id', $teacher->id)
            ->where('academic_year', $year)
            ->avg('percentage') ?? 0;

        $studentPerfScore = round(($avgStudentScore / 100) * 20, 1);

        $totalScore = round($planScore + $weeklyScore + $lectureScore + $resourceScore + $studentPerfScore, 1);

        $performanceLabel = match(true) {
            $totalScore >= 85 => 'Excellent',
            $totalScore >= 70 => 'Very Good',
            $totalScore >= 55 => 'Good',
            $totalScore >= 40 => 'Satisfactory',
            default           => 'Needs Improvement',
        };

        // Get classes taught
        $classesTaught = DB::table('results')
            ->where('results.teacher_id', $teacher->id)
            ->where('results.academic_year', $year)
            ->join('classes', 'classes.id', '=', 'results.class_id')
            ->selectRaw('DISTINCT CONCAT(classes.class, " ", classes.section) as class_name')
            ->pluck('class_name');

        // Proficiency test score (if taken)
        $profTest = DB::table('proficiency_test_attempts as a')
            ->join('proficiency_test_assignments as ass', 'ass.id', '=', 'a.assignment_id')
            ->where('a.user_id', $teacher->id)
            ->where('a.status', 'completed')
            ->orderByDesc('a.completed_at')
            ->first(['a.percentage', 'a.band_score', 'a.completed_at']);

        return [
            'id'              => $teacher->id,
            'name'            => $teacher->name,
            'email'           => $teacher->email,
            'total_score'     => $totalScore,
            'performance'     => $performanceLabel,
            'classes_taught'  => $classesTaught,
            'proficiency_test' => $profTest ? [
                'percentage'  => $profTest->percentage,
                'band_score'  => $profTest->band_score,
                'date'        => $profTest->completed_at,
            ] : null,
            'breakdown' => [
                'lesson_plans' => [
                    'score'   => $planScore,
                    'max'     => 25,
                    'detail'  => "{$approvedPlans} approved / {$totalPlans} submitted ({$pendingPlans} pending)",
                ],
                'weekly_tests' => [
                    'score'  => $weeklyScore,
                    'max'    => 20,
                    'detail' => "{$weeklyTestCount} tests conducted",
                ],
                'lectures' => [
                    'score'  => $lectureScore,
                    'max'    => 20,
                    'detail' => "{$lectureCount} lectures delivered",
                ],
                'resources' => [
                    'score'  => $resourceScore,
                    'max'    => 15,
                    'detail' => "{$resourceCount} resources uploaded ({$resourceDownloads} downloads)",
                ],
                'student_performance' => [
                    'score'  => $studentPerfScore,
                    'max'    => 20,
                    'detail' => "Students avg: " . round($avgStudentScore, 1) . "%",
                ],
            ],
        ];
    }

    private function currentYear(): string
    {
        $year = date('Y');
        return ($year - 1) . '-' . substr($year, -2);
    }
}
