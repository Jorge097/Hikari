<?php

namespace Tests\Feature\Panel;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\Products\Pages\EditProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SpanishPanelTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $category = Category::create(['name' => 'Siempre', 'slug' => 'siempre', 'description' => '-', 'is_active' => true]);

        return Product::create([
            'category_id' => $category->id, 'name' => 'Rosa', 'slug' => 'rosa', 'description' => '-',
            'price' => 390, 'is_active' => true, 'is_seasonal' => true,
        ]);
    }

    public function test_validacion_y_menu_en_espanol(): void
    {
        $this->assertSame('El campo nombre es obligatorio.', __('validation.required', ['attribute' => 'nombre']));

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get('/panel_hikari')
            ->assertOk()
            ->assertSee('Catálogo')
            ->assertSee('Productos')
            ->assertSee('Aromas')
            ->assertSee('Tamaños')
            ->assertDontSee('>Products<', false);
    }

    public function test_productos_en_mxn(): void
    {
        $this->product();

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get('/panel_hikari/products')
            ->assertOk()
            ->assertSee('$390.00', false)
            ->assertDontSee('US$')->assertDontSee('390,00');
    }

    // "Activo" apaga el producto en la tienda; "Destacado" es otra cosa
    public function test_activo_y_destacado_son_campos_distintos(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/rosa.jpg', 'x');
        $product = $this->product();
        $product->update(['image' => 'products/rosa.jpg']);
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertFalse($product->is_active);
        $this->assertTrue($product->is_seasonal);
    }
}
