<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ProfileController;

/* PÁGINAS PÚBLICAS */

Route::controller(PublicController::class)->group(function () {

    Route::get('/', 'home');

    Route::get('/producto/{slug}', 'productDetail')
        ->name('products.show');

    Route::get('/categorias', 'categorias')
        ->name('categorias');

    Route::get('/velas', 'velas')
        ->name('velas');

    Route::get('/pedido', 'cart')
        ->name('cart');

    Route::get('/Conocenos',  'conocenos')
        ->name('conocenos');

});


/* USUARIO AUTENTICADO */

Route::get('/dashboard', [PublicController::class, 'dashboard'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

});



require __DIR__ . '/auth.php';