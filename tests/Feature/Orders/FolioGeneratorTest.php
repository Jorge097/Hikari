<?php

namespace Tests\Feature\Orders;

use App\Models\Customer;
use App\Models\Order;
use App\Services\FolioGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FolioGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_folios_secuenciales(): void
    {
        $this->assertSame('HK-001', FolioGenerator::next());
        $this->assertSame('HK-002', FolioGenerator::next());
        $this->assertSame('HK-003', FolioGenerator::next());
    }

    public function test_despues_de_999_sigue_hk_1000(): void
    {
        DB::table('sequences')->where('name', 'orders')->update(['value' => 998]);

        $this->assertSame('HK-999', FolioGenerator::next());
        $this->assertSame('HK-1000', FolioGenerator::next());
    }

    public function test_rollback_no_deja_huecos(): void
    {
        try {
            DB::transaction(function () {
                FolioGenerator::next();
                throw new \RuntimeException('Falla al guardar el pedido');
            });
        } catch (\RuntimeException) {
            // Esperado
        }

        $this->assertSame('HK-001', FolioGenerator::next());
    }

    public function test_el_pedido_recibe_folio_al_crearse(): void
    {
        $customer = Customer::factory()->create();

        $first = $customer->orders()->create();
        $second = $customer->orders()->create();

        $this->assertSame('HK-001', $first->folio);
        $this->assertSame('HK-002', $second->folio);
        $this->assertEquals(0.16, (float) $first->tax_rate);
        $this->assertTrue($first->prices_include_tax);
    }

    public function test_folio_no_se_puede_repetir(): void
    {
        $customer = Customer::factory()->create();
        $customer->orders()->create();

        $this->expectException(QueryException::class);

        $duplicate = new Order(['customer_id' => $customer->id]);
        $duplicate->folio = 'HK-001';
        $duplicate->save();
    }
}
