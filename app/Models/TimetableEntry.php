<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetableEntry extends Model
{
    protected $table = 'timetable_entries';
    protected $fillable = ['timetable_id', 'class_id', 'subject_id', 'teacher_id', 'room_id', 'time_slot_id', 'day_of_week', 'is_locked', 'notes'];
    protected $casts = ['is_locked' => 'boolean'];

    public function timetable() { return $this->belongsTo(Timetable::class); }
    public function schoolClass() { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }
    public function room() { return $this->belongsTo(RoomConfiguration::class, 'room_id'); }
    public function timeSlot() { return $this->belongsTo(TimeSlot::class); }
}
