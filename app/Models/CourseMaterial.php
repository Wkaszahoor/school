<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseMaterial extends Model
{
    use SoftDeletes;

    protected $table = 'course_materials';

    protected $fillable = [
        'course_id',
        'material_name',
        'description',
        'material_type',
        'file_path',
        'file_url',
        'mime_type',
        'file_size',
        'sequence_order',
        'is_required',
        'metadata',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_required' => 'boolean',
        'metadata' => 'json',
    ];

    public function course()
    {
        return $this->belongsTo(TrainingCourse::class, 'course_id');
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('material_type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence_order');
    }
}
