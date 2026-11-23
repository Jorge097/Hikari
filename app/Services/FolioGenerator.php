<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class FolioGenerator
{
    // Siguiente folio sin huecos. Llamar dentro de la transacción que crea el pedido.
    public static function next(string $sequence = 'orders'): string
    {
        $number = DB::transaction(function () use ($sequence) {
            // Bloquea la fila hasta el commit
            $value = DB::table('sequences')
                ->where('name', $sequence)
                ->lockForUpdate()
                ->value('value');

            if ($value === null) {
                throw new \RuntimeException("Secuencia [{$sequence}] no existe.");
            }

            $next = (int) $value + 1;

            DB::table('sequences')
                ->where('name', $sequence)
                ->update(['value' => $next, 'updated_at' => now()]);

            return $next;
        });

        return self::format($number);
    }

    public static function format(int $number): string
    {
        return config('hikari.folio_prefix')
            .str_pad((string) $number, config('hikari.folio_padding'), '0', STR_PAD_LEFT);
    }
}
