<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Student, SchoolClass, SubjectGroup, AuditLog};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentsController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with(['class', 'subjectGroup'])
            ->when($request->search, fn($q) =>
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('admission_no', 'like', "%{$request->search}%"))
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->when($request->status !== null, fn($q) => $q->where('is_active', $request->status === 'active'));

        $students = $query->latest()->paginate(20)->withQueryString();
        $classes  = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);

        return Inertia::render('Admin/Students/Index', compact('students', 'classes'));
    }

    public function show(Student $student)
    {
        $student->load(['class', 'subjectGroup', 'attendance', 'results.subject', 'documents']);
        return Inertia::render('Admin/Students/Show', compact('student'));
    }

    public function create()
    {
        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);
        return Inertia::render('Admin/Students/Create', compact('classes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name'       => 'required|string|max:120',
            'admission_no'    => 'required|unique:students',
            'student_cnic'    => 'nullable|string|max:20',
            'dob'             => 'required|date',
            'gender'          => 'required|in:male,female,other',
            'class_id'        => 'required|exists:classes,id',
            'group_stream'    => 'nullable|in:pre_medical,pre_engineering,computer_science,arts,general',
            'semester'        => 'nullable|string|max:20',
            'phone'           => 'nullable|string|max:30',
            'email'           => 'nullable|email|max:120',
            'father_name'     => 'nullable|string|max:120',
            'father_cnic'     => 'nullable|string|max:20',
            'mother_name'     => 'nullable|string|max:120',
            'mother_cnic'     => 'nullable|string|max:20',
            'guardian_name'   => 'nullable|string|max:120',
            'guardian_cnic'   => 'nullable|string|max:20',
            'guardian_relation' => 'nullable|string|max:60',
            'guardian_phone'  => 'nullable|string|max:30',
            'guardian_address' => 'nullable|string',
            'blood_group'     => 'nullable|string|max:5',
            'favorite_color'  => 'nullable|string|max:50',
            'favorite_food'   => 'nullable|string|max:100',
            'favorite_subject' => 'nullable|string|max:100',
            'ambition'        => 'nullable|string',
            'is_orphan'       => 'boolean',
            'trust_notes'     => 'nullable|string',
            'previous_school' => 'nullable|string',
            'join_date_kort'  => 'nullable|date',
            'reason_left_kort' => 'nullable|string|max:255',
            'leaving_date'    => 'nullable|date',
        ]);

        $student = Student::create($data);
        AuditLog::log('create', 'Student', $student->id, null, $data);
        return redirect()->route('admin.students.show', $student)->with('success', 'Student created successfully.');
    }

    public function edit(Student $student)
    {
        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);
        return Inertia::render('Admin/Students/Edit', compact('student', 'classes'));
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'full_name'       => 'required|string|max:120',
            'student_cnic'    => 'nullable|string|max:20',
            'dob'             => 'required|date',
            'gender'          => 'required|in:male,female,other',
            'class_id'        => 'required|exists:classes,id',
            'group_stream'    => 'nullable|in:pre_medical,pre_engineering,computer_science,arts,general',
            'semester'        => 'nullable|string|max:20',
            'phone'           => 'nullable|string|max:30',
            'email'           => 'nullable|email|max:120',
            'father_name'     => 'nullable|string|max:120',
            'father_cnic'     => 'nullable|string|max:20',
            'mother_name'     => 'nullable|string|max:120',
            'mother_cnic'     => 'nullable|string|max:20',
            'guardian_name'   => 'nullable|string|max:120',
            'guardian_cnic'   => 'nullable|string|max:20',
            'guardian_relation' => 'nullable|string|max:60',
            'guardian_phone'  => 'nullable|string|max:30',
            'guardian_address' => 'nullable|string',
            'blood_group'     => 'nullable|string|max:5',
            'favorite_color'  => 'nullable|string|max:50',
            'favorite_food'   => 'nullable|string|max:100',
            'favorite_subject' => 'nullable|string|max:100',
            'ambition'        => 'nullable|string',
            'is_orphan'       => 'boolean',
            'trust_notes'     => 'nullable|string',
            'previous_school' => 'nullable|string',
            'join_date_kort'  => 'nullable|date',
            'is_active'       => 'boolean',
            'reason_left_kort' => 'nullable|string|max:255',
            'leaving_date'    => 'nullable|date',
        ]);

        $oldValues = $student->getAttributes();
        $student->update($data);
        AuditLog::log('update', 'Student', $student->id, $oldValues, $data);
        return back()->with('success', 'Student updated successfully.');
    }

    public function destroy(Student $student)
    {
        $student->update(['is_active' => false]);
        return redirect()->route('admin.students.index')->with('success', 'Student deactivated.');
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
}
