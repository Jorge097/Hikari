<?php

namespace App\Services\Invoicing;

use App\Models\Invoice;

// Contrato con el PAC; permite cambiarlo sin tocar el resto
interface InvoiceProvider
{
    public function name(): string;

    /**
     * Timbra el CFDI.
     *
     * @return array{id: string, uuid: string}
     *
     * @throws InvoiceProviderException
     */
    public function stamp(Invoice $invoice, array $cfdi): array;

    // Contenido del archivo ('xml' o 'pdf')
    public function download(Invoice $invoice, string $format): string;

    /**
     * Solicita la cancelación ante el SAT.
     *
     * @return string Estado devuelto: canceled, pending o active
     */
    public function cancel(Invoice $invoice, string $motive, ?string $replacementUuid = null): string;
}
