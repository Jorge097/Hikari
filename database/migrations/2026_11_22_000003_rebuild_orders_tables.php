<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Reemplaza las tablas de pedidos originales (sin uso y vacías)
    public function up(): void
    {
        if (DB::table('orders')->exists() || DB::table('order_items')->exists()) {
            throw new RuntimeException('orders/order_items tienen datos; migración detenida para no perderlos.');
        }

        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 20)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('origin', 20)->default('web')->index();
            $table->string('status', 20)->default('pendiente')->index();

            // Montos en centavos
            $table->char('currency', 3)->default('MXN');
            $table->decimal('exchange_rate', 12, 6)->nullable();
            $table->decimal('tax_rate', 5, 4);
            $table->boolean('prices_include_tax');
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('shipping_cents')->default(0);
            $table->unsignedBigInteger('tax_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);

            // Envío
            $table->string('shipping_name')->nullable();
            $table->string('shipping_phone', 30)->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_postal_code', 10)->nullable();
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();

            // Pedidos por volumen / eventos
            $table->date('required_date')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            // Copia del producto al momento de la venta
            $table->string('product_name');
            $table->string('scent')->nullable();
            $table->string('size')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedBigInteger('total_cents');
            $table->timestamps();
        });
    }

    // Restaura el esquema original
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('total_amount', 10, 2);
            $table->string('status')->default('pending');
            $table->text('shipping_address');
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }
};
