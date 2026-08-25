<?php

namespace App\Console\Commands;

use App\Services\Central\Bots\TelegramBotClient;
use Illuminate\Console\Command;

class TelegramSetWebhook extends Command
{
    protected $signature = 'potable:telegram-webhook
        {url? : URL publica del webhook. Si se omite usa APP_URL + /webhooks/telegram}
        {--drop-pending : Descarta mensajes pendientes en Telegram}';

    protected $description = 'Configura el webhook de Telegram para recibir mensajes del bot.';

    public function handle(TelegramBotClient $telegram): int
    {
        $url = $this->argument('url') ?: route('webhooks.telegram');
        $secret = (string) config('services.telegram.webhook_secret');

        if ($secret === '' && app()->environment('production')) {
            $this->error('TELEGRAM_WEBHOOK_SECRET es obligatorio en producción.');

            return self::FAILURE;
        }

        if (! str_starts_with($url, 'https://')) {
            $this->warn('Telegram necesita una URL HTTPS publica para webhooks.');
        }

        $telegram->setWebhook(
            url: $url,
            secretToken: $secret !== '' ? $secret : null,
            dropPendingUpdates: (bool) $this->option('drop-pending'),
        );

        $this->info('Webhook de Telegram configurado: '.$url);

        return self::SUCCESS;
    }
}
