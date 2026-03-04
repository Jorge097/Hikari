<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scent extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_active'];

    // Un aroma puede estar en muchos productos
    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
}