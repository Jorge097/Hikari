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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // Relacion -> Festividad
            $table->foreignId('category_id')->constrained()->onDelete('cascade');

            $table->string('name');   //Nombre de la vela
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 8, 2);
            $table->integer('stock');

            // Características específicas de las velas
            $table->string('aroma')->nullable(); 
            $table->string('size')->nullable();  
            $table->string('presentation')->nullable(); 

            $table->boolean('is_seasonal')->default(false); // Es de la temporada? Si/No
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
