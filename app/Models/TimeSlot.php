<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    protected $fillable = ['name', 'start_time', 'end_time', 'duration_minutes', 'period_number', 'slot_type', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function entries() { return $this->hasMany(TimetableEntry::class); }
    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeOrdered($q) { return $q->orderBy('period_number'); }
}
