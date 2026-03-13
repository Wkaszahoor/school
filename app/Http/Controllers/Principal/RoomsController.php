<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\RoomConfiguration;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoomsController extends Controller
{
    public function index(Request $request)
    {
        $rooms = RoomConfiguration::when($request->search, fn($q) => $q->where('room_name', 'like', '%' . $request->search . '%'))
            ->when($request->room_type, fn($q) => $q->where('room_type', $request->room_type))
            ->paginate(25);
        return Inertia::render('Principal/Timetables/Rooms/Index', compact('rooms'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_name' => 'required|string|max:100|unique:room_configurations',
            'room_type' => 'required|in:classroom,lab,auditorium,sports,art,music,library',
            'capacity' => 'required|integer|min:1',
            'block' => 'nullable|string|max:50',
            'floor' => 'nullable|string|max:50',
            'has_projector' => 'sometimes|boolean',
            'has_lab_equipment' => 'sometimes|boolean',
            'has_ac' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        $room = RoomConfiguration::create($data);
        AuditLog::log('create', 'RoomConfiguration', $room->id, [], $data);
        return back()->with('success', 'Room created');
    }

    public function update(Request $request, RoomConfiguration $room)
    {
        $data = $request->validate(['room_name' => 'sometimes|string|max:100', 'is_active' => 'sometimes|boolean']);
        $room->update($data);
        AuditLog::log('update', 'RoomConfiguration', $room->id, [], $data);
        return back()->with('success', 'Room updated');
    }

    public function destroy(RoomConfiguration $room)
    {
        AuditLog::log('delete', 'RoomConfiguration', $room->id, $room->toArray(), []);
        $room->delete();
        return back()->with('success', 'Room deleted');
    }
}
