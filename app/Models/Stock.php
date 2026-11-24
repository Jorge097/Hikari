<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Existencia de un producto en una sucursal. Cambia solo con StockService.
class Stock extends Model
{
    protected $fillable = ['branch_id', 'product_id', 'quantity'];

    protected $casts = ['quantity' => 'integer'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
