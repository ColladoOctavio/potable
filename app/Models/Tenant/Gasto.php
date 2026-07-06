<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class Gasto extends Model
{
    use TenantConnection;

    protected $fillable = ['empresa_id', 'proveedor_id', 'lote_id', 'categoria_gasto_id', 'fecha', 'descripcion', 'importe_total', 'estado_pago', 'observacion'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    public function categoriaGasto(): BelongsTo
    {
        return $this->belongsTo(CategoriaGasto::class);
    }

    public function movimientos(): MorphMany
    {
        return $this->morphMany(MovimientoCuenta::class, 'origen');
    }
}
