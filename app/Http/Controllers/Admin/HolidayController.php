<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\HolidayType;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $holidays = Holiday::with('holidayType')
            ->when($request->search, fn($q) => 
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
            )
            ->when($request->type_id, fn($q) => $q->where('holiday_type_id', $request->type_id))
            ->when($request->academic_year, fn($q) => $q->where('academic_year', $request->academic_year))
            ->when($request->month, fn($q) => $q->byMonth($request->month))
            ->orderBy('holiday_date')
            ->paginate(20)
            ->withQueryString();

        $types = HolidayType::active()->get();
        $currentYear = now()->format('Y');
        $academicYears = Holiday::distinct()
            ->pluck('academic_year')
            ->filter()
            ->sort()
            ->reverse()
            ->values();

        return Inertia::render('Admin/Holidays/Index', [
            'holidays' => $holidays,
            'types' => $types,
            'academicYears' => $academicYears,
            'currentYear' => $currentYear,
            'months' => collect(range(1, 12))->map(fn($m) => [
                'value' => $m,
                'label' => \Carbon\Carbon::createFromFormat('m', $m)->format('F')
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Holidays/Create', [
            'types' => HolidayType::active()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'holiday_date' => 'required|date',
            'holiday_type_id' => 'required|exists:holiday_types,id',
            'description' => 'nullable|string',
            'duration' => 'required|integer|min:1|max:365',
            'academic_year' => 'required|string',
            'is_gazetted' => 'boolean',
        ]);

        $holiday = Holiday::create($data);
        AuditLog::log('create', 'Holiday', $holiday->id, [], $data);

        return redirect()->route('admin.holidays.index')
            ->with('success', 'Holiday created successfully');
    }

    public function edit(Holiday $holiday)
    {
        return Inertia::render('Admin/Holidays/Edit', [
            'holiday' => $holiday->load('holidayType'),
            'types' => HolidayType::active()->get(),
        ]);
    }

    public function update(Request $request, Holiday $holiday)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'holiday_date' => 'required|date',
            'holiday_type_id' => 'required|exists:holiday_types,id',
            'description' => 'nullable|string',
            'duration' => 'required|integer|min:1|max:365',
            'academic_year' => 'required|string',
            'is_gazetted' => 'boolean',
        ]);

        $oldValues = $holiday->getAttributes();
        $holiday->update($data);
        AuditLog::log('update', 'Holiday', $holiday->id, $oldValues, $data);

        return redirect()->route('admin.holidays.index')
            ->with('success', 'Holiday updated successfully');
    }

    public function destroy(Holiday $holiday)
    {
        AuditLog::log('delete', 'Holiday', $holiday->id, $holiday->getAttributes(), []);
        $holiday->delete();

        return back()->with('success', 'Holiday deleted successfully');
    }

    public function calendar(Request $request)
    {
        $year = $request->year ?? now()->year;
        $academicYear = $request->academic_year;

        $holidays = Holiday::when($academicYear, fn($q) => $q->where('academic_year', $academicYear))
            ->whereYear('holiday_date', $year)
            ->with('holidayType')
            ->get();

        $types = HolidayType::active()->get();
        $academicYears = Holiday::distinct()
            ->pluck('academic_year')
            ->filter()
            ->sort()
            ->reverse()
            ->values();

        return Inertia::render('Admin/Holidays/Calendar', [
            'holidays' => $holidays,
            'types' => $types,
            'year' => $year,
            'academicYear' => $academicYear,
            'academicYears' => $academicYears,
        ]);
    }
}
