<?php

namespace App\Mail;

use App\Models\InboxMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public InboxMessage $message;

    public function __construct(InboxMessage $message)
    {
        $this->message = $message;
    }

    public function build()
    {
        return $this->subject("New message from {$this->message->sender->name}")
                    ->view('emails.new-message')
                    ->with([
                        'senderName' => $this->message->sender->name,
                        'messagePreview' => substr($this->message->message_body, 0, 100),
                        'chatUrl' => url(route('chat.show', $this->message->sender_id, false)),
                    ]);
    }
}
