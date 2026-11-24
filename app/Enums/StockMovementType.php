<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StockMovementType: string implements HasColor, HasLabel
{
    case Entrada = 'entrada';
    case SalidaVenta = 'salida_venta';
    case Ajuste = 'ajuste';
    case Devolucion = 'devolucion';
    case TraspasoSalida = 'traspaso_salida';
    case TraspasoEntrada = 'traspaso_entrada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::SalidaVenta => 'Venta',
            self::Ajuste => 'Ajuste',
            self::Devolucion => 'Devolución',
            self::TraspasoSalida => 'Traspaso (sale)',
            self::TraspasoEntrada => 'Traspaso (entra)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Entrada, self::Devolucion, self::TraspasoEntrada => 'success',
            self::SalidaVenta, self::TraspasoSalida => 'danger',
            self::Ajuste => 'warning',
        };
    }
}
