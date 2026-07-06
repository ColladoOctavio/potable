<?php

namespace App\Models\Central;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;

    protected $keyType = 'string';

    public $incrementing = false;

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    public function shouldGenerateId(): bool
    {
        return false;
    }

    protected $fillable = [
        'id',
        'nombre',
        'slug',
        'database_name',
        'tenancy_db_name',
        'estado',
        'owner_user_id',
        'data',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'nombre',
            'slug',
            'database_name',
            'tenancy_db_name',
            'estado',
            'owner_user_id',
            'created_at',
            'updated_at',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('rol')->withTimestamps();
    }
}
