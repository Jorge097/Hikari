<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Datos fiscales por defecto del cliente (se reutilizan al facturar)
        Schema::table('customers', function (Blueprint $table) {
            $table->string('rfc', 13)->nullable()->after('phone');
            $table->string('tax_name')->nullable()->after('rfc');
            $table->string('tax_regime', 3)->nullable()->after('tax_name');
            $table->string('tax_zip_code', 5)->nullable()->after('tax_regime');
            $table->string('cfdi_use', 4)->nullable()->after('tax_zip_code');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->char('type', 1)->default('I'); // I ingreso, E egreso
            $table->string('status', 20)->default('solicitada')->index();

            // Receptor (copia al momento de facturar)
            $table->string('receiver_rfc', 13);
            $table->string('receiver_name');
            $table->string('receiver_tax_regime', 3);
            $table->string('receiver_zip_code', 5);
            $table->string('receiver_cfdi_use', 4);
            $table->string('receiver_email')->nullable();

            $table->char('payment_form', 2);
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('tax_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);

            // PAC
            $table->string('provider', 20)->nullable();
            $table->string('provider_id')->nullable();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('xml_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->text('error_message')->nullable();

            // Nota de crédito → factura que corrige
            $table->foreignId('related_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->char('cancel_motive', 2)->nullable();

            $table->timestamp('stamped_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['rfc', 'tax_name', 'tax_regime', 'tax_zip_code', 'cfdi_use']);
        });
    }
};
