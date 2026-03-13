<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\TimeSlot;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TimeSlotsController extends Controller
{
    public function index(Request $request)
    {
        $timeSlots = TimeSlot::orderBy('period_number')->paginate(20);
        return Inertia::render('Principal/Timetables/TimeSlots/Index', compact('timeSlots'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'period_number' => 'required|integer|unique:time_slots',
            'slot_type' => 'required|in:regular,break,lunch,assembly',
            'description' => 'nullable|string',
        ]);

        $start = \Carbon\Carbon::createFromFormat('H:i', $data['start_time']);
        $end = \Carbon\Carbon::createFromFormat('H:i', $data['end_time']);
        $data['duration_minutes'] = $end->diffInMinutes($start);

        $slot = TimeSlot::create($data);
        AuditLog::log('create', 'TimeSlot', $slot->id, [], $data);
        return back()->with('success', 'Time slot created');
    }

    public function update(Request $request, TimeSlot $slot)
    {
        $data = $request->validate(['name' => 'sometimes|string|max:100', 'is_active' => 'sometimes|boolean']);
        $slot->update($data);
        AuditLog::log('update', 'TimeSlot', $slot->id, [], $data);
        return back()->with('success', 'Time slot updated');
    }

    public function destroy(TimeSlot $slot)
    {
        AuditLog::log('delete', 'TimeSlot', $slot->id, $slot->toArray(), []);
        $slot->delete();
        return back()->with('success', 'Time slot deleted');
    }
}
