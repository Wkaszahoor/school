<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{AdmissionCard, Student, StudentDatesheet, AttendanceCriteria, Attendance, SchoolClass};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class AdmissionCardsController extends Controller
{
    public function index(Request $request)
    {
        $academicYear = $request->get('academic_year', config('school.current_academic_year', '2025-26'));
        $classId = $request->get('class_id', null);
        $examPeriod = $request->get('exam_period', '');

        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);

        // Get distinct exam periods for selected academic year
        $examPeriods = StudentDatesheet::where('academic_year', $academicYear)
            ->distinct('exam_period')
            ->orderBy('exam_period')
            ->pluck('exam_period');

        $students = collect();
        $datasheets = collect();
        $existingCards = collect();

        if ($classId) {
            // Get all students in this class
            $students = Student::where('class_id', $classId)
                ->where('is_active', true)
                ->with('class')
                ->get(['id', 'admission_no', 'full_name', 'class_id', 'class', 'dob', 'gender', 'father_name', 'guardian_name'])
                ->map(function ($student) use ($academicYear, $examPeriod) {
                    // Calculate attendance %
                    $totalAttendance = Attendance::where('student_id', $student->id)
                        ->whereYear('attendance_date', substr($academicYear, 0, 4))
                        ->count();

                    $presentDays = Attendance::where('student_id', $student->id)
                        ->where('status', 'P')
                        ->whereYear('attendance_date', substr($academicYear, 0, 4))
                        ->count();

                    $attendancePercent = $totalAttendance > 0
                        ? round(($presentDays / $totalAttendance) * 100, 2)
                        : 0;

                    return [
                        ...$student->toArray(),
                        'attendance_percent' => $attendancePercent,
                    ];
                });

            // Get datasheets for selected class and exam period
            $datasheetQuery = StudentDatesheet::where('academic_year', $academicYear);

            $classObj = SchoolClass::find($classId);
            if ($classObj) {
                $datasheetQuery->where('class_name', 'like', '%' . $classObj->class . '%');
            }

            if ($examPeriod) {
                $datasheetQuery->where('exam_period', $examPeriod);
            }

            $datasheets = $datasheetQuery->orderBy('exam_date')->get();

            // Get existing admission cards
            $existingCards = AdmissionCard::where('class_id', $classId)
                ->where('academic_year', $academicYear)
                ->when($examPeriod, fn($q) => $q->where('exam_period', $examPeriod))
                ->pluck('status', 'student_id');
        }

        return Inertia::render('Principal/AdmissionCards/Index', [
            'classes' => $classes,
            'examPeriods' => $examPeriods,
            'students' => $students,
            'datesheets' => $datasheets,
            'existingCards' => $existingCards,
            'academicYear' => $academicYear,
            'selectedClassId' => $classId,
            'selectedExamPeriod' => $examPeriod,
        ]);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'exam_period' => 'required|string',
            'academic_year' => 'required|string',
            'student_ids' => 'required|array',
            'student_ids.*' => 'numeric|exists:students,id',
        ]);

        $criterion = AttendanceCriteria::where('class_id', $validated['class_id'])
            ->where('academic_year', $validated['academic_year'])
            ->first();

        $minAttendance = $criterion?->min_attendance_percent ?? 75;
        $generated = 0;

        foreach ($validated['student_ids'] as $studentId) {
            $student = Student::find($studentId);
            if (!$student) continue;

            // Calculate attendance %
            $totalAttendance = Attendance::where('student_id', $studentId)
                ->whereYear('attendance_date', substr($validated['academic_year'], 0, 4))
                ->count();

            $presentDays = Attendance::where('student_id', $studentId)
                ->where('status', 'P')
                ->whereYear('attendance_date', substr($validated['academic_year'], 0, 4))
                ->count();

            $attendancePercent = $totalAttendance > 0
                ? ($presentDays / $totalAttendance) * 100
                : 0;

            $eligible = $attendancePercent >= $minAttendance;

            AdmissionCard::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'class_id' => $validated['class_id'],
                    'academic_year' => $validated['academic_year'],
                    'exam_period' => $validated['exam_period'],
                ],
                [
                    'attendance_eligible' => $eligible,
                    'attendance_percent' => round($attendancePercent, 2),
                    'status' => 'draft',
                    'generated_by' => auth()->id(),
                ]
            );

            $generated++;
        }

        return back()->with('success', "Generated admission cards for {$generated} students");
    }

    public function download(AdmissionCard $card)
    {
        $card->load(['student', 'class']);

        $datesheets = StudentDatesheet::where('academic_year', $card->academic_year)
            ->where('exam_period', $card->exam_period)
            ->where('class_name', 'like', '%' . $card->class->class . '%')
            ->orderBy('exam_date')
            ->get();

        $pdf = Pdf::loadView('pdf.admission-card', [
            'card' => $card,
            'datesheets' => $datesheets,
            'status' => $card->status,
        ]);

        return $pdf->download("admission-card-{$card->student->admission_no}.pdf");
    }

    public function bulkDownload(Request $request)
    {
        $validated = $request->validate([
            'card_ids' => 'required|array',
            'card_ids.*' => 'numeric|exists:admission_cards,id',
        ]);

        $cards = AdmissionCard::whereIn('id', $validated['card_ids'])->with(['student', 'class'])->get();

        if ($cards->isEmpty()) {
            return back()->with('error', 'No cards selected');
        }

        // Create temporary zip file
        $zip = new \ZipArchive();
        $zipPath = storage_path('app/temp-' . time() . '.zip');
        $zip->open($zipPath, \ZipArchive::CREATE);

        foreach ($cards as $card) {
            $datesheets = StudentDatesheet::where('academic_year', $card->academic_year)
                ->where('exam_period', $card->exam_period)
                ->where('class_name', 'like', '%' . $card->class->class . '%')
                ->orderBy('exam_date')
                ->get();

            $pdf = Pdf::loadView('pdf.admission-card', [
                'card' => $card,
                'datesheets' => $datesheets,
                'status' => $card->status,
            ]);

            $zip->addFromString("admission-card-{$card->student->admission_no}.pdf", $pdf->output());
        }

        $zip->close();

        return response()->download($zipPath, 'admission-cards.zip')->deleteFileAfterSend(true);
    }
}
