<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\{Student, SchoolClass};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_students'   => Student::where('is_active', true)->count(),
            'new_this_month'   => Student::whereMonth('created_at', now()->month)->count(),
            'total_classes'    => SchoolClass::where('is_active', true)->count(),
        ];

        $recentStudents = Student::with('class')
            ->where('is_active', true)
            ->latest()
            ->take(10)
            ->get(['id', 'full_name', 'admission_no', 'class_id', 'gender', 'created_at']);

        return Inertia::render('Receptionist/Dashboard', compact('stats', 'recentStudents'));
    }

    public function students(Request $request)
    {
        $students = Student::with(['class'])
            ->when($request->search, fn($q) =>
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('admission_no', 'like', "%{$request->search}%"))
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->where('is_active', true)
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);

        return Inertia::render('Receptionist/Students', compact('students', 'classes'));
    }

    public function createStudent()
    {
        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);
        $nextNo  = 'SCH' . str_pad(Student::max('id') + 1, 4, '0', STR_PAD_LEFT);
        return Inertia::render('Receptionist/CreateStudent', compact('classes', 'nextNo'));
    }

    public function storeStudent(Request $request)
    {
        $data = $request->validate([
            'admission_no'     => 'required|unique:students',
            'full_name'        => 'required|string|max:120',
            'student_cnic'     => 'nullable|string|max:20',
            'dob'              => 'required|date',
            'gender'           => 'required|in:male,female,other',
            'phone'            => 'nullable|string|max:30',
            'email'            => 'nullable|email|max:120',
            'class_id'         => 'required|exists:classes,id',
            'group_stream'     => 'nullable|in:pre_medical,pre_engineering,computer_science,arts,general',
            'semester'         => 'nullable|string|max:20',
            'join_date_kort'   => 'nullable|date',
            'father_name'      => 'nullable|string|max:120',
            'father_cnic'      => 'nullable|string|max:20',
            'mother_name'      => 'nullable|string|max:120',
            'mother_cnic'      => 'nullable|string|max:20',
            'guardian_name'    => 'nullable|string|max:120',
            'guardian_cnic'    => 'nullable|string|max:20',
            'guardian_phone'   => 'nullable|string|max:30',
            'guardian_address' => 'nullable|string',
            'blood_group'      => 'nullable|string|max:5',
            'favorite_color'   => 'nullable|string|max:50',
            'favorite_food'    => 'nullable|string|max:100',
            'favorite_subject' => 'nullable|string|max:100',
            'ambition'         => 'nullable|string',
            'is_orphan'        => 'boolean',
            'trust_notes'      => 'nullable|string',
            'previous_school'  => 'nullable|string',
            'is_active'        => 'boolean',
            'reason_left_kort' => 'nullable|string|max:255',
            'leaving_date'     => 'nullable|date',
        ]);

        Student::create($data);
        return redirect()->route('receptionist.students')
            ->with('success', 'Student registered successfully.');
    }
}
