<?php

namespace App\Services\Central\Bots;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramBotClient
{
    public function setWebhook(string $url, ?string $secretToken = null, bool $dropPendingUpdates = false): void
    {
        $payload = [
            'url' => $url,
            'allowed_updates' => ['message'],
            'drop_pending_updates' => $dropPendingUpdates,
        ];

        if ($secretToken !== null && $secretToken !== '') {
            $payload['secret_token'] = $secretToken;
        }

        Http::timeout(10)
            ->asJson()
            ->post($this->apiUrl('setWebhook'), $payload)
            ->throw();
    }

    public function sendMessage(string|int $chatId, string $text): void
    {
        if ($text === '') {
            return;
        }

        foreach (mb_str_split($text, 4000) as $chunk) {
            Http::timeout(10)
                ->asJson()
                ->post($this->apiUrl('sendMessage'), [
                    'chat_id' => $chatId,
                    'text' => $chunk,
                ])
                ->throw();
        }
    }

    private function apiUrl(string $method): string
    {
        return 'https://api.telegram.org/bot'.$this->token().'/'.$method;
    }

    private function token(): string
    {
        $token = (string) config('services.telegram.bot_token');

        if ($token === '') {
            throw new RuntimeException('TELEGRAM_BOT_TOKEN no esta configurado.');
        }

        return $token;
    }
}
