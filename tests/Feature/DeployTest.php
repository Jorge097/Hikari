<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\GenerateMonthlySalesReport;
use App\Models\MonthlySalesReport;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DeployTest extends TestCase
{
    use RefreshDatabase;

    private function event(string $name)
    {
        return collect(app(Schedule::class)->events())->first(fn ($event) => $event->description === $name);
    }

    public function test_preflight_falla_con_configuracion_local(): void
    {
        config(['app.debug' => true, 'services.stripe.secret' => null]);

        $this->artisan('hikari:preflight')
            ->expectsOutputToContain('No abras el sitio todavía')
            ->assertFailed();
    }

    public function test_preflight_pasa_con_configuracion_de_produccion(): void
    {
        User::factory()->create(['role' => UserRole::Admin]);

        config([
            'app.debug' => false,
            'app.url' => 'https://hikaricandlesandco.com',
            'session.secure' => true,
            'services.stripe.secret' => 'sk_live_x',
            'services.stripe.webhook_secret' => 'whsec_x',
            'mail.default' => 'smtp',
            'mail.from.address' => 'pedidos@hikaricandlesandco.com',
            'hikari.report_emails' => ['charlotte@hikaricandlesandco.com'],
            'hikari.privacy.address' => 'Domicilio',
            'hikari.privacy.email' => 'privacidad@hikaricandlesandco.com',
        ]);

        // public/ simulado como en el servidor (sin tocar el local)
        $public = sys_get_temp_dir().'/hikari-public-'.uniqid();
        mkdir("{$public}/build", 0777, true);
        mkdir("{$public}/storage");
        file_put_contents("{$public}/build/manifest.json", '{}');
        // storage/ propio: OneDrive marca las carpetas locales como solo lectura
        mkdir("{$public}/app-storage/app", 0777, true);
        mkdir("{$public}/app-storage/logs");
        $original = [public_path(), storage_path()];
        $this->app->usePublicPath($public);
        $this->app->useStoragePath("{$public}/app-storage");

        try {
            $this->artisan('hikari:preflight')->assertSuccessful();
        } finally {
            $this->app->usePublicPath($original[0]);
            $this->app->useStoragePath($original[1]);
            unlink("{$public}/build/manifest.json");
            rmdir("{$public}/build");
            rmdir("{$public}/storage");
            rmdir("{$public}/app-storage/app");
            rmdir("{$public}/app-storage/logs");
            rmdir("{$public}/app-storage");
            rmdir($public);
        }
    }

    public function test_respaldo_del_cierre_mensual(): void
    {
        Queue::fake();
        $this->travelTo(now('America/Mexico_City')->setDate(2026, 12, 1)->setTime(0, 0));

        $event = $this->event('cierre-mensual-respaldo');
        $this->assertTrue($event->isDue($this->app));

        $event->run($this->app);
        Queue::assertPushed(GenerateMonthlySalesReport::class, fn ($job) => $job->year === 2026 && $job->month === 11);
    }

    public function test_respaldo_no_repite_si_ya_se_envio(): void
    {
        Queue::fake();
        MonthlySalesReport::create(['year' => 2026, 'month' => 11, 'emailed_at' => now(), 'generated_at' => now()]);
        $this->travelTo(now('America/Mexico_City')->setDate(2026, 12, 1)->setTime(0, 0));

        $this->event('cierre-mensual-respaldo')->run($this->app);

        Queue::assertNothingPushed();
    }

    public function test_htaccess_fuerza_https_y_oculta_dotfiles(): void
    {
        $htaccess = file_get_contents(public_path('.htaccess'));

        $this->assertStringContainsString('R=301', $htaccess);
        $this->assertStringContainsString('<FilesMatch "^\.">', $htaccess);
        $this->assertSame(1, substr_count($htaccess, 'RewriteEngine On'));
    }
}
