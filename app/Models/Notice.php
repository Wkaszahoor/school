<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $fillable = [
        'title', 'body', 'target_scope', 'target_role', 'target_class_id',
        'target_user_id', 'posted_by', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active'  => 'boolean',
    ];

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by', 'id');
    }

    public function reads()
    {
        return $this->hasMany(NoticeRead::class);
    }

    public function isReadBy(int $userId): bool
    {
        return $this->reads()->where('user_id', $userId)->exists();
    }
}
