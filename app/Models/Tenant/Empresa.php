<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class Empresa extends Model
{
    use HasFactory, TenantConnection;

    protected $fillable = ['nombre', 'cuit', 'email', 'telefono', 'direccion', 'peso_bolsa_kg', 'observacion'];

    protected static function booted(): void
    {
        static::created(function (Empresa $empresa): void {
            $empresa->cuenta()->create([
                'nombre' => 'Cuenta principal',
                'saldo_inicial' => 0,
            ]);
        });
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class);
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function proveedores(): HasMany
    {
        return $this->hasMany(Proveedor::class);
    }

    public function categoriasGasto(): HasMany
    {
        return $this->hasMany(CategoriaGasto::class);
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function cuenta(): HasOne
    {
        return $this->hasOne(Cuenta::class);
    }
}
