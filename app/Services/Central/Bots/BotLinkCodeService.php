<?php

namespace App\Services\Central\Bots;

use App\Models\Central\BotLinkCode;
use App\Models\Central\BotUserLink;
use App\Models\User;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class BotLinkCodeService
{
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(private readonly BotUserLinkStore $links) {}

    public function createCode(string $channel, int $userId, string $tenantId, int $empresaId, ?CarbonInterface $expiresAt = null): BotLinkCodeIssue
    {
        if (! $this->userCanAccessTenant($userId, $tenantId)) {
            throw new InvalidArgumentException('El usuario no tiene acceso al tenant indicado.');
        }

        $this->expirePreviousCodes($channel, $userId, $tenantId, $empresaId);

        $code = $this->newCode();
        $linkCode = $this->persistCode([
            'channel' => $channel,
            'code_hash' => $this->hashCode($code),
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'empresa_id' => $empresaId,
            'expires_at' => $expiresAt ?? now()->addMinutes(10),
        ]);

        return new BotLinkCodeIssue($this->formatCode($code), $linkCode);
    }

    public function consumeCode(
        string $channel,
        string $code,
        string $externalUserId,
        ?string $externalUsername = null,
        ?string $externalDisplayName = null,
    ): BotUserLink {
        $linkCode = $this->findValidCode($channel, $code);

        if (! $linkCode) {
            throw new InvalidArgumentException('El codigo de vinculacion no existe o vencio.');
        }

        $link = $this->links->link(
            channel: $channel,
            externalUserId: $externalUserId,
            userId: (int) $linkCode->user_id,
            tenantId: (string) $linkCode->tenant_id,
            empresaId: (int) $linkCode->empresa_id,
            externalUsername: $externalUsername,
            externalDisplayName: $externalDisplayName,
        );

        $this->markConsumed($linkCode);

        return $link;
    }

    protected function persistCode(array $attributes): BotLinkCode
    {
        return BotLinkCode::create($attributes);
    }

    protected function findValidCode(string $channel, string $code): ?BotLinkCode
    {
        return BotLinkCode::query()
            ->where('channel', $channel)
            ->where('code_hash', $this->hashCode($code))
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    protected function markConsumed(BotLinkCode $linkCode): void
    {
        $linkCode->forceFill(['consumed_at' => now()])->save();
    }

    protected function expirePreviousCodes(string $channel, int $userId, string $tenantId, int $empresaId): void
    {
        BotLinkCode::query()
            ->where('channel', $channel)
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('empresa_id', $empresaId)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->update(['consumed_at' => now()]);
    }

    protected function userCanAccessTenant(int $userId, string $tenantId): bool
    {
        $user = User::find($userId);

        if (! $user) {
            return false;
        }

        if ($user->isPlatformAdmin()) {
            return false;
        }

        return $user->tenants()->whereKey($tenantId)->where('estado', '!=', 'suspendido')->exists();
    }

    protected function newCode(): string
    {
        $code = '';
        $max = strlen(self::CODE_ALPHABET) - 1;

        for ($i = 0; $i < 8; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, $max)];
        }

        return $code;
    }

    protected function hashCode(string $code): string
    {
        return hash('sha256', $this->normalizeCode($code));
    }

    protected function normalizeCode(string $code): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($code)) ?? '';
    }

    private function formatCode(string $code): string
    {
        return substr($code, 0, 4).'-'.substr($code, 4);
    }
}
