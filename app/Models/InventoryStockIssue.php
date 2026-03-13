<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryStockIssue extends Model
{
    protected $fillable = [
        'item_id', 'quantity', 'issued_to_type', 'issued_to_id',
        'purpose', 'issued_by', 'issue_date', 'return_date', 'notes',
    ];

    protected $casts = [
        'issue_date'  => 'date',
        'return_date' => 'date',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
