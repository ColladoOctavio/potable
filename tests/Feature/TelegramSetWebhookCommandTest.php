<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramSetWebhookCommandTest extends TestCase
{
    public function test_sets_telegram_webhook_from_command(): void
    {
        config([
            'services.telegram.bot_token' => 'token-prueba',
            'services.telegram.webhook_secret' => 'secreto',
        ]);
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        $this->artisan('potable:telegram-webhook https://potable.test/webhooks/telegram --drop-pending')
            ->assertSuccessful();

        Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/bottoken-prueba/setWebhook'
            && $request['url'] === 'https://potable.test/webhooks/telegram'
            && $request['secret_token'] === 'secreto'
            && $request['drop_pending_updates'] === true);
    }

    public function test_requires_webhook_secret_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config([
            'services.telegram.bot_token' => 'token-prueba',
            'services.telegram.webhook_secret' => null,
        ]);
        Http::fake();

        $this->artisan('potable:telegram-webhook https://potable.test/webhooks/telegram')
            ->assertFailed()
            ->expectsOutput('TELEGRAM_WEBHOOK_SECRET es obligatorio en producción.');

        Http::assertNothingSent();
    }
}
