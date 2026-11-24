<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'street', 'exterior_number', 'interior_number', 'neighborhood',
        'city', 'state', 'postal_code', 'references', 'phone', 'is_active', 'is_main',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_main' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Solo una principal
        static::saved(function (Branch $branch) {
            if ($branch->is_main) {
                static::query()->whereKeyNot($branch->getKey())->where('is_main', true)->update(['is_main' => false]);
            }
        });
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public static function main(): ?self
    {
        return static::query()->where('is_main', true)->where('is_active', true)->first();
    }

    // Motivo por el que no se puede borrar, o null
    public function deletionBlocker(): ?string
    {
        if ($this->stocks()->where('quantity', '>', 0)->exists()) {
            return 'Tiene productos con existencias. Pásalas a 0 o desactiva la sucursal.';
        }

        return null;
    }

    public function fullAddress(): string
    {
        $number = trim(($this->exterior_number ?? '').($this->interior_number ? ' int. '.$this->interior_number : ''));

        return collect([trim($this->street.' '.$number), $this->neighborhood, $this->city, $this->state, 'C.P. '.$this->postal_code])
            ->filter()
            ->implode(', ');
    }
}
