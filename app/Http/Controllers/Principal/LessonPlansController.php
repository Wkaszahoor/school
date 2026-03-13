<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\LessonPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LessonPlansController extends Controller
{
    public function index(Request $request)
    {
        $plans = LessonPlan::with(['teacher.user', 'class', 'subject'])
            ->when($request->status, fn($q) => $q->where('approval_status', $request->status))
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Principal/LessonPlans/Index', compact('plans'));
    }

    public function show(LessonPlan $lessonPlan)
    {
        $lessonPlan->load(['teacher.user', 'class', 'subject', 'reviewedBy']);
        return Inertia::render('Principal/LessonPlans/Show', compact('lessonPlan'));
    }

    public function approve(Request $request, LessonPlan $lessonPlan)
    {
        $request->validate(['feedback' => 'nullable|string']);
        $lessonPlan->update([
            'approval_status'   => 'approved',
            'principal_comment' => $request->feedback,
            'reviewed_by'       => auth()->id(),
            'reviewed_at'       => now(),
        ]);
        return back()->with('success', 'Lesson plan approved.');
    }

    public function reject(Request $request, LessonPlan $lessonPlan)
    {
        $request->validate(['feedback' => 'required|string']);
        $lessonPlan->update([
            'approval_status'   => 'rejected',
            'principal_comment' => $request->feedback,
            'reviewed_by'       => auth()->id(),
            'reviewed_at'       => now(),
        ]);
        return back()->with('success', 'Lesson plan returned for revision.');
    }
}
