<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class Cuenta extends Model
{
    use TenantConnection;

    protected $fillable = ['empresa_id', 'nombre', 'saldo_inicial', 'observacion'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoCuenta::class);
    }

    public function saldoActual(): float
    {
        $ingresos = $this->movimientos()->where('tipo', 'ingreso')->sum('importe');
        $egresos = $this->movimientos()->where('tipo', 'egreso')->sum('importe');
        $ajustes = $this->movimientos()->where('tipo', 'ajuste')->sum('importe');

        return (float) $this->saldo_inicial + (float) $ingresos - (float) $egresos + (float) $ajustes;
    }
}
