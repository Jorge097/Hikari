<?php

namespace Tests\Feature\Inventory;

use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\Branches\Pages\EditBranch;
use App\Filament\Admin\Resources\Branches\Pages\ListBranches;
use App\Filament\Admin\Resources\Branches\RelationManagers\StocksRelationManager;
use App\Filament\Admin\Resources\Products\Pages\ListProducts;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Inventory\InsufficientStockException;
use App\Services\Inventory\StockService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stock = app(StockService::class);
    }

    private function branch(string $name = 'Centro', array $attrs = []): Branch
    {
        return Branch::create(array_merge([
            'name' => $name, 'street' => 'Morelos', 'exterior_number' => '100', 'neighborhood' => 'Centro',
            'city' => 'Torreón', 'state' => 'Coahuila', 'postal_code' => '27000',
        ], $attrs));
    }

    private function product(string $name = 'Rosa', array $attrs = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'siempre'], ['name' => 'Siempre', 'description' => '-', 'is_active' => true]);

        return Product::create(array_merge([
            'category_id' => $category->id, 'name' => $name, 'slug' => str($name)->slug(), 'description' => '-', 'price' => 300,
        ], $attrs));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_entrada_y_salida_dejan_historial(): void
    {
        $branch = $this->branch();
        $product = $this->product();

        $this->stock->move($branch, $product, StockMovementType::Entrada, 10, 'Llegó lote');
        $last = $this->stock->move($branch, $product, StockMovementType::Ajuste, -3, 'Rota');

        $this->assertSame(7, Stock::firstWhere(['branch_id' => $branch->id, 'product_id' => $product->id])->quantity);
        $this->assertSame(7, $last->resulting_stock);
        $this->assertSame([10, -3], StockMovement::orderBy('id')->pluck('quantity')->all());
    }

    public function test_no_permite_stock_negativo_y_no_cambia_nada(): void
    {
        $branch = $this->branch();
        $product = $this->product();
        $this->stock->move($branch, $product, StockMovementType::Entrada, 2);

        try {
            $this->stock->move($branch, $product, StockMovementType::SalidaVenta, -3);
            $this->fail('Debió rechazar la salida');
        } catch (InsufficientStockException $e) {
            $this->assertStringContainsString('tiene 2', $e->getMessage());
        }

        $this->assertSame(2, $product->availableStock());
        $this->assertSame(1, StockMovement::count());
    }

    public function test_conteo_fisico_ajusta_la_diferencia(): void
    {
        $branch = $this->branch();
        $product = $this->product();
        $this->stock->move($branch, $product, StockMovementType::Entrada, 8);

        $movement = $this->stock->setCount($branch, $product, 5);

        $this->assertSame(-3, $movement->quantity);
        $this->assertSame(StockMovementType::Ajuste, $movement->type);
        $this->assertNull($this->stock->setCount($branch, $product, 5));
    }

    public function test_un_producto_una_vez_por_sucursal(): void
    {
        $branch = $this->branch();
        $product = $this->product();
        Stock::create(['branch_id' => $branch->id, 'product_id' => $product->id]);

        $this->expectException(QueryException::class);
        Stock::create(['branch_id' => $branch->id, 'product_id' => $product->id]);
    }

    public function test_solo_una_sucursal_principal(): void
    {
        $a = $this->branch('Centro', ['is_main' => true]);
        $b = $this->branch('Norte', ['is_main' => true]);

        $this->assertFalse($a->refresh()->is_main);
        $this->assertTrue($b->refresh()->is_main);
        $this->assertTrue(Branch::main()->is($b));
    }

    public function test_disponible_suma_solo_sucursales_activas(): void
    {
        $product = $this->product();
        $this->stock->move($this->branch('Centro'), $product, StockMovementType::Entrada, 3);
        $this->stock->move($this->branch('Bodega', ['is_active' => false]), $product, StockMovementType::Entrada, 50);
        $deleted = $this->branch('Cerrada');
        $this->stock->move($deleted, $product, StockMovementType::Entrada, 20);
        $deleted->delete();

        $this->assertSame(3, $product->availableStock());
        $this->assertTrue(Product::lowStock()->whereKey($product)->exists());
        $this->assertFalse(Product::outOfStock()->whereKey($product)->exists());
    }

    public function test_bajo_pedido_no_cuenta_como_stock_bajo(): void
    {
        $this->product('Personalizada', ['tracks_stock' => false]);
        $agotada = $this->product('Agotada');

        $this->assertSame([$agotada->id], Product::lowStock()->pluck('id')->all());
        $this->assertSame([$agotada->id], Product::outOfStock()->pluck('id')->all());
    }

    public function test_lista_de_productos_muestra_stock_y_filtra(): void
    {
        $branch = $this->branch();
        $poco = $this->product('Poco');
        $mucho = $this->product('Mucho');
        $this->product('Bajo', ['tracks_stock' => false]);
        $this->stock->move($branch, $poco, StockMovementType::Entrada, 2);
        $this->stock->move($branch, $mucho, StockMovementType::Entrada, 30);
        $this->actingAs($this->admin());

        Livewire::test(ListProducts::class)
            ->assertSee('Stock disponible')
            ->assertSee('Bajo pedido')
            ->filterTable('low_stock')
            ->assertCanSeeTableRecords([$poco])
            ->assertCanNotSeeTableRecords([$mucho]);
    }

    public function test_ajustar_desde_la_sucursal(): void
    {
        $branch = $this->branch();
        $product = $this->product();
        $this->stock->move($branch, $product, StockMovementType::Entrada, 6);
        $stock = Stock::first();
        $this->actingAs($admin = $this->admin());

        Livewire::test(StocksRelationManager::class, ['ownerRecord' => $branch, 'pageClass' => EditBranch::class])
            ->assertCanSeeTableRecords([$stock])
            ->callTableAction('adjust', $stock, ['mode' => 'salida', 'quantity' => 2, 'reason' => 'Se rompió'])
            ->assertHasNoTableActionErrors();

        $this->assertSame(4, $stock->refresh()->quantity);
        $this->assertTrue(StockMovement::where('reason', 'Se rompió')->where('user_id', $admin->id)->exists());

        // Más de lo que hay: no cambia
        Livewire::test(StocksRelationManager::class, ['ownerRecord' => $branch, 'pageClass' => EditBranch::class])
            ->callTableAction('adjust', $stock, ['mode' => 'salida', 'quantity' => 10, 'reason' => 'x']);
        $this->assertSame(4, $stock->refresh()->quantity);
    }

    public function test_no_borra_sucursal_con_existencias(): void
    {
        $branch = $this->branch();
        $this->stock->move($branch, $this->product(), StockMovementType::Entrada, 1);
        $this->actingAs($this->admin());

        Livewire::test(ListBranches::class)->callTableAction('delete', $branch);

        $this->assertNotSoftDeleted($branch);
    }

    public function test_sucursales_solo_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Contador]))
            ->get('/panel_hikari/branches')
            ->assertForbidden();
    }
}
