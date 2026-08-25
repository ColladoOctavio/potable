<?php

namespace App\Services\Tenant\Bots;

use App\Models\Tenant\BotPendingAction;
use Carbon\CarbonInterface;

class BotPendingActionStore
{
    public function createPending(
        string $channel,
        string $externalUserId,
        ?int $userId,
        int $empresaId,
        string $type,
        array $payload,
        ?CarbonInterface $expiresAt = null,
    ): BotPendingAction {
        return BotPendingAction::create([
            'channel' => $channel,
            'external_user_id' => $externalUserId,
            'user_id' => $userId,
            'empresa_id' => $empresaId,
            'type' => $type,
            'payload' => $payload,
            'status' => BotPendingAction::STATUS_PENDING,
            'expires_at' => $expiresAt ?? now()->addMinutes(30),
        ]);
    }

    public function findPending(string $channel, string $externalUserId, ?string $type = null): ?BotPendingAction
    {
        return BotPendingAction::query()
            ->where('channel', $channel)
            ->where('external_user_id', $externalUserId)
            ->where('status', BotPendingAction::STATUS_PENDING)
            ->where('expires_at', '>', now())
            ->when($type, fn ($query) => $query->where('type', $type))
            ->latest()
            ->first();
    }

    public function complete(BotPendingAction $action): void
    {
        $action->forceFill([
            'status' => BotPendingAction::STATUS_COMPLETED,
            'completed_at' => now(),
        ])->save();
    }

    public function cancel(BotPendingAction $action): void
    {
        $action->forceFill([
            'status' => BotPendingAction::STATUS_CANCELLED,
        ])->save();
    }

    public function expireOld(): int
    {
        return BotPendingAction::query()
            ->where('status', BotPendingAction::STATUS_PENDING)
            ->where('expires_at', '<=', now())
            ->update(['status' => BotPendingAction::STATUS_EXPIRED]);
    }
}
