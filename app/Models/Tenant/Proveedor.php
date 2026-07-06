<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class Proveedor extends Model
{
    use TenantConnection;

    protected $table = 'proveedores';

    protected $fillable = ['empresa_id', 'nombre', 'cuit', 'telefono', 'email', 'direccion', 'saldo_inicial', 'observacion'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class);
    }
}
