<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{Student, TeacherProfile, SchoolClass, Attendance, Result, LeaveRequest, LessonPlan, DisciplineRecord, Notice};
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_students'      => Student::where('is_active', true)->count(),
            'total_teachers'      => TeacherProfile::where('is_active', true)->count(),
            'total_classes'       => SchoolClass::where('is_active', true)->count(),
            'pending_leaves'      => LeaveRequest::where('status', 'pending')->count(),
            'pending_lesson_plans'=> LessonPlan::where('approval_status', 'pending')->count(),
            'pending_results'     => Result::where('approval_status', 'pending')->count(),
            'discipline_this_month' => DisciplineRecord::whereMonth('created_at', now()->month)->count(),
            'today_attendance_rate' => $this->getTodayAttendanceRate(),
        ];

        $pendingLeaves = LeaveRequest::with(['teacher.user'])
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        $recentDiscipline = DisciplineRecord::with(['student', 'recordedBy'])
            ->latest('incident_date')
            ->take(5)
            ->get();

        $pendingLessonPlans = LessonPlan::with(['teacher.user', 'class', 'subject'])
            ->where('approval_status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        $classAttendance = $this->getClassAttendanceSummary();
        $attendanceTrend = $this->getAttendanceTrend();

        $notices = Notice::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->take(5)
            ->get();

        $allStudents = Student::where('is_active', true)
            ->with('class:id,class,section,academic_year')
            ->orderBy('full_name')
            ->paginate(20, ['id', 'full_name', 'admission_no', 'class_id', 'gender', 'phone', 'dob', 'student_cnic', 'father_name', 'mother_name', 'guardian_name', 'guardian_cnic', 'guardian_address', 'favorite_color', 'favorite_food', 'favorite_subject', 'ambition', 'semester', 'group_stream', 'join_date_kort', 'reason_left_kort']);

        return Inertia::render('Principal/Dashboard', compact(
            'stats', 'pendingLeaves', 'recentDiscipline',
            'pendingLessonPlans', 'classAttendance', 'attendanceTrend', 'notices', 'allStudents'
        ));
    }

    private function getTodayAttendanceRate(): float
    {
        $allowedClasses = ['Nursery', 'Prep', '1', '2', '3', '4', '5', '6', '7', '8', '9a', '9b', '10', '1st year', '2nd year'];
        $classIds = SchoolClass::whereIn('class', $allowedClasses)->pluck('id');
        $total   = Attendance::whereIn('class_id', $classIds)->whereDate('attendance_date', today())->count();
        $present = Attendance::whereIn('class_id', $classIds)->whereDate('attendance_date', today())->where('status', 'P')->count();
        return $total > 0 ? round(($present / $total) * 100, 1) : 0;
    }

    private function getClassAttendanceSummary(): array
    {
        $allowedClasses = ['Nursery', 'Prep', '1', '2', '3', '4', '5', '6', '7', '8', '9a', '9b', '10', '1st year', '2nd year'];
        $classIds = SchoolClass::where('is_active', true)
            ->whereIn('class', $allowedClasses)
            ->pluck('id', 'class');

        // Get all attendance for today in one query
        $todayAttendance = Attendance::whereIn('class_id', $classIds->values())
            ->whereDate('attendance_date', today())
            ->selectRaw('class_id, COUNT(*) as total, SUM(IF(status = "P", 1, 0)) as present')
            ->groupBy('class_id')
            ->get()
            ->keyBy('class_id');

        $classes = SchoolClass::where('is_active', true)
            ->whereIn('class', $allowedClasses)
            ->orderByRaw("FIELD(class, '" . implode("','", $allowedClasses) . "')")
            ->get();

        return $classes->map(function ($class) use ($todayAttendance) {
            $attendance = $todayAttendance->get($class->id);
            $total = $attendance?->total ?? 0;
            $present = $attendance?->present ?? 0;

            return [
                'class'   => $class->class . ($class->section ? " - {$class->section}" : ''),
                'present' => $present,
                'total'   => $total,
                'rate'    => $total > 0 ? round(($present / $total) * 100) : 0,
            ];
        })->toArray();
    }

    private function getAttendanceTrend(): array
    {
        $allowedClasses = ['Nursery', 'Prep', '1', '2', '3', '4', '5', '6', '7', '8', '9a', '9b', '10', '1st year', '2nd year'];
        $classIds = SchoolClass::whereIn('class', $allowedClasses)->pluck('id');
        return collect(range(6, 0))->map(function ($i) use ($classIds) {
            $date    = now()->subDays($i)->toDateString();
            $total   = Attendance::whereIn('class_id', $classIds)->whereDate('attendance_date', $date)->count();
            $present = Attendance::whereIn('class_id', $classIds)->whereDate('attendance_date', $date)->where('status', 'P')->count();
            return [
                'date'    => $date,
                'present' => $present,
                'absent'  => $total - $present,
                'rate'    => $total > 0 ? round(($present / $total) * 100) : 0,
            ];
        })->toArray();
    }
}
