<?php

namespace App\Services\Invoicing;

use RuntimeException;

// Rechazo del PAC con un mensaje legible para el panel
class InvoiceProviderException extends RuntimeException {}
