<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\Category;

/* PÁGINA DE INICIO (WELCOME) */
Route::get('/', function () {

    $featuredProducts = Product::where('is_seasonal', true)
        ->where('is_active', true)
        ->whereHas('category', function ($query) {
            $query->where('is_active', true); 
        })
        ->take(4)
        ->get();

    // Categorías activas para el carrusel
    $categories = Category::where('is_active', true)
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

/* DETALLE DE PRODUCTO */
Route::get('/producto/{slug}', function ($slug) {

    $product = Product::where('slug', $slug)
        ->where('is_active', true)
        ->whereHas('category', function ($query) {
            $query->where('is_active', true); 
        })
        ->firstOrFail();

    $relatedProducts = Product::where('id', '!=', $product->id) 
        ->where('is_active', true) 
        ->whereHas('category', function ($query) {
            $query->where('is_active', true); 
        })
        ->inRandomOrder() 
        ->take(4)
        ->get();

    return Inertia::render('ProductDetail', [
        'product' => $product,
        'relatedProducts' => $relatedProducts,
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);

})->name('products.show');


/* RUTAS DE USUARIO AUTENTICADO */
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';