<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case Pendiente = 'pendiente';
    case Pagado = 'pagado';
    case Enviado = 'enviado';
    case Entregado = 'entregado';
    case Cancelado = 'cancelado';
    case Reembolsado = 'reembolsado';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Pagado => 'success',
            self::Enviado, self::Entregado => 'info',
            self::Cancelado, self::Reembolsado => 'danger',
        };
    }
}
