<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomConfiguration extends Model
{
    use SoftDeletes;
    protected $fillable = ['room_name', 'room_type', 'capacity', 'block', 'floor', 'has_projector', 'has_lab_equipment', 'has_ac', 'description', 'is_active'];
    protected $casts = ['has_projector' => 'boolean', 'has_lab_equipment' => 'boolean', 'has_ac' => 'boolean', 'is_active' => 'boolean'];

    public function entries() { return $this->hasMany(TimetableEntry::class, 'room_id'); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}
