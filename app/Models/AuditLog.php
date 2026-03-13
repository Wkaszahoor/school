<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'user_role', 'user_name', 'action', 'resource',
        'reference_id', 'old_value', 'new_value', 'ip_address',
    ];

    public $timestamps = false;

    protected $dates = ['created_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Static helper to log an audit entry
     */
    public static function log($action, $resource, $referenceId = null, $oldValue = null, $newValue = null)
    {
        $user = auth()->user();

        return static::create([
            'user_id' => $user?->id,
            'user_role' => $user?->role,
            'user_name' => $user?->name,
            'action' => $action,
            'resource' => $resource,
            'reference_id' => (string) $referenceId,
            'old_value' => is_array($oldValue) ? json_encode($oldValue) : $oldValue,
            'new_value' => is_array($newValue) ? json_encode($newValue) : $newValue,
            'ip_address' => request()->ip(),
        ]);
    }
}
