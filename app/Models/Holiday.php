<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'holiday_date',
        'holiday_type_id',
        'description',
        'duration',
        'academic_year',
        'is_gazetted',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'is_gazetted' => 'boolean',
    ];

    public function holidayType()
    {
        return $this->belongsTo(HolidayType::class);
    }

    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopeByMonth($query, $month)
    {
        return $query->whereMonth('holiday_date', $month);
    }

    public function scopeByType($query, $typeId)
    {
        return $query->where('holiday_type_id', $typeId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('holiday_date', '>=', now()->toDateString())->orderBy('holiday_date');
    }

    public function scopePast($query)
    {
        return $query->where('holiday_date', '<', now()->toDateString())->orderByDesc('holiday_date');
    }
}
