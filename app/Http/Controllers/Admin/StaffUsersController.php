<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{User, AuditLog};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class StaffUsersController extends Controller
{
    public function index(Request $request)
    {
        $users = User::when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/StaffUsers/Index', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role'     => 'required|in:admin,principal,teacher,receptionist,principal_helper,inventory_manager,doctor',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);
        AuditLog::log('create', 'User', $user->id, null, ['name' => $user->name, 'email' => $user->email, 'role' => $user->role]);

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'role'      => 'required|in:admin,principal,teacher,receptionist,principal_helper,inventory_manager,doctor',
            'is_active' => 'boolean',
        ]);

        $user->update($request->only(['name', 'role', 'is_active']));

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:8']);
            $user->update(['password' => Hash::make($request->password)]);
        }

        return back()->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Cannot delete your own account.']);
        }
        $user->update(['is_active' => false]);
        return back()->with('success', 'User deactivated.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate(['password' => 'required|min:8|confirmed']);
        $user->update(['password' => Hash::make($request->password)]);
        AuditLog::log('reset_password', 'User', $user->id, null, ['email' => $user->email]);
        return back()->with('success', 'Password reset successfully.');
    }
}
