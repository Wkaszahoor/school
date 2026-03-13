<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TodoController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $userRole = auth()->user()->role;

        // Get todos based on role
        $query = Todo::with('assignedTo', 'creator');

        if ($userRole === 'principal' || $userRole === 'admin') {
            // Principals/admins see all todos unless filtering
            if ($request->input('show') !== 'all') {
                $query->where('assigned_to', $userId)->orWhere('created_by', $userId);
            }
        } else {
            // Teachers only see their assigned tasks
            $query->where('assigned_to', $userId);
        }

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $todos = $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'in_progress' THEN 1 WHEN status = 'completed' THEN 2 ELSE 3 END")
                      ->orderBy('due_date', 'asc')
                      ->paginate(15)
                      ->withQueryString();

        // Calculate statistics
        $statsQuery = Todo::query();

        if ($userRole === 'principal' || $userRole === 'admin') {
            if ($request->input('show') !== 'all') {
                $statsQuery->where('assigned_to', $userId)->orWhere('created_by', $userId);
            }
        } else {
            $statsQuery->where('assigned_to', $userId);
        }

        $statistics = [
            'total' => $statsQuery->count(),
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
            'overdue' => (clone $statsQuery)->where('status', '!=', 'completed')
                                           ->where('status', '!=', 'cancelled')
                                           ->where('due_date', '<', now()->format('Y-m-d'))
                                           ->count(),
        ];

        return Inertia::render('Todos/Index', [
            'todos' => $todos,
            'statistics' => $statistics,
            'userRole' => $userRole,
        ]);
    }

    public function create()
    {
        $users = User::where('role', '!=', 'admin')
                    ->orderBy('name')
                    ->get(['id', 'name', 'email']);

        return Inertia::render('Todos/Create', [
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'category' => 'required|in:academic,administrative,event,maintenance,other',
            'assigned_to' => 'required|exists:users,id',
        ]);

        $data['created_by'] = auth()->id();
        $data['status'] = 'pending';

        Todo::create($data);

        return redirect()->route('todos.index')
                       ->with('success', 'Task created successfully');
    }

    public function edit(Todo $todo)
    {
        $users = User::where('role', '!=', 'admin')
                    ->orderBy('name')
                    ->get(['id', 'name', 'email']);

        return Inertia::render('Todos/Edit', [
            'todo' => $todo->load('assignedTo'),
            'users' => $users,
        ]);
    }

    public function update(Request $request, Todo $todo)
    {
        $data = $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'date',
            'priority' => 'in:low,medium,high,urgent',
            'status' => 'in:pending,in_progress,completed,cancelled',
            'category' => 'in:academic,administrative,event,maintenance,other',
        ]);

        if (isset($data['status']) && $data['status'] === 'completed' && $todo->status !== 'completed') {
            $data['completed_at'] = now();
        }

        $todo->update(array_filter($data, fn($value) => $value !== null));

        return redirect()->route('todos.index')
                       ->with('success', 'Task updated successfully');
    }

    public function destroy(Todo $todo)
    {
        $todo->delete();

        return back()->with('success', 'Task deleted successfully');
    }

    public function markComplete(Todo $todo)
    {
        $todo->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Task marked as completed');
    }

    public function pendingCount()
    {
        $userId = auth()->id();
        $userRole = auth()->user()->role;

        $query = Todo::where('status', 'pending');

        if ($userRole === 'principal' || $userRole === 'admin') {
            $query->where('assigned_to', $userId);
        } else {
            $query->where('assigned_to', $userId);
        }

        return response()->json([
            'count' => $query->count(),
        ]);
    }

    public function getPending()
    {
        $userId = auth()->id();
        $userRole = auth()->user()->role;

        $query = Todo::where('status', 'pending')
                    ->with('assignedTo', 'creator')
                    ->orderBy('due_date', 'asc');

        if ($userRole === 'principal' || $userRole === 'admin') {
            $query->where('assigned_to', $userId);
        } else {
            $query->where('assigned_to', $userId);
        }

        $todos = $query->limit(5)->get();

        return response()->json([
            'todos' => $todos->map(fn($todo) => [
                'id' => $todo->id,
                'title' => $todo->title,
                'description' => $todo->description,
                'due_date' => $todo->due_date?->format('M d, Y'),
                'priority' => $todo->priority,
                'status' => $todo->status,
                'category' => $todo->category,
            ]),
        ]);
    }
}
