<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasColor, HasLabel
{
    case Solicitada = 'solicitada';
    case Timbrada = 'timbrada';
    case Error = 'error';
    case CancelacionPendiente = 'cancelacion_pendiente';
    case Cancelada = 'cancelada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Solicitada => 'Solicitada',
            self::Timbrada => 'Timbrada',
            self::Error => 'Error',
            self::CancelacionPendiente => 'Cancelación pendiente',
            self::Cancelada => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Solicitada => 'warning',
            self::Timbrada => 'success',
            self::Error => 'danger',
            self::CancelacionPendiente => 'info',
            self::Cancelada => 'gray',
        };
    }

    // Bloquean una nueva factura de ingreso para el pedido
    public static function active(): array
    {
        return [self::Solicitada, self::Timbrada, self::Error, self::CancelacionPendiente];
    }
}
