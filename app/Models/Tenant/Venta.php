<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class Venta extends Model
{
    use TenantConnection;

    protected $fillable = ['empresa_id', 'cliente_id', 'lote_id', 'fecha', 'descripcion', 'kilos', 'precio_por_kg', 'importe_total', 'estado_cobro', 'observacion'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    protected static function booted(): void
    {
        static::saving(function (Venta $venta): void {
            $venta->importe_total = (float) $venta->kilos * (float) $venta->precio_por_kg;
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    public function movimientos(): MorphMany
    {
        return $this->morphMany(MovimientoCuenta::class, 'origen');
    }
}
