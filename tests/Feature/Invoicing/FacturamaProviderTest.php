<?php

namespace Tests\Feature\Invoicing;

use App\Models\Invoice;
use App\Services\Invoicing\FacturamaProvider;
use App\Services\Invoicing\InvoiceProviderException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacturamaProviderTest extends TestCase
{
    private FacturamaProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        $this->provider = new FacturamaProvider('usuario', 'secreto', sandbox: true);
    }

    private function invoice(): Invoice
    {
        return (new Invoice)->forceFill(['provider_id' => 'abc123']);
    }

    public function test_timbra_en_sandbox_con_basic_auth(): void
    {
        Http::fake(['apisandbox.facturama.mx/3/cfdis' => Http::response([
            'Id' => 'abc123',
            'Complement' => ['TaxStamp' => ['Uuid' => 'AAAAAAAA-BBBB-4CCC-8DDD-EEEEEEEEEEEE']],
        ], 201)]);

        $result = $this->provider->stamp($this->invoice(), ['CfdiType' => 'I']);

        $this->assertSame(['id' => 'abc123', 'uuid' => 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee'], $result);
        Http::assertSent(fn (Request $r) => $r->method() === 'POST'
            && $r->url() === 'https://apisandbox.facturama.mx/3/cfdis'
            && $r->hasHeader('Authorization', 'Basic '.base64_encode('usuario:secreto'))
            && $r['CfdiType'] === 'I');
    }

    public function test_error_del_pac_se_resume(): void
    {
        Http::fake(['*' => Http::response([
            'Message' => 'La solicitud no es válida.',
            'ModelState' => ['Receiver.Rfc' => ['El RFC no está en la lista de inscritos.']],
        ], 400)]);

        $this->expectException(InvoiceProviderException::class);
        $this->expectExceptionMessage('La solicitud no es válida. El RFC no está en la lista de inscritos.');

        $this->provider->stamp($this->invoice(), []);
    }

    public function test_credenciales_invalidas(): void
    {
        Http::fake(['*' => Http::response(null, 401)]);

        $this->expectExceptionMessage('Credenciales de Facturama inválidas.');
        $this->provider->stamp($this->invoice(), []);
    }

    public function test_sin_credenciales_no_llama(): void
    {
        $this->expectExceptionMessage('Faltan FACTURAMA_USER');
        (new FacturamaProvider(null, null))->stamp($this->invoice(), []);
    }

    public function test_descarga_decodifica_base64(): void
    {
        Http::fake(['apisandbox.facturama.mx/cfdi/xml/issued/abc123' => Http::response([
            'ContentEncoding' => 'base64',
            'Content' => base64_encode('<cfdi/>'),
        ])]);

        $this->assertSame('<cfdi/>', $this->provider->download($this->invoice(), 'xml'));
    }

    public function test_cancela_sin_uuid_de_reemplazo_salvo_motivo_01(): void
    {
        Http::fake(['*' => Http::response(['Status' => 'canceled'])]);

        $this->assertSame('canceled', $this->provider->cancel($this->invoice(), '02', 'ignorado'));
        $this->provider->cancel($this->invoice(), '01', 'uuid-nuevo');

        $urls = collect(Http::recorded())->map(fn ($pair) => $pair[0]->url())->all();
        $this->assertSame('https://apisandbox.facturama.mx/cfdi/abc123?type=issued&motive=02', $urls[0]);
        $this->assertSame('https://apisandbox.facturama.mx/cfdi/abc123?type=issued&motive=01&uuidReplacement=uuid-nuevo', $urls[1]);
    }

    public function test_produccion_usa_host_productivo(): void
    {
        Http::fake(['api.facturama.mx/*' => Http::response(['Content' => base64_encode('pdf')])]);

        $this->assertSame('pdf', (new FacturamaProvider('u', 'p', sandbox: false))->download($this->invoice(), 'pdf'));
    }
}
