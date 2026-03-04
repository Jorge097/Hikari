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
    ];


    public function sizes()
    {
        return $this->belongsToMany(Size::class)->withTimestamps();
    }

    public function isClassic()
    {
        return $this->category && $this->category->name === 'Clásicas';
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