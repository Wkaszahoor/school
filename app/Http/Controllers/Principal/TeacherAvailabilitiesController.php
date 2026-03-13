<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\TeacherAvailability;
use App\Models\User;
use App\Models\TimeSlot;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeacherAvailabilitiesController extends Controller
{
    public function index(Request $request)
    {
        $availabilities = TeacherAvailability::with('teacher', 'timeSlot')
            ->when($request->teacher_id, fn($q) => $q->where('teacher_id', $request->teacher_id))
            ->when($request->day_of_week, fn($q) => $q->where('day_of_week', $request->day_of_week))
            ->paginate(30);

        return Inertia::render('Principal/Timetables/TeacherAvailabilities/Index', [
            'availabilities' => $availabilities,
            'teachers' => User::where('role', 'teacher')->get(['id', 'name']),
            'timeSlots' => TimeSlot::active()->get(['id', 'name', 'period_number']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'time_slot_id' => 'nullable|exists:time_slots,id',
            'availability_type' => 'required|in:available,unavailable,preferred',
            'notes' => 'nullable|string',
            'max_periods_per_day' => 'nullable|integer|min:1|max:10',
            'min_free_periods' => 'nullable|integer|min:0|max:6',
        ]);

        $availability = TeacherAvailability::create($data);
        AuditLog::log('create', 'TeacherAvailability', $availability->id, [], $data);
        return back()->with('success', 'Availability constraint created');
    }

    public function update(Request $request, TeacherAvailability $availability)
    {
        $data = $request->validate([
            'availability_type' => 'sometimes|in:available,unavailable,preferred',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $availability->update($data);
        AuditLog::log('update', 'TeacherAvailability', $availability->id, [], $data);
        return back()->with('success', 'Availability constraint updated');
    }

    public function destroy(TeacherAvailability $availability)
    {
        AuditLog::log('delete', 'TeacherAvailability', $availability->id, $availability->toArray(), []);
        $availability->delete();
        return back()->with('success', 'Availability constraint deleted');
    }
}
