<?php

namespace App\Http\Controllers\Helper;

use App\Http\Controllers\Controller;
use App\Models\{Student, SchoolClass, SubjectGroup};
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_students' => Student::where('is_active', true)->count(),
            'total_classes'  => SchoolClass::where('is_active', true)->count(),
            'unassigned'     => Student::where('is_active', true)->whereNull('subject_group_id')->count(),
        ];

        $classBreakdown = SchoolClass::withCount('students')->where('is_active', true)->get();

        return Inertia::render('Helper/Dashboard', compact('stats', 'classBreakdown'));
    }

    public function students(Request $request)
    {
        $students = Student::with(['class', 'subjectGroup'])
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('admission_no', 'like', "%{$request->search}%"))
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->where('is_active', true)
            ->paginate(25)
            ->withQueryString();

        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);
        $groups  = SubjectGroup::where('is_active', true)->get(['id', 'group_name', 'stream']);

        return Inertia::render('Helper/Students', compact('students', 'classes', 'groups'));
    }

    public function assignGroup(Request $request, Student $student)
    {
        $request->validate(['subject_group_id' => 'nullable|exists:subject_groups,id']);
        $student->update(['subject_group_id' => $request->subject_group_id]);
        return back()->with('success', 'Group assigned successfully.');
    }
}
