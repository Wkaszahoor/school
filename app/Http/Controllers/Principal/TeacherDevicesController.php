<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\TeacherDevice;
use App\Models\User;
use Illuminate\Http\Request;

class TeacherDevicesController extends Controller
{
    public function store(Request $request, User $teacher)
    {
        $data = $request->validate([
            'device_type' => 'required|in:laptop,chromebook,tablet',
            'serial_number' => 'required|string|unique:teacher_devices',
            'model' => 'required|string|max:255',
            'made_year' => 'required|integer|min:2000|max:' . now()->year,
            'assigned_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $data['teacher_id'] = $teacher->id;

        TeacherDevice::create($data);

        return back()->with('success', 'Device assigned successfully');
    }

    public function update(Request $request, TeacherDevice $device)
    {
        $data = $request->validate([
            'device_type' => 'sometimes|in:laptop,chromebook,tablet',
            'serial_number' => 'sometimes|string|unique:teacher_devices,serial_number,' . $device->id,
            'model' => 'sometimes|string|max:255',
            'made_year' => 'sometimes|integer|min:2000|max:' . now()->year,
            'assigned_at' => 'sometimes|date',
            'unassigned_at' => 'nullable|date|after_or_equal:assigned_at',
            'notes' => 'nullable|string',
        ]);

        $device->update(array_filter($data, fn($value) => $value !== null));

        return back()->with('success', 'Device updated successfully');
    }

    public function destroy(TeacherDevice $device)
    {
        $device->delete();

        return back()->with('success', 'Device unassigned successfully');
    }
}
