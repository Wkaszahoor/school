<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLedger extends Model
{
    protected $fillable = [
        'item_id', 'transaction_type', 'quantity', 'balance_after',
        'reference_type', 'reference_id', 'notes', 'created_by',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
