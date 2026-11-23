<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case Efectivo = 'efectivo';
    case Transferencia = 'transferencia';
    case Tarjeta = 'tarjeta';
    case Oxxo = 'oxxo';
    case Spei = 'spei';

    public function getLabel(): string
    {
        return match ($this) {
            self::Oxxo => 'OXXO',
            self::Spei => 'SPEI',
            default => ucfirst($this->value),
        };
    }

    // Métodos que se capturan a mano en el panel
    public static function manual(): array
    {
        return [self::Efectivo, self::Transferencia, self::Tarjeta];
    }
}
