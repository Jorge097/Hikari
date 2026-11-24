<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'tracks_stock',
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
        'tracks_stock' => 'boolean',
    ];

    protected $attributes = [
        'tracks_stock' => true,
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    // Suma de existencias en sucursales activas (subconsulta)
    public static function availableStockQuery(): Builder
    {
        return Stock::query()
            ->selectRaw('coalesce(sum(quantity), 0)')
            ->whereColumn('stocks.product_id', 'products.id')
            // La relación incluye borradas; aquí se excluyen
            ->whereHas('branch', fn (Builder $query) => $query->where('is_active', true)->whereNull('deleted_at'));
    }

    public function scopeWithAvailableStock(Builder $query): Builder
    {
        if ($query->getQuery()->columns === null) {
            $query->select('products.*');
        }

        return $query->addSelect(['stock_available' => self::availableStockQuery()]);
    }

    // Lleva inventario y quedan menos del umbral (incluye agotados)
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('tracks_stock', true)
            ->where(self::availableStockQuery(), '<', config('inventory.low_stock_threshold'));
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('tracks_stock', true)->where(self::availableStockQuery(), '<=', 0);
    }

    public function availableStock(): int
    {
        return (int) self::query()->whereKey($this->getKey())->withAvailableStock()->value('stock_available');
    }

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
