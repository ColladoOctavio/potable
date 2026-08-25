<?php

namespace App\Models\Central;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotUserLink extends Model
{
    public ?string $previousTenantId = null;

    public ?int $previousEmpresaId = null;

    public ?int $previousUserId = null;

    public ?string $previousUserEmail = null;

    public ?string $linkedUserEmail = null;

    protected $fillable = [
        'channel',
        'external_user_id',
        'external_username',
        'external_display_name',
        'user_id',
        'tenant_id',
        'empresa_id',
        'active',
        'linked_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'linked_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function getConnectionName()
    {
        return config('tenancy.database.central_connection') ?: config('database.default');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function changedTenantOnLastLink(): bool
    {
        return $this->previousTenantId !== null
            && $this->previousTenantId !== (string) $this->tenant_id;
    }
}
