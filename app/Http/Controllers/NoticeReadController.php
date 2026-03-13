<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Illuminate\Http\Request;

class NoticeReadController extends Controller
{
    public function myNotices()
    {
        $user = auth()->user();

        $notices = Notice::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) use ($user) {
                $q->where('target_scope', 'all')
                  ->orWhere(function ($q2) use ($user) {
                      $q2->where('target_scope', 'role')->where('target_role', $user->role);
                  })
                  ->orWhere(function ($q2) use ($user) {
                      $q2->where('target_scope', 'teacher')->where('target_user_id', $user->id);
                  });
            })
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', auth()->id()))
            ->with('postedBy')
            ->latest()
            ->take(6)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => substr($n->body, 0, 100) . (strlen($n->body) > 100 ? '...' : ''),
                'created_at' => $n->created_at->format('M d, Y'),
                'posted_by' => $n->postedBy?->name,
            ]);

        return response()->json(['notices' => $notices]);
    }

    public function markRead(Notice $notice)
    {
        $notice->reads()->firstOrCreate(['user_id' => auth()->id()]);

        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        $user = auth()->user();

        $noticeIds = Notice::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) use ($user) {
                $q->where('target_scope', 'all')
                  ->orWhere(function ($q2) use ($user) {
                      $q2->where('target_scope', 'role')->where('target_role', $user->role);
                  })
                  ->orWhere(function ($q2) use ($user) {
                      $q2->where('target_scope', 'teacher')->where('target_user_id', $user->id);
                  });
            })
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', auth()->id()))
            ->take(50)
            ->pluck('id');

        $reads = $noticeIds->map(fn ($id) => ['notice_id' => $id, 'user_id' => auth()->id()]);

        if ($reads->isNotEmpty()) {
            \DB::table('notice_reads')->insertOrIgnore($reads->toArray());
        }

        return response()->json(['success' => true]);
    }
}
