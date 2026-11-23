<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // En Stripe el método se conoce al pagar
            $table->string('method', 20)->nullable()->change();
            $table->string('stripe_payment_intent_id')->nullable()->index()->after('reference');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('confirmation_sent_at')->nullable()->after('cancelled_at');
        });

        // Idempotencia de webhooks
        Schema::create('stripe_events', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('type');
            $table->timestamp('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_events');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('confirmation_sent_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('stripe_payment_intent_id');
            $table->string('method', 20)->nullable(false)->change();
        });
    }
};
