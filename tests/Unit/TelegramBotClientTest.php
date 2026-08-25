<?php

namespace Tests\Unit;

use App\Services\Central\Bots\TelegramBotClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class TelegramBotClientTest extends TestCase
{
    public function test_sends_message_to_telegram_api(): void
    {
        config(['services.telegram.bot_token' => 'token-prueba']);
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        (new TelegramBotClient)->sendMessage(123, 'Hola');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/bottoken-prueba/sendMessage'
            && $request['chat_id'] === 123
            && $request['text'] === 'Hola');
    }

    public function test_sets_webhook(): void
    {
        config(['services.telegram.bot_token' => 'token-prueba']);
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        (new TelegramBotClient)->setWebhook(
            url: 'https://potable.test/webhooks/telegram',
            secretToken: 'secreto',
            dropPendingUpdates: true,
        );

        Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/bottoken-prueba/setWebhook'
            && $request['url'] === 'https://potable.test/webhooks/telegram'
            && $request['secret_token'] === 'secreto'
            && $request['drop_pending_updates'] === true
            && $request['allowed_updates'] === ['message']);
    }

    public function test_requires_token(): void
    {
        config(['services.telegram.bot_token' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TELEGRAM_BOT_TOKEN no esta configurado.');

        (new TelegramBotClient)->sendMessage(123, 'Hola');
    }
}
