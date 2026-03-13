<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetableConflict extends Model
{
    protected $fillable = ['timetable_id', 'entry_id', 'conflict_type', 'severity', 'description', 'affected_entries', 'is_resolved', 'resolution_notes'];
    protected $casts = ['affected_entries' => 'json', 'is_resolved' => 'boolean'];

    public function timetable() { return $this->belongsTo(Timetable::class); }
    public function entry() { return $this->belongsTo(TimetableEntry::class); }

    public function scopeUnresolved($q) { return $q->where('is_resolved', false); }
    public function scopeByType($q, $type) { return $q->where('conflict_type', $type); }
}
