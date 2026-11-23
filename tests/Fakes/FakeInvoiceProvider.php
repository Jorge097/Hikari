<?php

namespace Tests\Fakes;

use App\Models\Invoice;
use App\Services\Invoicing\InvoiceProvider;
use App\Services\Invoicing\InvoiceProviderException;

class FakeInvoiceProvider implements InvoiceProvider
{
    public array $stamped = [];

    public array $cancelled = [];

    public ?string $failWith = null;

    public string $cancelStatus = 'canceled';

    public function name(): string
    {
        return 'fake';
    }

    public function stamp(Invoice $invoice, array $cfdi): array
    {
        if ($this->failWith) {
            throw new InvoiceProviderException($this->failWith);
        }

        $this->stamped[] = $cfdi;
        $n = count($this->stamped);

        return ['id' => "fake-{$n}", 'uuid' => sprintf('00000000-0000-4000-8000-%012d', $n)];
    }

    public function download(Invoice $invoice, string $format): string
    {
        return "{$format}:{$invoice->uuid}";
    }

    public function cancel(Invoice $invoice, string $motive, ?string $replacementUuid = null): string
    {
        $this->cancelled[] = compact('motive', 'replacementUuid');

        return $this->cancelStatus;
    }
}
