<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {   
        // Esta tabla se usara para las epocas del año
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Epoca del Año
            $table->string('slug')->unique(); // Para la URL "Hikari.com/Halloween/--Navidad..."
            $table->boolean('is_active')->default(true); // Esta Activa la Temporada? Si/No
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
