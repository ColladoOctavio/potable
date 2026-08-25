<?php

namespace App\Services\Central\Bots;

use App\Models\Central\BotUserLink;
use App\Models\User;
use InvalidArgumentException;

class BotUserLinkStore
{
    public function link(
        string $channel,
        string $externalUserId,
        int $userId,
        string $tenantId,
        int $empresaId,
        ?string $externalUsername = null,
        ?string $externalDisplayName = null,
    ): BotUserLink {
        if (! $this->userCanAccessTenant($userId, $tenantId)) {
            throw new InvalidArgumentException('El usuario no tiene acceso al tenant indicado.');
        }

        return $this->persistLink([
            'channel' => $channel,
            'external_user_id' => $externalUserId,
            'external_username' => $externalUsername,
            'external_display_name' => $externalDisplayName,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'empresa_id' => $empresaId,
            'active' => true,
            'linked_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    public function findLinkedContext(string $channel, string $externalUserId): ?BotUserLink
    {
        $link = $this->findActiveLink($channel, $externalUserId);

        if (! $link) {
            return null;
        }

        if (! $this->userCanAccessTenant((int) $link->user_id, (string) $link->tenant_id)) {
            $this->unlink($link);

            return null;
        }

        return $link;
    }

    public function touchSeen(BotUserLink $link): void
    {
        $link->forceFill(['last_seen_at' => now()]);
        $this->save($link);
    }

    public function unlink(BotUserLink $link): void
    {
        $link->forceFill(['active' => false]);
        $this->save($link);
    }

    public function changeEmpresa(BotUserLink $link, int $empresaId): void
    {
        $link->forceFill(['empresa_id' => $empresaId]);
        $this->save($link);
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

    protected function persistLink(array $attributes): BotUserLink
    {
        $lookup = [
            'channel' => $attributes['channel'],
            'external_user_id' => $attributes['external_user_id'],
        ];
        $previousLink = BotUserLink::query()->with('user')->where($lookup)->first();
        $link = BotUserLink::updateOrCreate($lookup, $attributes);
        $link->loadMissing('user');
        $link->linkedUserEmail = $link->user?->email;

        if ($previousLink) {
            $link->previousTenantId = (string) $previousLink->tenant_id;
            $link->previousEmpresaId = (int) $previousLink->empresa_id;
            $link->previousUserId = (int) $previousLink->user_id;
            $link->previousUserEmail = $previousLink->user?->email;
        }

        return $link;
    }

    protected function findActiveLink(string $channel, string $externalUserId): ?BotUserLink
    {
        return BotUserLink::query()
            ->with(['user', 'tenant'])
            ->where('channel', $channel)
            ->where('external_user_id', $externalUserId)
            ->where('active', true)
            ->whereHas('tenant', fn ($query) => $query->where('estado', '!=', 'suspendido'))
            ->first();
    }

    protected function save(BotUserLink $link): void
    {
        $link->save();
    }
}
