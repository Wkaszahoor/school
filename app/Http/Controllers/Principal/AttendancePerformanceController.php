<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{Attendance, SchoolClass};
use Inertia\Inertia;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendancePerformanceController extends Controller
{
    private $allowedClasses = ['Nursery', 'Prep', '1', '2', '3', '4', '5', '6', '7', '8', '9a', '9b', '10', '1st year', '2nd year'];

    public function index(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : now()->subDays(30);
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : now();
        $selectedClass = $request->input('class', '');

        // Get all allowed classes
        $allClasses = SchoolClass::where('is_active', true)
            ->whereIn('class', $this->allowedClasses)
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'name' => $c->class . ($c->section ? " - {$c->section}" : '')])
            ->toArray();

        // Get classwise attendance performance
        $classPerformance = $this->getClassPerformance($startDate, $endDate, $selectedClass);

        // Get attendance trend over date range
        $attendanceTrend = $this->getAttendanceTrend($startDate, $endDate, $selectedClass);

        // Get daily breakdown
        $dailyBreakdown = $this->getDailyBreakdown($startDate, $endDate, $selectedClass);

        return Inertia::render('Principal/AttendancePerformance', compact(
            'classPerformance', 'attendanceTrend', 'dailyBreakdown', 'allClasses', 'startDate', 'endDate', 'selectedClass'
        ));
    }

    private function getClassPerformance($startDate, $endDate, $selectedClass): array
    {
        $query = SchoolClass::where('is_active', true)
            ->whereIn('class', $this->allowedClasses);

        if ($selectedClass) {
            $query->where('id', $selectedClass);
        }

        return $query->get()->map(function ($class) use ($startDate, $endDate) {
            $total = Attendance::where('class_id', $class->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->count();

            $present = Attendance::where('class_id', $class->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->where('status', 'P')
                ->count();

            $absent = Attendance::where('class_id', $class->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->where('status', 'A')
                ->count();

            $leave = Attendance::where('class_id', $class->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->where('status', 'L')
                ->count();

            return [
                'class' => $class->class . ($class->section ? " - {$class->section}" : ''),
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'leave' => $leave,
                'presentRate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                'absentRate' => $total > 0 ? round(($absent / $total) * 100, 1) : 0,
            ];
        })->toArray();
    }

    private function getAttendanceTrend($startDate, $endDate, $selectedClass): array
    {
        $classIds = SchoolClass::where('is_active', true)
            ->whereIn('class', $this->allowedClasses);

        if ($selectedClass) {
            $classIds = $classIds->where('id', $selectedClass);
        }

        $classIds = $classIds->pluck('id');

        $dates = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dates[] = $current->toDateString();
            $current->addDay();
        }

        return array_map(function ($date) use ($classIds) {
            $total = Attendance::whereIn('class_id', $classIds)
                ->whereDate('attendance_date', $date)
                ->count();

            $present = Attendance::whereIn('class_id', $classIds)
                ->whereDate('attendance_date', $date)
                ->where('status', 'P')
                ->count();

            return [
                'date' => $date,
                'total' => $total,
                'present' => $present,
                'rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
            ];
        }, $dates);
    }

    private function getDailyBreakdown($startDate, $endDate, $selectedClass): array
    {
        $classIds = SchoolClass::where('is_active', true)
            ->whereIn('class', $this->allowedClasses);

        if ($selectedClass) {
            $classIds = $classIds->where('id', $selectedClass);
        }

        $classIds = $classIds->pluck('id');

        $records = Attendance::whereIn('class_id', $classIds)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->with('student:id,full_name,admission_no', 'class:id,class,section')
            ->latest('attendance_date')
            ->paginate(50, ['*'], 'page', request('page', 1));

        return [
            'data' => $records->items(),
            'pagination' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
                'from' => $records->firstItem(),
                'to' => $records->lastItem(),
                'links' => $records->getUrlRange(1, $records->lastPage()),
            ],
        ];
    }
}
