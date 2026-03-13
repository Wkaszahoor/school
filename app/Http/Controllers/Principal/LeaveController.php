<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $leaves = LeaveRequest::with(['teacher.user'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Principal/Leave/Index', compact('leaves'));
    }

    public function approve(LeaveRequest $leave)
    {
        $leave->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        return back()->with('success', 'Leave approved.');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $request->validate(['remarks' => 'required|string']);
        $leave->update([
            'status'      => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks'     => $request->remarks,
        ]);
        return back()->with('success', 'Leave rejected.');
    }
}
