<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit()
    {
        $teacher = auth()->user();

        return Inertia::render('Teacher/Profile/Edit', [
            'user' => $teacher,
        ]);
    }

    public function updateName(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        auth()->user()->update(['name' => $validated['name']]);

        return back()->with('success', 'Name updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Check if current password is correct
        if (!Hash::check($validated['current_password'], auth()->user()->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        auth()->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password changed successfully.');
    }
}
