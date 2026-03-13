<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResourceDownload extends Model
{
    protected $table = 'resource_downloads';

    public $timestamps = true;

    protected $fillable = [
        'resource_id',
        'downloaded_by',
        'file_name',
        'ip_address',
        'user_agent',
        'status',
    ];

    public function resource()
    {
        return $this->belongsTo(TeachingResource::class, 'resource_id');
    }

    public function downloadedBy()
    {
        return $this->belongsTo(User::class, 'downloaded_by');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByResource($query, $resourceId)
    {
        return $query->where('resource_id', $resourceId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('downloaded_by', $userId);
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }
}
