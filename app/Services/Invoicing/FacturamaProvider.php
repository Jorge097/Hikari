<?php

namespace App\Services\Invoicing;

use App\Models\Invoice;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

// Facturama API Web (un emisor). El CSD lo carga Charlotte en el portal de Facturama.
class FacturamaProvider implements InvoiceProvider
{
    public function __construct(
        private readonly ?string $user,
        private readonly ?string $password,
        private readonly bool $sandbox = true,
    ) {}

    public function name(): string
    {
        return 'facturama';
    }

    public function stamp(Invoice $invoice, array $cfdi): array
    {
        $data = $this->check($this->client()->post('3/cfdis', $cfdi))->json();

        $uuid = data_get($data, 'Complement.TaxStamp.Uuid');

        if (! $uuid) {
            throw new InvoiceProviderException('Facturama no devolvió el UUID del CFDI.');
        }

        return ['id' => (string) $data['Id'], 'uuid' => strtolower($uuid)];
    }

    public function download(Invoice $invoice, string $format): string
    {
        $response = $this->check($this->client()->get("cfdi/{$format}/issued/{$invoice->provider_id}"));

        // Respuesta con el archivo en Base64
        $content = $response->json('Content');

        return $content ? base64_decode($content) : $response->body();
    }

    public function cancel(Invoice $invoice, string $motive, ?string $replacementUuid = null): string
    {
        // uuidReplacement solo con motivo 01; enviarlo de más provoca error
        $query = array_filter([
            'type' => 'issued',
            'motive' => $motive,
            'uuidReplacement' => $motive === '01' ? $replacementUuid : null,
        ]);

        $response = $this->check($this->client()->delete("cfdi/{$invoice->provider_id}?".http_build_query($query)));

        return (string) $response->json('Status', 'pending');
    }

    private function client(): PendingRequest
    {
        if (! $this->user || ! $this->password) {
            throw new InvoiceProviderException('Faltan FACTURAMA_USER y FACTURAMA_PASSWORD en .env.');
        }

        $base = $this->sandbox ? 'https://apisandbox.facturama.mx/' : 'https://api.facturama.mx/';

        return Http::baseUrl($base)
            ->withBasicAuth($this->user, $this->password)
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    // Convierte errores del PAC en un mensaje corto (sin volcar datos fiscales a logs)
    private function check(Response $response): Response
    {
        if ($response->successful()) {
            return $response;
        }

        if ($response->status() === 401) {
            throw new InvoiceProviderException('Credenciales de Facturama inválidas.');
        }

        $details = collect($response->json('ModelState', []))->flatten()->implode(' ');
        $message = trim($response->json('Message', 'Error del PAC').' '.$details);

        throw new InvoiceProviderException(mb_substr($message, 0, 1000));
    }
}
