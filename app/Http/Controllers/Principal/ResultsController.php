<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{Result, SchoolClass, Subject, Student, AuditLog};
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResultsController extends Controller
{
    public function index(Request $request)
    {
        $results = Result::with(['student:id,full_name,class_id,stream', 'subject:id,subject_name', 'class:id,class,section,class_teacher_id', 'teacher'])
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->when($request->exam_type, fn($q) => $q->where('exam_type', $request->exam_type))
            ->when($request->subject_id, fn($q) => $q->where('subject_id', $request->subject_id))
            ->when($request->academic_year, fn($q) => $q->where('academic_year', $request->academic_year))
            ->when($request->term, fn($q) => $q->where('term', $request->term))
            ->when($request->approval_status !== null, fn($q) => $q->where('approval_status', $request->approval_status))
            ->when($request->stream, fn($q) => $q->whereHas('student', fn($sq) => $sq->where('stream', $request->stream)))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $classes        = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);
        $subjects       = Subject::select('id', 'subject_name')->orderBy('subject_name')->get();
        $examTypes      = config('school.exam_types');
        $terms          = config('school.terms');
        $academicYears  = Result::distinct()->orderByDesc('academic_year')->pluck('academic_year');
        $streams        = Student::distinct()->whereNotNull('stream')->orderBy('stream')->pluck('stream');

        return Inertia::render('Principal/Results/Index', compact('results', 'classes', 'subjects', 'examTypes', 'terms', 'academicYears', 'streams'));
    }

    public function approve(Request $request, Result $result)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:500',
        ]);

        // Check if principal can approve this result
        // Can approve if: status is 'class_teacher_approved' OR (status is 'pending' AND class has no class teacher)
        $class = SchoolClass::find($result->class_id);
        $canApprove = ($result->approval_status === 'class_teacher_approved') ||
                      ($result->approval_status === 'pending' && !$class->class_teacher_id);

        if (!$canApprove) {
            return back()->with('error', 'This result is not ready for principal approval.');
        }

        $oldValues = $result->getAttributes();
        $result->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'principal_remarks' => $request->remarks,
        ]);
        AuditLog::log('approve', 'Result', $result->id, $oldValues, $result->getAttributes());
        return back()->with('success', 'Result approved.');
    }

    public function bulkApprove(Request $request)
    {
        $request->validate(['result_ids' => 'required|array', 'result_ids.*' => 'exists:results,id']);

        // Get results with their class info
        $results = Result::with('class:id,class_teacher_id')
            ->whereIn('id', $request->result_ids)
            ->get();

        $approvedCount = 0;
        $skippedCount = 0;

        foreach ($results as $result) {
            // Check if principal can approve this result
            $canApprove = ($result->approval_status === 'class_teacher_approved') ||
                          ($result->approval_status === 'pending' && !$result->class->class_teacher_id);

            if ($canApprove) {
                $result->update([
                    'approval_status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
                $approvedCount++;
            } else {
                $skippedCount++;
            }
        }

        AuditLog::log('bulk_approve', 'Result', null, null, ['count' => $approvedCount, 'skipped' => $skippedCount]);

        $message = $approvedCount . ' results approved.';
        if ($skippedCount > 0) {
            $message .= " ($skippedCount results skipped - pending class teacher review)";
        }

        return back()->with('success', $message);
    }

    public function lock(Result $result)
    {
        $result->update(['is_locked' => true]);
        return back()->with('success', 'Result locked successfully.');
    }

    public function unlock(Result $result)
    {
        $result->update(['is_locked' => false]);
        return back()->with('success', 'Result unlocked successfully.');
    }

    public function reject(Request $request, Result $result)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
            'remarks' => 'nullable|string|max:500',
        ]);

        $oldValues = $result->getAttributes();
        $result->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'principal_remarks' => $request->remarks,
        ]);
        AuditLog::log('reject', 'Result', $result->id, $oldValues, $result->getAttributes());
        return back()->with('success', 'Result rejected and returned to subject teacher.');
    }

    public function reportCards(Request $request)
    {
        $request->validate([
            'exam_type'     => 'required|string',
            'academic_year' => 'required|string',
            'term'          => 'required|string',
            'class_id'      => 'nullable|integer|exists:classes,id',
        ]);

        $reportData = $this->buildReportData($request);
        $classes    = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);

        return Inertia::render('Principal/Results/ReportCards', [
            'reportData' => $reportData,
            'filters'    => [
                'class_id'      => $request->class_id,
                'exam_type'     => $request->exam_type,
                'academic_year' => $request->academic_year,
                'term'          => $request->term,
            ],
            'examTypes'  => config('school.exam_types'),
            'terms'      => config('school.terms'),
            'classes'    => $classes,
        ]);
    }

    private function buildReportData(Request $request): array
    {
        // Map term key (term1) to actual database value (Term 1)
        $termMap = config('school.terms');
        $termValue = $termMap[$request->term] ?? $request->term;

        $results = Result::with(['student.class', 'subject'])
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->where('exam_type',     $request->exam_type)
            ->where('academic_year', $request->academic_year)
            ->where('term',          $termValue)
            ->where('approval_status', 'approved')
            ->get();

        return $results
            ->groupBy('student_id')
            ->map(function ($rows) {
                $student       = $rows->first()->student;
                $totalObtained = $rows->sum('obtained_marks');
                $totalPossible = $rows->sum('total_marks');
                $overallPct    = $totalPossible > 0
                    ? round(($totalObtained / $totalPossible) * 100, 1) : 0;

                return [
                    'student' => [
                        'id'           => $student->id,
                        'full_name'    => $student->full_name,
                        'admission_no' => $student->admission_no,
                        'father_name'  => $student->father_name,
                        'photo'        => $student->photo,
                        'stream'       => $student->stream,
                        'class'        => $student->class ? [
                            'class'     => $student->class->class,
                            'section'   => $student->class->section,
                            'full_name' => $student->class->full_name,
                        ] : null,
                    ],
                    'results' => $rows->map(fn($r) => [
                        'subject_name'   => $r->subject?->subject_name ?? 'N/A',
                        'obtained_marks' => $r->obtained_marks,
                        'total_marks'    => $r->total_marks,
                        'percentage'     => round($r->percentage, 1),
                        'grade'          => $r->grade,
                        'gpa_point'      => $r->gpa_point,
                        'class_teacher_remarks' => $r->class_teacher_remarks,
                        'principal_remarks' => $r->principal_remarks,
                    ])->values(),
                    'summary' => [
                        'total_obtained'     => $totalObtained,
                        'total_possible'     => $totalPossible,
                        'overall_percentage' => $overallPct,
                        'overall_grade'      => $rows->first()->grade,
                        'average_gpa'        => round($rows->avg('gpa_point'), 2),
                        'pass_fail'          => $overallPct >= 33 ? 'PASS' : 'FAIL',
                    ],
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Export filtered results as CSV
     */
    public function export(Request $request)
    {
        $results = Result::with(['student', 'subject', 'class'])
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->when($request->exam_type, fn($q) => $q->where('exam_type', $request->exam_type))
            ->when($request->subject_id, fn($q) => $q->where('subject_id', $request->subject_id))
            ->when($request->academic_year, fn($q) => $q->where('academic_year', $request->academic_year))
            ->when($request->term, fn($q) => $q->where('term', $request->term))
            ->when($request->approval_status !== null, fn($q) => $q->where('approval_status', $request->approval_status))
            ->orderBy('class_id')
            ->orderBy('exam_type')
            ->orderBy('academic_year')
            ->orderBy('term')
            ->get();

        $filename = 'results-export-' . now()->format('Y-m-d-H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($results) {
            $file = fopen('php://output', 'w');

            // Write headers
            fputcsv($file, [
                'Student Name',
                'Admission No',
                'Class',
                'Subject',
                'Obtained Marks',
                'Total Marks',
                'Percentage',
                'Grade',
                'GPA',
                'Status',
                'Exam Type',
                'Academic Year',
                'Term',
            ]);

            // Write data rows
            foreach ($results as $result) {
                fputcsv($file, [
                    $result->student?->full_name ?? 'N/A',
                    $result->student?->admission_no ?? 'N/A',
                    ($result->class?->class ?? '') . ' ' . ($result->class?->section ?? ''),
                    $result->subject?->subject_name ?? 'N/A',
                    $result->obtained_marks,
                    $result->total_marks,
                    round($result->percentage, 2),
                    $result->grade,
                    round($result->gpa_point, 2),
                    ucfirst(str_replace('_', ' ', $result->approval_status)),
                    $result->exam_type,
                    $result->academic_year,
                    $result->term,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
