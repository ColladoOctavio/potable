<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class MovimientoCuenta extends Model
{
    use TenantConnection;

    protected $table = 'movimientos_cuenta';

    protected $fillable = ['empresa_id', 'cuenta_id', 'fecha', 'tipo', 'concepto', 'importe', 'origen_type', 'origen_id', 'observacion'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function origen(): MorphTo
    {
        return $this->morphTo();
    }
}
