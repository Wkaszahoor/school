<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoticeRead extends Model
{
    protected $fillable = ['notice_id', 'user_id', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];
}
