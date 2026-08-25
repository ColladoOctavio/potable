<?php

namespace App\Http\Controllers\Central\Bots;

use App\Http\Controllers\Controller;
use App\Services\Central\Bots\BotMessageRouter;
use App\Services\Central\Bots\TelegramBotClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, BotMessageRouter $router, TelegramBotClient $telegram): JsonResponse
    {
        if (! $this->hasValidSecret($request)) {
            return response()->json(['ok' => false], 403);
        }

        $message = $request->input('message');

        if (! is_array($message)) {
            return response()->json(['ok' => true]);
        }

        $text = data_get($message, 'text');
        $chatId = data_get($message, 'chat.id');
        $fromId = data_get($message, 'from.id');

        if (! is_string($text) || trim($text) === '' || ! $this->isTelegramId($chatId) || ! $this->isTelegramId($fromId)) {
            return response()->json(['ok' => true]);
        }

        if ($this->isRateLimited((string) $fromId)) {
            return response()->json(['ok' => true]);
        }

        $reply = $this->routeMessage($router, $text, (string) $fromId, $message);
        $this->sendReply($telegram, $chatId, $reply);

        return response()->json(['ok' => true]);
    }

    private function routeMessage(BotMessageRouter $router, string $text, string $fromId, array $message): string
    {
        try {
            return $router->handleMessage(
                channel: 'telegram',
                externalUserId: $fromId,
                text: $text,
                externalUsername: $this->stringOrNull(data_get($message, 'from.username')),
                externalDisplayName: $this->displayName($message),
            );
        } catch (Throwable $exception) {
            report($exception);

            return 'No pude procesar ese mensaje. Proba de nuevo en unos minutos.';
        }
    }

    private function sendReply(TelegramBotClient $telegram, string|int $chatId, string $reply): void
    {
        try {
            $telegram->sendMessage($chatId, $reply);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function hasValidSecret(Request $request): bool
    {
        $expected = (string) config('services.telegram.webhook_secret');

        if ($expected === '') {
            return ! app()->environment('production');
        }

        return hash_equals($expected, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'));
    }

    private function isRateLimited(string $fromId): bool
    {
        $key = 'telegram-webhook:'.$fromId;

        if (RateLimiter::tooManyAttempts($key, 30)) {
            return true;
        }

        RateLimiter::hit($key, 60);

        return false;
    }

    private function displayName(array $message): ?string
    {
        $name = collect([
            data_get($message, 'from.first_name'),
            data_get($message, 'from.last_name'),
        ])
            ->filter(fn ($part) => is_string($part) && $part !== '')
            ->implode(' ');

        return $name !== '' ? $name : null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function isTelegramId(mixed $value): bool
    {
        return is_int($value) || is_string($value);
    }
}
