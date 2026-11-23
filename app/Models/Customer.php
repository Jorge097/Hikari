<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'phone', 'notes', 'rfc', 'tax_name', 'tax_regime', 'tax_zip_code', 'cfdi_use'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
