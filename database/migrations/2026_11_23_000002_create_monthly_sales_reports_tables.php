<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Snapshot del cierre mensual (uno por mes)
        Schema::create('monthly_sales_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedInteger('orders_count')->default(0);
            // Totales convertidos a MXN, en centavos
            $table->unsignedBigInteger('subtotal_mxn_cents')->default(0);
            $table->unsignedBigInteger('tax_mxn_cents')->default(0);
            $table->unsignedBigInteger('shipping_mxn_cents')->default(0);
            $table->unsignedBigInteger('total_mxn_cents')->default(0);
            $table->string('file_path')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamps();

            $table->unique(['year', 'month']);
        });

        Schema::create('monthly_sales_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_sales_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('folio', 20);
            $table->timestamp('paid_at');
            $table->string('customer_name')->nullable();
            $table->string('rfc', 13)->nullable();
            $table->unsignedBigInteger('subtotal_cents');
            $table->unsignedBigInteger('tax_cents');
            $table->unsignedBigInteger('shipping_cents');
            $table->unsignedBigInteger('total_cents');
            $table->char('currency', 3);
            $table->decimal('exchange_rate', 12, 6)->nullable();
            $table->unsignedBigInteger('total_mxn_cents');
            $table->string('payment_method')->nullable();
            $table->string('status', 20);
            $table->string('origin', 20);
            $table->string('stripe_payment_id')->nullable();
            $table->string('cfdi_uuid', 36)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_sales_report_items');
        Schema::dropIfExists('monthly_sales_reports');
    }
};
