<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\{TeacherProfile, TeacherAssignment, SchoolClass, Attendance, Result, LessonPlan, HomeworkTask, LeaveRequest, TeacherReport};
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $teacher = auth()->user()->teacherProfile;

        if (!$teacher) {
            return Inertia::render('Teacher/Dashboard', ['stats' => [], 'teacher' => null]);
        }

        $teacher->load(['assignments.class', 'assignments.subject']);

        $myClasses = $teacher->assignments->pluck('class')->unique('id');

        // Get primary subject (most frequently assigned subject)
        $primarySubject = $teacher->assignments
            ->where('assignment_type', 'subject_teacher')
            ->pluck('subject')
            ->countBy('id')
            ->sort()
            ->reverse()
            ->keys()
            ->first();

        if ($primarySubject) {
            $teacher->primary_subject = $teacher->assignments
                ->firstWhere('subject_id', $primarySubject)?->subject?->subject_name;
        }

        $stats = [
            'my_classes'        => $myClasses->count(),
            'my_subjects'       => $teacher->assignments->pluck('subject_id')->unique()->count(),
            'pending_plans'     => LessonPlan::where('teacher_id', auth()->id())->where('approval_status', 'pending')->count(),
            'pending_leave'     => LeaveRequest::where('teacher_id', auth()->id())->where('status', 'pending')->count(),
            'today_marked'      => $this->countTodayMarked($teacher),
            'homework_active'   => HomeworkTask::where('teacher_id', auth()->id())->count(),
        ];

        $recentAttendance = Attendance::whereIn('class_id', $myClasses->pluck('id'))
            ->whereDate('attendance_date', today())
            ->with('student')
            ->get();

        $pendingPlans = LessonPlan::where('teacher_id', auth()->id())
            ->with(['class', 'subject'])
            ->where('approval_status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        $recentResults = Result::where('teacher_id', auth()->id())
            ->with(['student', 'subject', 'class'])
            ->latest()
            ->take(5)
            ->get();

        $assignments = $teacher->assignments->load(['class', 'subject', 'group']);

        // Get classes where teacher is assigned as class teacher (from SchoolClass table)
        $classTeacherAssignments = SchoolClass::where('class_teacher_id', auth()->id())
            ->where('is_active', true)
            ->get();

        // Get reports received by this teacher
        $reportsReceived = TeacherReport::where('subject_teacher_id', auth()->id())
            ->with(['classTeacher', 'class'])
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Teacher/Dashboard', compact(
            'stats', 'teacher', 'recentAttendance', 'pendingPlans', 'recentResults', 'assignments', 'classTeacherAssignments', 'reportsReceived'
        ));
    }

    private function countTodayMarked($teacher): int
    {
        $classIds = $teacher->assignments->pluck('class_id')->unique();
        return Attendance::whereIn('class_id', $classIds)
            ->whereDate('attendance_date', today())
            ->where('marked_by', auth()->id())
            ->count();
    }
}
