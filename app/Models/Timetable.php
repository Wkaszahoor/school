<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timetable extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'created_by', 'academic_year', 'term', 'status',
        'start_date', 'end_date', 'total_days', 'notes', 'conflict_count', 'published_at'
    ];

    protected $casts = ['published_at' => 'datetime'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function entries() { return $this->hasMany(TimetableEntry::class); }
    public function conflicts() { return $this->hasMany(TimetableConflict::class); }

    public function scopeActive($q) { return $q->whereIn('status', ['generated', 'published']); }
    public function scopeByStatus($q, $status) { return $q->where('status', $status); }
}
