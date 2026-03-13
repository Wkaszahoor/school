<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogsController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->action, fn($q) => $q->where('action', 'like', "%{$request->action}%"))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Admin/AuditLogs', compact('logs'));
    }
}
