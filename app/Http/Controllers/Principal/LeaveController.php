<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveSetting;
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
        $request->validate([
            'remarks' => 'nullable|string|max:500',
            'is_paid' => 'nullable|boolean',
            'approved_days' => 'nullable|integer|min:1',
        ]);

        $leave->update([
            'status'      => 'Approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks'     => $request->remarks,
            'is_paid'     => $request->is_paid,
            'approved_days' => $request->approved_days,
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

    public function calendar(Request $request)
    {
        $academicYear = $request->academic_year ?? now()->format('Y') . '-' . (now()->format('Y') + 1);

        $leaves = LeaveRequest::with(['teacher:id,name,email'])
            ->where('request_type', 'teacher')
            ->get();

        $settings = LeaveSetting::where('academic_year', $academicYear)->get();

        $academicYears = LeaveRequest::distinct()
            ->orderBy('from_date', 'desc')
            ->pluck('from_date')
            ->map(fn($date) => \Carbon\Carbon::parse($date)->format('Y') . '-' . (\Carbon\Carbon::parse($date)->format('Y') + 1))
            ->unique()
            ->values()
            ->toArray();

        return Inertia::render('Principal/Leave/Calendar', [
            'leaves' => $leaves,
            'settings' => $settings,
            'academicYear' => $academicYear,
            'academicYears' => $academicYears,
        ]);
    }

    public function settingsIndex(Request $request)
    {
        $academicYear = $request->academic_year ?? now()->format('Y') . '-' . (now()->format('Y') + 1);

        $settings = LeaveSetting::where('academic_year', $academicYear)->get();

        $academicYears = LeaveSetting::distinct()
            ->orderBy('academic_year', 'desc')
            ->pluck('academic_year')
            ->toArray();

        if (empty($academicYears)) {
            $currentYear = now()->year;
            $academicYears = [(string)$currentYear . '-' . ($currentYear + 1)];
        }

        return Inertia::render('Principal/Leave/Settings', [
            'settings' => $settings,
            'academicYear' => $academicYear,
            'academicYears' => $academicYears,
        ]);
    }

    public function settingsStore(Request $request)
    {
        $request->validate([
            'academic_year' => 'required|string',
            'settings' => 'required|array',
            'settings.*.leave_type' => 'required|in:casual,annual,emergency,paid_total',
            'settings.*.max_days' => 'required|integer|min:1',
            'settings.*.is_paid' => 'required|boolean',
        ]);

        foreach ($request->settings as $setting) {
            LeaveSetting::updateOrCreate(
                [
                    'academic_year' => $request->academic_year,
                    'leave_type' => $setting['leave_type'],
                ],
                [
                    'max_days' => $setting['max_days'],
                    'is_paid' => $setting['is_paid'],
                    'created_by' => auth()->id(),
                ]
            );
        }

        return back()->with('success', 'Leave settings updated successfully.');
    }
}
