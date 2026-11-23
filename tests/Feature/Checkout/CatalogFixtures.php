<?php

namespace Tests\Feature\Checkout;

use App\Models\Category;
use App\Models\Product;
use App\Models\Scent;
use App\Models\Size;

trait CatalogFixtures
{
    protected Product $vela;

    protected Product $imperial;

    protected function seedCatalog(): void
    {
        $basicas = Category::create(['name' => 'Pilares', 'slug' => 'Pilares', 'description' => '-', 'is_active' => true]);
        $imperial = Category::create(['name' => 'Luminaria Imperial', 'slug' => 'Luminaria Imperial', 'description' => '-', 'is_active' => true]);

        $this->vela = Product::create([
            'category_id' => $basicas->id, 'name' => 'Vela pilar', 'slug' => 'vela-pilar',
            'description' => '-', 'price' => '290.00', 'size' => '250g', 'is_active' => true,
        ]);
        $this->imperial = Product::create([
            'category_id' => $imperial->id, 'name' => 'Luminaria', 'slug' => 'luminaria',
            'description' => '-', 'price' => '390.00', 'is_active' => true,
        ]);

        Scent::create(['name' => 'Vainilla', 'is_active' => true]);
        Scent::create(['name' => 'Lavanda', 'is_active' => false]);
        Size::create(['name' => 'Chica', 'extra_price' => 0]);
        Size::create(['name' => 'Grande', 'extra_price' => 260]);
    }

    protected function checkoutData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ana López',
            'email' => 'ana@example.com',
            'phone' => '8711234567',
            'delivery' => 'local',
            'privacy_accepted' => true,
            'items' => [
                ['product_id' => $this->vela->id, 'scent' => 'Vainilla', 'size' => '250g', 'quantity' => 2],
                ['product_id' => $this->imperial->id, 'scent' => 'Vainilla', 'size' => 'Grande', 'quantity' => 1],
            ],
        ], $overrides);
    }
}
