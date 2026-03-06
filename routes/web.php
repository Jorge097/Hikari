<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\Category;
use App\Models\Scent;
use App\Models\Size;
use App\Http\Controllers\PublicController;

/* PÁGINA DE INICIO */

Route::get('/', function () {

    $featuredProducts = Product::where('is_seasonal', true)
        ->where('is_active', true)
        ->whereHas('category', function ($query) {
            $query->where('is_active', true);
        })
        ->take(4)
        ->get();

    $categories = Category::where('is_active', true)
        ->with([
            'products' => function ($query) {
                $query->where('is_active', true)
                    ->whereNotNull('image')
                    ->limit(4)
                    ->select('id', 'category_id', 'image');
            }
        ])
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
        ->with(['sizes', 'category'])
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

    // Aromas activos
    $scents = Scent::where('is_active', true)
        ->orderBy('name')
        ->get(['id','name']);

    // Tamaños disponibles
    $sizes = Size::orderBy('name')
        ->get(['id','name','extra_price']);

    return Inertia::render('ProductDetail', [
        'product' => $product,
        'relatedProducts' => $relatedProducts,
        'scents' => $scents,
        'sizes' => $sizes,
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);

})->name('products.show');


/* USUARIO AUTENTICADO */

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});


/* CATEGORÍAS */

Route::get('/categorias', function () {

    $categories = Category::with('products')
        ->where('is_active', true)
        ->get();

    return Inertia::render('Categorias', [
        'categories' => $categories
    ]);

})->name('categorias');


/* CATÁLOGO */

Route::get('/Velas', [PublicController::class, 'velas'])->name('velas');


/* CARRITO*/

Route::get('/pedido', function () {
    return Inertia::render('Cart');
})->name('cart');


require __DIR__ . '/auth.php';