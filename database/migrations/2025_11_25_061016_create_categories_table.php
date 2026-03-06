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

            $table->string('name'); // Nombre de la categoria
            $table->string('slug')->unique(); // URL amigable

            $table->text('description');

            $table->boolean('is_active')->default(true); // activa o desactiva categoria

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