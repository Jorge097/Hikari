<?php

namespace Tests\Feature\Invoicing;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceRequestTest extends TestCase
{
    use InvoicingFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->setUpInvoicing();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge($this->fiscalData(), ['folio' => 'hk-001', 'email' => 'ANA@example.com'], $overrides);
    }

    public function test_pagina_carga_con_folio(): void
    {
        $this->get('/factura?folio=HK-001')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('SolicitarFactura')->where('folio', 'HK-001')->has('taxRegimes'));
    }

    public function test_cliente_solicita_factura(): void
    {
        $this->paidOrder();

        $this->post('/factura', $this->payload(['rfc' => ' lopa800101ab1 ']))
            ->assertRedirect('/factura')
            ->assertSessionHas('message');

        $invoice = Invoice::sole();
        $this->assertSame(InvoiceStatus::Solicitada, $invoice->status);
        $this->assertSame('LOPA800101AB1', $invoice->receiver_rfc);
        $this->assertEmpty($this->pac->stamped);
    }

    public function test_correo_distinto_no_revela_el_pedido(): void
    {
        $this->paidOrder();

        $this->post('/factura', $this->payload(['email' => 'otro@example.com']))
            ->assertSessionHasErrors(['folio' => 'No encontramos un pedido con ese folio y correo.']);
        $this->post('/factura', $this->payload(['folio' => 'HK-999']))
            ->assertSessionHasErrors(['folio' => 'No encontramos un pedido con ese folio y correo.']);

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_valida_rfc_y_catalogos(): void
    {
        $this->paidOrder();

        $this->post('/factura', $this->payload([
            'rfc' => 'XAXX010101000',
            'tax_regime' => '999',
            'tax_zip_code' => '270',
            'cfdi_use' => 'P01',
        ]))->assertSessionHasErrors(['rfc', 'tax_regime', 'tax_zip_code', 'cfdi_use']);
    }

    public function test_limite_de_intentos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/factura', []);
        }

        $this->post('/factura', [])->assertStatus(429);
    }
}
