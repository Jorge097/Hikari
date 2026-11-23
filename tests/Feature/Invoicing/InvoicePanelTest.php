<?php

namespace Tests\Feature\Invoicing;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Admin\Resources\Orders\Pages\EditOrder;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Invoicing\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class InvoicePanelTest extends TestCase
{
    use InvoicingFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('local');
        $this->setUpInvoicing();
    }

    public function test_admin_factura_desde_el_pedido_y_timbra(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $order = $this->paidOrder(['rfc' => 'LOPA800101AB1', 'tax_name' => 'ANA LÓPEZ PÉREZ', 'tax_regime' => '612', 'tax_zip_code' => '27000']);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('invoice')
            ->assertHasNoActionErrors();

        $invoice = Invoice::sole();
        $this->assertSame('LOPA800101AB1', $invoice->receiver_rfc);

        Livewire::test(ListInvoices::class)
            ->callTableAction('stamp', $invoice)
            ->assertNotified('CFDI timbrado (HK-001)');

        $this->assertSame(InvoiceStatus::Timbrada, $invoice->refresh()->status);
        $this->get("/panel_hikari/invoices/{$invoice->id}")->assertOk()->assertSee($invoice->uuid);
    }

    public function test_contador_ve_facturas_sin_timbrar_ni_cancelar(): void
    {
        $service = app(InvoiceService::class);
        $stamped = $service->stamp($service->request($this->paidOrder(), $this->fiscalData()));

        $this->actingAs(User::factory()->create(['role' => UserRole::Contador]));

        $this->get('/panel_hikari/invoices')->assertOk()->assertSee('LOPA800101AB1');
        Livewire::test(ListInvoices::class)
            ->assertTableActionVisible('download_xml', $stamped)
            ->assertTableActionHidden('cancel', $stamped)
            ->assertTableActionHidden('credit_note', $stamped);
        $this->get("/panel_hikari/invoices/{$stamped->id}/edit")->assertForbidden();
    }
}
