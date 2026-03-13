<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = [
        'category_id', 'name', 'description', 'unit',
        'current_stock', 'min_stock_level', 'is_active',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'current_stock'   => 'integer',
        'min_stock_level' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function stockEntries()
    {
        return $this->hasMany(InventoryStockEntry::class, 'item_id');
    }

    public function issues()
    {
        return $this->hasMany(InventoryStockIssue::class, 'item_id');
    }

    public function ledger()
    {
        return $this->hasMany(InventoryLedger::class, 'item_id');
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->min_stock_level;
    }
}
