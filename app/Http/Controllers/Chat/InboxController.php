<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Mail\NewMessageMail;
use App\Models\InboxMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class InboxController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        
        $conversations = InboxMessage::where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)->orWhere('recipient_id', $userId);
        })
        ->latest('created_at')
        ->get()
        ->groupBy(function ($msg) use ($userId) {
            return $msg->sender_id === $userId ? $msg->recipient_id : $msg->sender_id;
        })
        ->map(function ($msgs, $otherId) use ($userId) {
            $lastMsg = $msgs->last();
            $otherUser = User::find($otherId);
            $unreadCount = $msgs->where('recipient_id', $userId)->where('is_read', false)->count();

            return [
                'other_user_id' => $otherId,
                'other_user' => [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'email' => $otherUser->email,
                    'role_label' => $otherUser->role_label,
                    'phone' => $this->getUserPhone($otherUser),
                ],
                'last_message' => $lastMsg->message_body,
                'last_message_time' => $lastMsg->created_at,
                'unread_count' => $unreadCount,
            ];
        })
        ->values();

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
        ]);
    }

    public function show($userId)
    {
        $otherUser = User::findOrFail($userId);
        $authId = auth()->id();

        $messages = InboxMessage::where(function ($q) use ($authId, $userId) {
            $q->where('sender_id', $authId)->where('recipient_id', $userId)
              ->orWhere('sender_id', $userId)->where('recipient_id', $authId);
        })
        ->orderBy('created_at', 'asc')
        ->get()
        ->map(function ($msg) {
            return [
                'id' => $msg->id,
                'sender_id' => $msg->sender_id,
                'recipient_id' => $msg->recipient_id,
                'subject' => $msg->subject,
                'message_body' => $msg->message_body,
                'created_at' => $msg->created_at,
                'sender_name' => $msg->sender->name,
                'is_sent' => $msg->sender_id === auth()->id(),
            ];
        });

        // Mark all messages from this user as read
        InboxMessage::where('sender_id', $userId)
            ->where('recipient_id', $authId)
            ->update(['is_read' => true, 'read_at' => now()]);

        return Inertia::render('Chat/Show', [
            'other_user' => [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'email' => $otherUser->email,
                'role_label' => $otherUser->role_label,
                'phone' => $this->getUserPhone($otherUser),
            ],
            'messages' => $messages,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id|different:sender_id',
            'subject' => 'required|string|max:255',
            'message_body' => 'required|string',
        ], [
            'recipient_id.different' => 'You cannot send a message to yourself.',
        ]);

        $recipient = User::findOrFail($validated['recipient_id']);

        $message = InboxMessage::create([
            'sender_id' => auth()->id(),
            'sender_role' => auth()->user()->role,
            'recipient_id' => $recipient->id,
            'recipient_role' => $recipient->role,
            'subject' => $validated['subject'],
            'message_body' => $validated['message_body'],
        ]);

        try {
            Mail::to($recipient->email)->send(new NewMessageMail($message));
        } catch (\Exception $e) {
            \Log::error('Failed to send message email: ' . $e->getMessage());
        }

        return back()->with('success', 'Message sent');
    }

    public function unreadCount()
    {
        $count = InboxMessage::where('recipient_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markRead($userId)
    {
        InboxMessage::where('sender_id', $userId)
            ->where('recipient_id', auth()->id())
            ->update(['is_read' => true, 'read_at' => now()]);

        return back();
    }

    public function users()
    {
        $users = User::where('id', '!=', auth()->id())
            ->select('id', 'name', 'email', 'role')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role_label' => $user->role_label,
                ];
            });

        return response()->json($users);
    }

    private function getUserPhone(User $user): ?string
    {
        if ($user->role === 'teacher' && $user->teacherProfile) {
            return $user->teacherProfile->phone;
        }
        
        return $user->phone ?? null;
    }
}
