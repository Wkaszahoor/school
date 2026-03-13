<?php

namespace App\Http\Middleware;

use App\Models\InboxMessage;
use App\Models\Notice;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'csrf_token' => $request->session()->token(),
            'auth' => [
                'user' => $request->user() ? [
                    'id'         => $request->user()->id,
                    'name'       => $request->user()->name,
                    'email'      => $request->user()->email,
                    'role'       => $request->user()->role,
                    'role_label' => $request->user()->role_label,
                    'avatar'     => $request->user()->avatar,
                ] : null,
            ],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error'   => fn() => $request->session()->get('error'),
                'warning' => fn() => $request->session()->get('warning'),
            ],
            'school' => [
                'name' => config('school.name'),
                'url'  => config('school.url'),
            ],
            'unread_notices_count' => $this->getUnreadNoticesCount($request),
            'unread_messages_count' => $this->getUnreadMessagesCount($request),
        ]);
    }

    private function getUnreadNoticesCount(Request $request): int
    {
        if (!$request->user()) {
            return 0;
        }

        $user = $request->user();

        return Notice::where('is_active', true)
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
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))
            ->count();
    }

    private function getUnreadMessagesCount(Request $request): int
    {
        if (!$request->user()) {
            return 0;
        }

        return InboxMessage::where('recipient_id', $request->user()->id)
            ->where('is_read', false)
            ->count();
    }
}
