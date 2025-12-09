<?php

namespace Database\Seeders;

use App\Models\User;     
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Jorge Admin',
            'email' => 'admin@hikari.com',
        ]);

        // 2. Crear Categorías
        $navidad = Category::create([
            'name' => 'Navidad 2025',
            'slug' => 'navidad-2025',
            'is_active' => true
        ]);

        $halloween = Category::create([
            'name' => 'Halloween',
            'slug' => 'halloween',
            'is_active' => false 
        ]);

        $siempre = Category::create([
            'name' => 'Siempre',
            'slug' => 'clasicas',
            'is_active' => true
        ]);

        // 3. Crear Productos (Velas)
        Product::create([
            'category_id' => $navidad->id,
            'name' => 'Vela Pino Nevado',
            'slug' => 'vela-pino-nevado',
            'description' => 'Vela con aroma a bosque invernal ideal para la cena navideña.',
            'price' => 350.00,
            'stock' => 50,
            'aroma' => 'Pino y Roble',
            'size' => '300g',
            'presentation' => 'Frasco Vidrio Rojo',
            'is_seasonal' => true,
            'image' => 'images/velas/vela_pino.jpg'
        ]);

        Product::create([
            'category_id' => $navidad->id,
            'name' => 'Galleta de Jengibre',
            'slug' => 'vela-galleta-jengibre',
            'description' => 'Dulce aroma a galletas recién horneadas.',
            'price' => 280.50,
            'stock' => 20,
            'aroma' => 'Jengibre y Canela',
            'size' => '200g',
            'presentation' => 'Lata Dorada',
            'is_seasonal' => true,
            'image' => 'images/velas/jengibre.jpg'
        ]);

        Product::create([
            'category_id' => $siempre->id,
            'name' => 'Lavanda Relax',
            'slug' => 'vela-lavanda-relax',
            'description' => 'Para momentos de estrés y ansiedad.',
            'price' => 200.00,
            'stock' => 100,
            'aroma' => 'Lavanda Francesa',
            'size' => '150g',
            'presentation' => 'Frasco Transparente',
            'is_seasonal' => true,
            'image' => ''
        ]);
    }
}