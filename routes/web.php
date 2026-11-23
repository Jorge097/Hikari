<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

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

    Route::get('/Conocenos', 'conocenos')
        ->name('conocenos');

    Route::get('/aviso-de-privacidad', 'privacy')
        ->name('privacy');

});

/* CHECKOUT Y PAGOS */

Route::controller(CheckoutController::class)->group(function () {
    Route::get('/checkout', 'show')->name('checkout');
    Route::post('/checkout', 'store')->middleware('throttle:checkout')->name('checkout.store');
    Route::get('/pedido/confirmado', 'success')->name('checkout.success');
    Route::get('/pedido/cancelado', 'cancel')->name('checkout.cancel');
});

// Sin CSRF (ver bootstrap/app.php); se valida la firma
Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

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

require __DIR__.'/auth.php';
