<?php

namespace App\Http\Controllers\Teacher;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveController
{
    public function index(Request $request)
    {
        $leaves = LeaveRequest::where('teacher_id', auth()->id())
            ->where('request_type', 'teacher')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'pending'  => LeaveRequest::where('teacher_id', auth()->id())->where('request_type', 'teacher')->where('status', 'Pending')->count(),
            'approved' => LeaveRequest::where('teacher_id', auth()->id())->where('request_type', 'teacher')->where('status', 'Approved')->count(),
            'rejected' => LeaveRequest::where('teacher_id', auth()->id())->where('request_type', 'teacher')->where('status', 'Rejected')->count(),
        ];

        return Inertia::render('Teacher/Leave/Index', compact('leaves', 'counts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'leave_type'       => 'required|in:casual,annual,emergency,other',
            'other_leave_type' => 'required_if:leave_type,other|nullable|string|max:100',
            'from_date'        => 'required|date|after_or_equal:today',
            'to_date'          => 'required|date|after_or_equal:from_date',
            'reason'           => 'required|string|max:1000',
        ]);

        LeaveRequest::create([
            'request_type'     => 'teacher',
            'teacher_id'       => auth()->id(),
            'status'           => 'Pending',
            'leave_type'       => $request->leave_type,
            'other_leave_type' => $request->leave_type === 'other' ? $request->other_leave_type : null,
            'from_date'        => $request->from_date,
            'to_date'          => $request->to_date,
            'reason'           => $request->reason,
        ]);

        return back()->with('success', 'Leave request submitted successfully.');
    }
}
