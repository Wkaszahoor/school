<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InboxMessage extends Model
{
    protected $table = 'inbox_messages';

    protected $fillable = [
        'sender_id',
        'sender_role',
        'recipient_id',
        'recipient_role',
        'subject',
        'message_body',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
