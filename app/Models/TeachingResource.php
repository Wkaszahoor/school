<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeachingResource extends Model
{
    use SoftDeletes;

    protected $table = 'teaching_resources';

    protected $fillable = [
        'resource_name',
        'description',
        'created_by',
        'subject_id',
        'resource_type',
        'grade_level',
        'file_path',
        'file_url',
        'mime_type',
        'file_size',
        'is_public',
        'is_featured',
        'download_count',
        'average_rating',
        'rating_count',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
        'download_count' => 'integer',
        'rating_count' => 'integer',
        'average_rating' => 'float',
        'tags' => 'json',
        'metadata' => 'json',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function downloads()
    {
        return $this->hasMany(ResourceDownload::class, 'resource_id');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('resource_type', $type);
    }

    public function scopeByGradeLevel($query, $level)
    {
        return $query->where('grade_level', $level);
    }

    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('download_count');
    }

    public function scopeTopRated($query)
    {
        return $query->orderByDesc('average_rating');
    }

    public function scopeRecent($query)
    {
        return $query->latest('created_at');
    }

    public function getFileSizeFormatted()
    {
        $size = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = max($size, 0);
        $pow = floor(($size ? log($size) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $size /= (1 << (10 * $pow));

        return round($size, 2) . ' ' . $units[$pow];
    }

    public function incrementDownloads()
    {
        $this->increment('download_count');
    }

    public function canDownload($user)
    {
        return $this->is_public || $this->created_by === $user->id;
    }
}
