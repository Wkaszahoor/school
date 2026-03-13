<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HolidayType extends Model
{
    protected $fillable = ['name', 'color', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function holidays()
    {
        return $this->hasMany(Holiday::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
