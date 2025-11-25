<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'product_id', 'quantity', 'price'
    ];

    // Pertenece a una Orden
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Pertenece a un Producto (para saber qué vela es)
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}