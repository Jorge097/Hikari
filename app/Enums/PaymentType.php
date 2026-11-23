<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentType: string implements HasColor, HasLabel
{
    case Pago = 'pago';
    case Reembolso = 'reembolso';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pago => 'success',
            self::Reembolso => 'danger',
        };
    }
}
