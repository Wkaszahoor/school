<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\{Attendance, SchoolClass, Student, TeacherAssignment, AuditLog};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $teacher = auth()->user()->teacherProfile;
        $userId = $teacher?->user_id;

        // Get classes where teacher is CLASS TEACHER (can mark attendance)
        $classTeacherClasses = SchoolClass::where('class_teacher_id', $userId)->get();

        // Get subject classes where teacher is SUBJECT TEACHER (can only view)
        $subjectClassIds = TeacherAssignment::where('teacher_id', $userId)
            ->where('assignment_type', 'subject_teacher')
            ->pluck('class_id')
            ->unique();

        $subjectClasses = SchoolClass::whereIn('id', $subjectClassIds)->get();

        // Separate classes by role
        $canMarkAttendance = $classTeacherClasses->pluck('id')->toArray();
        $canViewOnly = $subjectClasses->pluck('id')->toArray();
        $allClasses = $classTeacherClasses->merge($subjectClasses)->unique('id');

        $selectedClass = $request->class_id ? SchoolClass::find($request->class_id) : $allClasses->first();
        $date = $request->date ?? today()->toDateString();

        // Check if teacher is authorized to view this class
        if ($selectedClass && !in_array($selectedClass->id, array_merge($canMarkAttendance, $canViewOnly))) {
            return back()->with('error', 'Unauthorized: You are not assigned to this class.');
        }

        // Check if teacher can mark (only class teachers)
        $canMark = $selectedClass && in_array($selectedClass->id, $canMarkAttendance);

        $students = $selectedClass
            ? Student::where('class_id', $selectedClass->id)->where('is_active', true)->get()
            : collect();

        $existing = $selectedClass
            ? Attendance::where('class_id', $selectedClass->id)
                ->whereDate('attendance_date', $date)
                ->pluck('status', 'student_id')
            : collect();

        return Inertia::render('Teacher/Attendance/Mark', compact(
            'allClasses', 'selectedClass', 'students', 'existing', 'date', 'canMark', 'canMarkAttendance', 'canViewOnly'
        ));
    }

    public function store(Request $request)
    {
        $teacher = auth()->user()->teacherProfile;
        $userId = $teacher?->user_id;

        $request->validate([
            'class_id'    => 'required|exists:classes,id',
            'date'        => 'required|date',
            'attendance'  => 'required|array',
            'attendance.*'=> 'in:P,A,L',
        ]);

        // Check if teacher is CLASS TEACHER for this class (only they can mark attendance)
        $class = SchoolClass::find($request->class_id);
        if (!$class || $class->class_teacher_id !== $userId) {
            return back()->with('error', 'Only class teachers can mark attendance. You can view attendance as a subject teacher.');
        }

        $absentStudents = [];
        foreach ($request->attendance as $studentId => $status) {
            Attendance::updateOrCreate(
                ['student_id' => $studentId, 'class_id' => $request->class_id, 'attendance_date' => $request->date],
                ['status' => $status, 'marked_by' => auth()->id()]
            );

            // Collect absent students
            if ($status === 'A') {
                $absentStudents[] = $studentId;
            }
        }

        // Report absent students to class teacher and principal
        if (!empty($absentStudents)) {
            $this->reportAbsentStudents($request->class_id, $absentStudents, $request->date);
        }

        AuditLog::log('bulk_mark', 'Attendance', $request->class_id, null, ['date' => $request->date, 'count' => count($request->attendance)]);
        return back()->with('success', 'Attendance saved successfully.');
    }

    public function reportAbsence(Request $request)
    {
        $teacher = auth()->user()->teacherProfile;
        $userId = $teacher?->user_id;

        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'student_ids' => 'required|array',
            'student_ids.*' => 'required|integer|exists:students,id',
            'date' => 'required|date',
            'reason' => 'nullable|string|max:500',
        ]);

        $class = SchoolClass::find($request->class_id);

        // Check if teacher is assigned to this class (either as class teacher or subject teacher)
        $isClassTeacher = $class->class_teacher_id === $userId;
        $isSubjectTeacher = TeacherAssignment::where('teacher_id', $userId)
            ->where('class_id', $request->class_id)
            ->where('assignment_type', 'subject_teacher')
            ->exists();

        if (!$isClassTeacher && !$isSubjectTeacher) {
            return back()->with('error', 'You are not assigned to this class.');
        }

        $absentStudents = Student::whereIn('id', $request->student_ids)->get(['id', 'full_name', 'admission_no']);

        // Log absence report
        AuditLog::log('absence_report', 'Attendance', $class->id, null, [
            'date' => $request->date,
            'reported_by' => auth()->user()->name,
            'reported_by_role' => $isClassTeacher ? 'class_teacher' : 'subject_teacher',
            'absent_count' => $absentStudents->count(),
            'absent_students' => $absentStudents->pluck('id')->toArray(),
            'reason' => $request->reason,
        ]);

        return back()->with('success', 'Absence report submitted to class teacher and principal.');
    }

    private function reportAbsentStudents($classId, $absentStudentIds, $date)
    {
        $class = SchoolClass::find($classId);
        if (!$class || !$class->class_teacher_id) {
            return; // No class teacher assigned
        }

        $absentStudents = Student::whereIn('id', $absentStudentIds)->get(['id', 'full_name', 'admission_no']);
        $classTeacher = $class->classTeacher;

        // Create notification message for class teacher
        $studentList = $absentStudents->pluck('full_name')->join(', ');
        $message = "Alert: {$absentStudents->count()} student(s) absent on " . date('M d, Y', strtotime($date)) . " in {$class->class}-{$class->section}: {$studentList}";

        // Log for audit trail
        AuditLog::log('absence_report', 'Attendance', $classId, null, [
            'date' => $date,
            'absent_count' => $absentStudents->count(),
            'absent_students' => $absentStudents->pluck('id')->toArray(),
        ]);
    }

    public function report(Request $request)
    {
        $teacher = auth()->user()->teacherProfile;
        $userId = $teacher?->user_id;

        // Get classes where teacher is CLASS TEACHER
        $classTeacherClassIds = SchoolClass::where('class_teacher_id', $userId)->pluck('id');

        // Get subject classes
        $subjectClassIds = TeacherAssignment::where('teacher_id', $userId)
            ->where('assignment_type', 'subject_teacher')
            ->pluck('class_id');

        $allClassIds = $classTeacherClassIds->merge($subjectClassIds)->unique();

        $attendance = Attendance::with(['student', 'class'])
            ->whereIn('class_id', $allClassIds)
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->when($request->date_from, fn($q) => $q->whereDate('attendance_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('attendance_date', '<=', $request->date_to))
            ->latest('attendance_date')
            ->paginate(30)
            ->withQueryString();

        $classes = SchoolClass::whereIn('id', $allClassIds)->get();

        $filters = [
            'class_id' => $request->class_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];

        return Inertia::render('Teacher/Attendance/Report', compact('attendance', 'classes', 'filters'));
    }

    public function reportPdf(Request $request)
    {
        $teacher = auth()->user()->teacherProfile;
        $userId = $teacher?->user_id;

        // Get classes where teacher is CLASS TEACHER
        $classTeacherClassIds = SchoolClass::where('class_teacher_id', $userId)->pluck('id');

        // Get subject classes
        $subjectClassIds = TeacherAssignment::where('teacher_id', $userId)
            ->where('assignment_type', 'subject_teacher')
            ->pluck('class_id');

        $allClassIds = $classTeacherClassIds->merge($subjectClassIds)->unique();

        $attendance = Attendance::with(['student', 'class'])
            ->whereIn('class_id', $allClassIds)
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->when($request->date_from, fn($q) => $q->whereDate('attendance_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('attendance_date', '<=', $request->date_to))
            ->latest('attendance_date')
            ->get();

        $selectedClass = $request->class_id ? SchoolClass::find($request->class_id) : null;
        $fromDate = $request->date_from;
        $toDate = $request->date_to;

        $pdf = Pdf::loadView('pdf.attendance-report', [
            'attendance' => $attendance,
            'selectedClass' => $selectedClass,
            'subject' => null,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);

        return $pdf->download('attendance-report-' . now()->format('Y-m-d') . '.pdf');
    }
}
