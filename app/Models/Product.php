<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'aroma',
        'size',
        'presentation',
        'is_seasonal',
        'is_active',
        'image',
        'sort',
    ];

    protected $casts = [
        'is_seasonal' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function sizes()
    {
        return $this->belongsToMany(Size::class)->withTimestamps();
    }

    public function isImperial()
    {
        return $this->category && $this->category->name === 'IsImperial';
    }

    // Igual que ProductDetail.jsx: solo esta categoría elige tamaño
    public function hasSizeOptions(): bool
    {
        return $this->category?->slug === 'Luminaria Imperial';
    }

    // Precio en centavos con el extra del tamaño elegido
    public function unitPriceCents(?string $size): int
    {
        $extra = $this->hasSizeOptions()
            ? (float) (Size::where('name', $size)->value('extra_price') ?? 0)
            : 0;

        return (int) round(((float) $this->price + $extra) * 100);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
