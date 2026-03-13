<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{Student, SchoolClass, Attendance};
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with(['class', 'attendance.subject'])
            ->where('is_active', true);

        // Filter by class
        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        // Filter by search (student name or admission number)
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('admission_no', 'like', "%{$request->search}%");
            });
        }

        $students = $query->paginate(25)->withQueryString();

        // Transform students with attendance summary
        $studentsWithAttendance = $students->through(function($student) use ($request) {
            $attendance = $student->attendance;

            // Filter by date range if provided
            if ($request->start_date && $request->end_date) {
                $startDate = \Carbon\Carbon::parse($request->start_date);
                $endDate = \Carbon\Carbon::parse($request->end_date);
                $attendance = $attendance->filter(fn($a) =>
                    \Carbon\Carbon::parse($a->attendance_date)->between($startDate, $endDate)
                );
            }

            $total = $attendance->count();
            $present = $attendance->where('status', 'P')->count();
            $absent = $attendance->where('status', 'A')->count();
            $leave = $attendance->where('status', 'L')->count();
            $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            // Subject-wise breakdown
            $subjectWise = $attendance->groupBy('subject_id')->map(function($records) {
                $subject = $records->first()->subject;
                $subTotal = $records->count();
                $subPresent = $records->where('status', 'P')->count();
                return [
                    'subject_name' => $subject?->subject_name ?? 'General',
                    'total' => $subTotal,
                    'present' => $subPresent,
                    'absent' => $records->where('status', 'A')->count(),
                    'leave' => $records->where('status', 'L')->count(),
                    'rate' => $subTotal > 0 ? round(($subPresent / $subTotal) * 100, 1) : 0,
                ];
            })->values();

            // Attendance calendar data (map by date)
            $calendarData = $attendance->keyBy('attendance_date')->map(fn($a) => [
                'status' => $a->status,
                'subject_name' => $a->subject?->subject_name ?? 'General',
                'remarks' => $a->remarks,
            ]);

            return [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'admission_no' => $student->admission_no,
                'class_name' => $student->class?->name,
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'leave' => $leave,
                'rate' => $rate,
                'subject_wise' => $subjectWise,
                'calendar_data' => $calendarData,
            ];
        });

        // Get classes for filter
        $classes = SchoolClass::where('is_active', true)
            ->get(['id', 'class', 'section'])
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ]);

        return Inertia::render('Principal/Attendance/Report', [
            'students' => $studentsWithAttendance,
            'classes' => $classes,
            'filters' => [
                'search' => $request->get('search'),
                'class_id' => $request->get('class_id'),
                'start_date' => $request->get('start_date'),
                'end_date' => $request->get('end_date'),
            ],
        ]);
    }
}
