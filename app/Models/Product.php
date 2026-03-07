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


    public function sizes()
    {
        return $this->belongsToMany(Size::class)->withTimestamps();
    }

    public function isImperial()
    {
        return $this->category && $this->category->name === 'IsImperial';
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