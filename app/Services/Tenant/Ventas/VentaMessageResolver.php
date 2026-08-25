<?php

namespace App\Services\Tenant\Ventas;

use App\Models\Tenant\Cliente;
use Illuminate\Support\Str;

class VentaMessageResolver
{
    public function resolve(ParsedVentaMessage $message, int $empresaId): VentaMessageResolution
    {
        $clientes = $this->clientesIndex($empresaId);
        $lineas = [];

        foreach ($message->lineas as $linea) {
            $cliente = $clientes[$this->normalizeName($linea->clienteNombre)] ?? null;

            $lineas[] = new ResolvedVentaLine(
                parsed: $linea,
                clienteId: $cliente['id'] ?? null,
                clienteNombre: $cliente['nombre'] ?? null,
            );
        }

        return new VentaMessageResolution($message, $lineas);
    }

    /**
     * @return array<string, array{id:int, nombre:string}>
     */
    protected function clientesIndex(int $empresaId): array
    {
        return Cliente::where('empresa_id', $empresaId)
            ->get(['id', 'nombre'])
            ->mapWithKeys(fn (Cliente $cliente) => [
                $this->normalizeName($cliente->nombre) => [
                    'id' => (int) $cliente->id,
                    'nombre' => $cliente->nombre,
                ],
            ])
            ->all();
    }

    protected function normalizeName(string $name): string
    {
        $name = Str::ascii(trim($name));
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return mb_strtolower($name);
    }
}
