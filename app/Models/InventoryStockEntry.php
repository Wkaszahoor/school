<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryStockEntry extends Model
{
    protected $fillable = [
        'item_id', 'quantity', 'type', 'supplier', 'unit_price',
        'total_price', 'received_by', 'entry_date', 'notes',
    ];

    protected $casts = ['entry_date' => 'date'];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
