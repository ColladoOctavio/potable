<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class Cliente extends Model
{
    use TenantConnection;

    protected $fillable = ['empresa_id', 'nombre', 'cuit', 'telefono', 'email', 'direccion', 'saldo_inicial', 'observacion'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function movimientos(): MorphMany
    {
        return $this->morphMany(MovimientoCuenta::class, 'origen');
    }
}
