<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherAvailability extends Model
{
    protected $fillable = ['teacher_id', 'day_of_week', 'time_slot_id', 'availability_type', 'notes', 'max_periods_per_day', 'min_free_periods', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }
    public function timeSlot() { return $this->belongsTo(TimeSlot::class); }

    public function scopeForTeacher($q, $id) { return $q->where('teacher_id', $id); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}
