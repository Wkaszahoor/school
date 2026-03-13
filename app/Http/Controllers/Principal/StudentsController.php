<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{Student, SchoolClass, SubjectGroup, AuditLog};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentsController extends Controller
{
    public function index(Request $request)
    {
        $students = Student::with(['class', 'subjectGroup'])
            ->when($request->search, fn($q) =>
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('admission_no', 'like', "%{$request->search}%"))
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->when($request->subject_group_id, fn($q) => $q->where('subject_group_id', $request->subject_group_id))
            ->where('is_active', true)
            ->latest()
            ->paginate(25, ['*'], 'page', $request->get('page', 1))
            ->withQueryString()
            ->through(fn($student) => [
                'id' => $student->id,
                'admission_no' => $student->admission_no,
                'full_name' => $student->full_name,
                'class' => $student->class,
                'subjectGroup' => $student->subjectGroup,
                'is_active' => $student->is_active,
            ]);

        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);
        $groups = SubjectGroup::where('is_active', true)->get(['id', 'group_name', 'stream']);

        $filters = [
            'search' => $request->get('search'),
            'class_id' => $request->get('class_id'),
            'subject_group_id' => $request->get('subject_group_id'),
        ];

        return Inertia::render('Principal/Students/Index', compact('students', 'classes', 'groups', 'filters'));
    }

    public function create()
    {
        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);
        $groups = SubjectGroup::where('is_active', true)->get(['id', 'group_name', 'stream']);
        return Inertia::render('Principal/Students/Create', compact('classes', 'groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'admission_no'     => 'required|unique:students',
            'date_of_birth'    => 'required|date',
            'gender'           => 'required|in:male,female,other',
            'class_id'         => 'required|exists:classes,id',
            'subject_group_id' => 'nullable|exists:subject_groups,id',
            'guardian_name'    => 'nullable|string',
            'guardian_phone'   => 'nullable|string',
            'guardian_email'   => 'nullable|email',
            'address'          => 'nullable|string',
            'blood_group'      => 'nullable|string',
            'is_orphan'        => 'boolean',
            'trust_notes'      => 'nullable|string',
            'join_date_kort'   => 'nullable|date',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('students', 'public');
        }

        $student = Student::create($data);
        AuditLog::log('create', 'Student', $student->id, null, $data);
        return redirect()->route('principal.students.show', $student)
            ->with('success', 'Student admitted successfully.');
    }

    public function show(Student $student)
    {
        $student->load([
            'class', 'subjectGroup',
            'attendance' => fn($q) => $q->latest('attendance_date')->with('subject'),
            'results.subject',
            'behaviourRecords.recordedBy',
            'disciplineRecords',
            'documents',
        ]);

        // Calculate summary from all attendance
        $allAttendance = $student->attendance->all();
        $attendanceSummary = [
            'total'   => count($allAttendance),
            'present' => collect($allAttendance)->where('status', 'P')->count(),
            'absent'  => collect($allAttendance)->where('status', 'A')->count(),
            'leave'   => collect($allAttendance)->where('status', 'L')->count(),
        ];
        $attendanceSummary['rate'] = $attendanceSummary['total'] > 0
            ? round(($attendanceSummary['present'] / $attendanceSummary['total']) * 100, 1)
            : 0;

        // Fetch current month's attendance for calendar
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $monthAttendance = collect($allAttendance)
            ->filter(fn($a) => \Carbon\Carbon::parse($a->attendance_date)->between($monthStart, $monthEnd))
            ->keyBy('attendance_date')
            ->map(fn($a) => ['status' => $a->status, 'remarks' => $a->remarks ?? null]);

        // Comprehensive attendance report
        $attendanceReport = collect($allAttendance)->map(fn($a) => [
            'id'               => $a->id,
            'attendance_date'  => $a->attendance_date,
            'status'           => $a->status,
            'remarks'          => $a->remarks,
            'subject_name'     => $a->subject?->subject_name ?? 'General',
            'subject_id'       => $a->subject_id,
            'month'            => \Carbon\Carbon::parse($a->attendance_date)->format('Y-m'),
            'year'             => \Carbon\Carbon::parse($a->attendance_date)->format('Y'),
        ])->sortByDesc('attendance_date')->values();

        // Subject-wise summary
        $subjectWiseSummary = collect($allAttendance)
            ->groupBy('subject_id')
            ->map(function($records) {
                $subject = $records->first()->subject;
                $total = $records->count();
                $present = $records->where('status', 'P')->count();
                return [
                    'subject_id'   => $records->first()->subject_id,
                    'subject_name' => $subject?->subject_name ?? 'General',
                    'total'        => $total,
                    'present'      => $present,
                    'absent'       => $records->where('status', 'A')->count(),
                    'leave'        => $records->where('status', 'L')->count(),
                    'rate'         => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                ];
            })->values();

        // Month-wise summary
        $monthWiseSummary = collect($allAttendance)
            ->groupBy(fn($a) => \Carbon\Carbon::parse($a->attendance_date)->format('Y-m'))
            ->map(function($records, $month) {
                $total = $records->count();
                $present = $records->where('status', 'P')->count();
                return [
                    'month'        => $month,
                    'month_label'  => \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M Y'),
                    'total'        => $total,
                    'present'      => $present,
                    'absent'       => $records->where('status', 'A')->count(),
                    'leave'        => $records->where('status', 'L')->count(),
                    'rate'         => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                ];
            })->sortByDesc('month')->values();

        return Inertia::render('Principal/Students/Show', compact(
            'student', 'attendanceSummary', 'monthAttendance',
            'attendanceReport', 'subjectWiseSummary', 'monthWiseSummary'
        ));
    }

    public function edit(Student $student)
    {
        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);
        $groups  = SubjectGroup::where('is_active', true)->get(['id', 'group_name', 'stream']);
        return Inertia::render('Principal/Students/Edit', compact('student', 'classes', 'groups'));
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'full_name'         => 'required|string|max:255',
            'student_cnic'      => 'nullable|string|max:20',
            'father_name'       => 'nullable|string|max:255',
            'father_cnic'       => 'nullable|string|max:20',
            'mother_name'       => 'nullable|string|max:255',
            'mother_cnic'       => 'nullable|string|max:20',
            'guardian_name'     => 'nullable|string|max:255',
            'guardian_relation' => 'nullable|string|max:100',
            'guardian_phone'    => 'nullable|string|max:20',
            'guardian_cnic'     => 'nullable|string|max:20',
            'guardian_address'  => 'nullable|string',
            'dob'               => 'nullable|date',
            'gender'            => 'required|in:male,female,other',
            'class_id'          => 'required|exists:classes,id',
            'subject_group_id'  => 'nullable|exists:subject_groups,id',
            'group_stream'      => 'nullable|string|max:100',
            'semester'          => 'nullable|string|max:50',
            'phone'             => 'nullable|string|max:20',
            'email'             => 'nullable|email|max:255',
            'blood_group'       => 'nullable|string|max:5',
            'join_date_kort'    => 'nullable|date',
            'is_orphan'         => 'boolean',
            'trust_notes'       => 'nullable|string',
            'previous_school'   => 'nullable|string|max:255',
            'favorite_color'    => 'nullable|string|max:50',
            'favorite_food'     => 'nullable|string|max:100',
            'favorite_subject'  => 'nullable|string|max:100',
            'ambition'          => 'nullable|string|max:255',
            'reason_left_kort'  => 'nullable|string',
            'leaving_date'      => 'nullable|date',
        ]);

        $oldValues = $student->getAttributes();
        $student->update($data);
        AuditLog::log('update', 'Student', $student->id, $oldValues, $data);
        return back()->with('success', 'Student updated successfully.');
    }

    public function pdf(Student $student)
    {
        $student->load(['class', 'results.subject', 'attendance']);

        $attendanceSummary = null;
        if ($student->attendance && count($student->attendance) > 0) {
            $attendanceSummary = [
                'total' => $student->attendance->count(),
                'present' => $student->attendance->where('status', 'P')->count(),
                'absent' => $student->attendance->where('status', 'A')->count(),
                'leave' => $student->attendance->where('status', 'L')->count(),
                'rate' => round(($student->attendance->where('status', 'P')->count() / $student->attendance->count()) * 100, 1),
            ];
        }

        $pdf = Pdf::loadView('pdf.student-profile', [
            'student' => $student,
            'attendanceSummary' => $attendanceSummary,
        ]);

        return $pdf->download('student-' . $student->admission_no . '-profile.pdf');
    }

    public function destroy(Student $student)
    {
        $name = $student->full_name;
        $oldValues = $student->getAttributes();
        $student->delete();
        AuditLog::log('delete', 'Student', $student->id, $oldValues, null);

        return redirect()->route('principal.students.index')
            ->with('success', "Student '{$name}' has been removed.");
    }

    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:students,id',
            'subject_group_id' => 'required|integer|exists:subject_groups,id',
        ]);

        $count = Student::whereIn('id', $validated['student_ids'])
            ->update(['subject_group_id' => $validated['subject_group_id']]);

        // Log audit for each student
        foreach ($validated['student_ids'] as $studentId) {
            AuditLog::log('bulk_assign_group', 'Student', $studentId, null, [
                'subject_group_id' => $validated['subject_group_id']
            ]);
        }

        return back()->with('success', "Stream/Group assigned to {$count} student" . ($count !== 1 ? 's' : '') . '.');
    }
}
