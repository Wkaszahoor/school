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
        $leaves = LeaveRequest::with(['teacher:id,name,email'])
            ->where('request_type', 'teacher')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Principal/Leave/Index', compact('leaves'));
    }

    public function approve(Request $request, LeaveRequest $leave)
    {
        $request->validate(['remarks' => 'nullable|string|max:500']);
        $leave->update([
            'status'      => 'Approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks'     => $request->remarks,
        ]);
        return back()->with('success', 'Leave approved.');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $request->validate(['remarks' => 'required|string|max:500']);
        $leave->update([
            'status'      => 'Rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks'     => $request->remarks,
        ]);
        return back()->with('success', 'Leave rejected.');
    }
}
