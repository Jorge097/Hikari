<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Throwable;

// Revisión antes de abrir el sitio: nunca imprime secretos
class ProductionPreflight extends Command
{
    protected $signature = 'hikari:preflight';

    protected $description = 'Verifica la configuración de producción (Stripe, correo, PAC, cola, DB)';

    private array $results = [];

    public function handle(Migrator $migrator): int
    {
        $production = app()->isProduction();

        // App
        $this->check('APP_ENV=production', $production, 'warn');
        $this->check('APP_DEBUG=false', ! config('app.debug'));
        $this->check('APP_KEY definida', filled(config('app.key')));
        $this->check('APP_URL con https', str_starts_with((string) config('app.url'), 'https://'));
        $this->check('SESSION_SECURE_COOKIE=true', (bool) config('session.secure'));
        $this->check('Zona horaria America/Mexico_City', config('app.timezone') === 'America/Mexico_City');

        // Base de datos
        try {
            DB::connection()->getPdo();
            $this->check('Conexión a la base de datos', true);

            $ran = $migrator->getRepository()->getRan();
            $pending = collect($migrator->getMigrationFiles(database_path('migrations')))->keys()->diff($ran);
            $this->check('Sin migraciones pendientes', $pending->isEmpty(), detail: $pending->implode(', '));

            $hasAdmin = User::where('role', UserRole::Admin)->exists()
                || User::whereIn('email', config('app.admin_emails'))->exists();
            $this->check('Existe un administrador del panel', $hasAdmin);
        } catch (Throwable $e) {
            $this->check('Conexión a la base de datos', false, detail: class_basename($e));
        }

        // Archivos
        $this->check('storage/ con permisos de escritura', is_writable(storage_path('app')) && is_writable(storage_path('logs')));
        $this->check('Enlace public/storage (imágenes)', file_exists(public_path('storage')));
        $this->check('Assets compilados (public/build)', file_exists(public_path('build/manifest.json')));
        $this->check('Sin public/hot (Vite dev)', ! file_exists(public_path('hot')));

        // Stripe
        $secret = (string) config('services.stripe.secret');
        $this->check('STRIPE_SECRET definida', filled($secret));
        $this->check('Stripe en modo live', str_starts_with($secret, 'sk_live_') || str_starts_with($secret, 'rk_live_'), 'warn');
        $this->check('STRIPE_WEBHOOK_SECRET definida', str_starts_with((string) config('services.stripe.webhook_secret'), 'whsec_'));

        // Correo y cola
        $this->check('Correo real (MAIL_MAILER)', ! in_array(config('mail.default'), ['log', 'array'], true));
        $this->check('MAIL_FROM_ADDRESS propio', ! str_ends_with((string) config('mail.from.address'), '@example.com'));
        $this->check('Destinatarios del cierre mensual', filled(config('hikari.report_emails')));
        $queue = config('queue.default');
        $this->check(
            'La cola se procesa (sync o HIKARI_QUEUE_VIA_SCHEDULER)',
            $queue === 'sync' || config('hikari.queue_via_scheduler'),
            'warn',
            "QUEUE_CONNECTION={$queue}",
        );

        // Aviso de privacidad
        $this->check('Domicilio y correo del aviso de privacidad', filled(config('hikari.privacy.address')) && filled(config('hikari.privacy.email')));

        // Facturación
        $this->check('Credenciales de Facturama', filled(config('services.facturama.user')) && filled(config('services.facturama.password')), 'warn');
        $this->check('Facturama en producción', ! config('services.facturama.sandbox'), 'warn');
        $this->check('CP de expedición del CFDI', filled(config('hikari.invoicing.expedition_place')), 'warn');

        $this->table(['', 'Revisión', 'Detalle'], $this->results);

        $failed = collect($this->results)->where(0, '✗')->count();
        $warned = collect($this->results)->where(0, '!')->count();

        if ($failed) {
            $this->error("{$failed} revisión(es) fallaron. No abras el sitio todavía.");

            return self::FAILURE;
        }

        $this->info($warned ? "Listo con {$warned} advertencia(s)." : 'Todo listo.');

        return self::SUCCESS;
    }

    // level: 'fail' bloquea; 'warn' solo avisa
    private function check(string $label, bool $ok, string $level = 'fail', string $detail = ''): void
    {
        $this->results[] = [$ok ? '✓' : ($level === 'warn' ? '!' : '✗'), $label, $ok ? '' : $detail];
    }
}
