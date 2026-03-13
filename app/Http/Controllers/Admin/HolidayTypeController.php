<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HolidayType;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HolidayTypeController extends Controller
{
    public function index(Request $request)
    {
        $types = HolidayType::when($request->search, fn($q) => 
            $q->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%')
        )
        ->when($request->status, fn($q) => 
            $request->status === 'active' ? $q->active() : $q->where('is_active', false)
        )
        ->latest()
        ->paginate(15)
        ->withQueryString();

        return Inertia::render('Admin/Holidays/HolidayTypes/Index', [
            'types' => $types,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:holiday_types',
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $type = HolidayType::create($data);
        AuditLog::log('create', 'HolidayType', $type->id, [], $data);

        return back()->with('success', 'Holiday type created successfully');
    }

    public function update(Request $request, HolidayType $type)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:holiday_types,name,' . $type->id,
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $oldValues = $type->getAttributes();
        $type->update($data);
        AuditLog::log('update', 'HolidayType', $type->id, $oldValues, $data);

        return back()->with('success', 'Holiday type updated successfully');
    }

    public function destroy(HolidayType $type)
    {
        AuditLog::log('delete', 'HolidayType', $type->id, $type->getAttributes(), []);
        $type->delete();

        return back()->with('success', 'Holiday type deleted successfully');
    }
}
