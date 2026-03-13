<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\{LessonPlan, TeacherAssignment};
use Illuminate\Http\Request;
use Inertia\Inertia;

class LessonPlansController extends Controller
{
    public function index()
    {
        $plans = LessonPlan::where('teacher_id', auth()->id())
            ->with(['class', 'subject', 'reviewedBy'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Teacher/LessonPlans/Index', compact('plans'));
    }

    public function create()
    {
        $assignments = TeacherAssignment::where('teacher_id', auth()->id())
            ->with(['class', 'subject'])
            ->get();

        return Inertia::render('Teacher/LessonPlans/Create', compact('assignments'));
    }

    public function store(Request $request)
    {
        $teacher = auth()->user()->teacherProfile;

        $data = $request->validate([
            'class_id'     => 'required|exists:classes,id',
            'subject_id'   => 'required|exists:subjects,id',
            'week_starting'=> 'required|date',
            'topic'        => 'required|string',
            'objectives'   => 'required|string',
            'resources'    => 'nullable|string',
            'activities'   => 'nullable|string',
            'homework'     => 'nullable|string',
        ]);

        $data['teacher_id'] = auth()->id();
        $data['approval_status'] = 'pending';

        LessonPlan::create($data);

        return redirect()->route('teacher.lesson-plans.index')
            ->with('success', 'Lesson plan submitted for review.');
    }

    public function edit(LessonPlan $lessonPlan)
    {
        abort_if($lessonPlan->teacher_id !== auth()->id(), 403);
        abort_if($lessonPlan->approval_status === 'approved', 403, 'Cannot edit an approved plan.');

        $assignments = TeacherAssignment::where('teacher_id', auth()->id())
            ->with(['class', 'subject'])
            ->get();

        return Inertia::render('Teacher/LessonPlans/Edit', compact('lessonPlan', 'assignments'));
    }

    public function update(Request $request, LessonPlan $lessonPlan)
    {
        abort_if($lessonPlan->teacher_id !== auth()->id(), 403);

        $data = $request->validate([
            'topic'      => 'required|string',
            'objectives' => 'required|string',
            'resources'  => 'nullable|string',
            'activities' => 'nullable|string',
            'homework'   => 'nullable|string',
        ]);

        $data['approval_status'] = 'pending';
        $lessonPlan->update($data);

        return back()->with('success', 'Lesson plan updated and resubmitted.');
    }
}
