<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\Category; 

Route::get('/', function () {
    
    // 1. Productos Destacados 
    $featuredProducts = Product::where('is_seasonal', true)
        ->where('is_active', true) 
        ->take(4)
        ->get();

    // 2. Categorías Activas con Imágenes de sus productos
    $categories = Category::where('is_active', true) // Solo categorías con switch verde
        ->with(['products' => function ($query) {
            $query->where('is_active', true) 
                  ->whereNotNull('image')    
                  ->limit(4)                 
                  ->select('id', 'category_id', 'image'); 
        }])
        ->get();

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'products' => $featuredProducts, 
        'categories' => $categories, 
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';