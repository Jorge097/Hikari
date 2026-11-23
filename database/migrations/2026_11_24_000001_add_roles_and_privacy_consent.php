<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // null = sin acceso al panel
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->nullable()->after('email');
        });

        // Evidencia de aceptación del aviso de privacidad
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('privacy_accepted_at')->nullable()->after('notes');
            $table->string('privacy_version', 20)->nullable()->after('privacy_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['privacy_accepted_at', 'privacy_version']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
