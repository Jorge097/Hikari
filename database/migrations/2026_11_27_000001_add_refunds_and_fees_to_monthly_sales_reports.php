<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reembolsos hechos en el mes y comisiones de Stripe de las ventas del mes
        Schema::table('monthly_sales_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('refunds_mxn_cents')->default(0)->after('total_mxn_cents');
            $table->unsignedBigInteger('fees_mxn_cents')->default(0)->after('refunds_mxn_cents');
        });

        Schema::table('monthly_sales_report_items', function (Blueprint $table) {
            $table->unsignedBigInteger('fee_cents')->nullable()->after('total_mxn_cents');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_sales_report_items', function (Blueprint $table) {
            $table->dropColumn('fee_cents');
        });

        Schema::table('monthly_sales_reports', function (Blueprint $table) {
            $table->dropColumn(['refunds_mxn_cents', 'fees_mxn_cents']);
        });
    }
};
