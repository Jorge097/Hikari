<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Registro financiero: no se borra con el pedido
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('type', 20)->default('pago');
            $table->string('provider', 20)->default('manual');
            $table->string('method', 20);
            $table->string('status', 20)->default('pendiente')->index();
            $table->unsignedBigInteger('amount_cents');
            $table->char('currency', 3)->default('MXN');
            $table->string('reference')->nullable()->index();
            $table->timestamp('paid_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
