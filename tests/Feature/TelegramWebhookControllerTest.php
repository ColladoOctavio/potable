<?php

namespace Tests\Feature;

use App\Services\Central\Bots\BotMessageRouter;
use App\Services\Central\Bots\TelegramBotClient;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class TelegramWebhookControllerTest extends TestCase
{
    public function test_routes_text_message_and_sends_reply(): void
    {
        config(['services.telegram.webhook_secret' => null]);

        $router = new FakeBotMessageRouterForTelegramWebhook;
        $telegram = new FakeTelegramBotClientForTelegramWebhook;
        $this->app->instance(BotMessageRouter::class, $router);
        $this->app->instance(TelegramBotClient::class, $telegram);

        $this->postJson('/webhooks/telegram', [
            'message' => [
                'text' => '/empresa',
                'chat' => ['id' => 555],
                'from' => [
                    'id' => 123,
                    'username' => 'octavio',
                    'first_name' => 'Octavio',
                    'last_name' => 'Prueba',
                ],
            ],
        ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame('telegram', $router->channel);
        $this->assertSame('123', $router->externalUserId);
        $this->assertSame('/empresa', $router->text);
        $this->assertSame('octavio', $router->externalUsername);
        $this->assertSame('Octavio Prueba', $router->externalDisplayName);
        $this->assertSame([['chatId' => 555, 'text' => 'respuesta router']], $telegram->sent);
    }

    public function test_rejects_invalid_secret_when_configured(): void
    {
        config(['services.telegram.webhook_secret' => 'secreto']);

        $router = new FakeBotMessageRouterForTelegramWebhook;
        $telegram = new FakeTelegramBotClientForTelegramWebhook;
        $this->app->instance(BotMessageRouter::class, $router);
        $this->app->instance(TelegramBotClient::class, $telegram);

        $this->postJson('/webhooks/telegram', [
            'message' => [
                'text' => '/empresa',
                'chat' => ['id' => 555],
                'from' => ['id' => 123],
            ],
        ])->assertForbidden();

        $this->assertNull($router->text);
        $this->assertSame([], $telegram->sent);
    }

    public function test_rejects_missing_secret_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['services.telegram.webhook_secret' => null]);

        $router = new FakeBotMessageRouterForTelegramWebhook;
        $telegram = new FakeTelegramBotClientForTelegramWebhook;
        $this->app->instance(BotMessageRouter::class, $router);
        $this->app->instance(TelegramBotClient::class, $telegram);

        $this->postJson('/webhooks/telegram', [
            'message' => [
                'text' => '/empresa',
                'chat' => ['id' => 555],
                'from' => ['id' => 123],
            ],
        ])->assertForbidden();

        $this->assertNull($router->text);
        $this->assertSame([], $telegram->sent);
    }

    public function test_ignores_rate_limited_sender(): void
    {
        config(['services.telegram.webhook_secret' => null]);
        RateLimiter::clear('telegram-webhook:123');

        for ($i = 0; $i < 30; $i++) {
            RateLimiter::hit('telegram-webhook:123', 60);
        }

        $router = new FakeBotMessageRouterForTelegramWebhook;
        $telegram = new FakeTelegramBotClientForTelegramWebhook;
        $this->app->instance(BotMessageRouter::class, $router);
        $this->app->instance(TelegramBotClient::class, $telegram);

        $this->postJson('/webhooks/telegram', [
            'message' => [
                'text' => '/empresa',
                'chat' => ['id' => 555],
                'from' => ['id' => 123],
            ],
        ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertNull($router->text);
        $this->assertSame([], $telegram->sent);
    }

    public function test_ignores_non_text_updates(): void
    {
        config(['services.telegram.webhook_secret' => null]);

        $router = new FakeBotMessageRouterForTelegramWebhook;
        $telegram = new FakeTelegramBotClientForTelegramWebhook;
        $this->app->instance(BotMessageRouter::class, $router);
        $this->app->instance(TelegramBotClient::class, $telegram);

        $this->postJson('/webhooks/telegram', [
            'message' => [
                'chat' => ['id' => 555],
                'from' => ['id' => 123],
                'photo' => [],
            ],
        ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertNull($router->text);
        $this->assertSame([], $telegram->sent);
    }
}

class FakeBotMessageRouterForTelegramWebhook extends BotMessageRouter
{
    public ?string $channel = null;

    public ?string $externalUserId = null;

    public ?string $text = null;

    public ?string $externalUsername = null;

    public ?string $externalDisplayName = null;

    public function __construct() {}

    public function handleMessage(
        string $channel,
        string $externalUserId,
        string $text,
        ?string $externalUsername = null,
        ?string $externalDisplayName = null,
    ): string {
        $this->channel = $channel;
        $this->externalUserId = $externalUserId;
        $this->text = $text;
        $this->externalUsername = $externalUsername;
        $this->externalDisplayName = $externalDisplayName;

        return 'respuesta router';
    }
}

class FakeTelegramBotClientForTelegramWebhook extends TelegramBotClient
{
    public array $sent = [];

    public function sendMessage(string|int $chatId, string $text): void
    {
        $this->sent[] = compact('chatId', 'text');
    }
}
