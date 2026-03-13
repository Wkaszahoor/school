<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Student, User, SchoolClass, TeacherProfile, Attendance, Result, LeaveRequest, Notice, AuditLog};
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_students'  => Student::where('is_active', true)->count(),
            'total_teachers'  => TeacherProfile::where('is_active', true)->count(),
            'total_classes'   => SchoolClass::where('is_active', true)->count(),
            'total_users'     => User::where('is_active', true)->count(),
            'pending_leaves'  => LeaveRequest::where('status', 'pending')->count(),
            'today_present'   => Attendance::whereDate('attendance_date', today())->where('status', 'P')->count(),
            'today_absent'    => Attendance::whereDate('attendance_date', today())->where('status', 'A')->count(),
        ];

        $recentStudents = Student::with('class')
            ->where('is_active', true)
            ->latest()
            ->take(5)
            ->get(['id', 'full_name', 'admission_no', 'class_id', 'created_at']);

        $recentLogs = AuditLog::with('user')
            ->latest('created_at')
            ->take(10)
            ->get();

        $attendanceChart = $this->getWeeklyAttendance();
        $roleDistribution = User::groupBy('role')
            ->selectRaw('role, count(*) as count')
            ->pluck('count', 'role');

        return Inertia::render('Admin/Dashboard', compact(
            'stats', 'recentStudents', 'recentLogs',
            'attendanceChart', 'roleDistribution'
        ));
    }

    private function getWeeklyAttendance(): array
    {
        $days = collect(range(6, 0))->map(fn($i) => now()->subDays($i)->toDateString());

        return $days->map(function ($date) {
            $present = Attendance::whereDate('attendance_date', $date)->where('status', 'P')->count();
            $absent  = Attendance::whereDate('attendance_date', $date)->where('status', 'A')->count();
            return ['date' => $date, 'present' => $present, 'absent' => $absent];
        })->toArray();
    }
}
