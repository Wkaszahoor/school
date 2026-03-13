<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicCalendar extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'type',
        'color',
        'academic_year',
        'is_all_day',
        'location',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_all_day' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now()->toDateString())->orderBy('start_date');
    }

    public function scopeCurrentMonth($query)
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        return $query->whereBetween('start_date', [$start, $end]);
    }
}
