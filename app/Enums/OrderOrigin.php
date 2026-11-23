<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderOrigin: string implements HasColor, HasLabel
{
    case Web = 'web';
    case Whatsapp = 'whatsapp';

    public function getLabel(): string
    {
        return match ($this) {
            self::Web => 'Web',
            self::Whatsapp => 'WhatsApp',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Web => 'gray',
            self::Whatsapp => 'success',
        };
    }
}
