<?php

namespace App\Services\Tenant\Ventas;

use App\Models\Tenant\Cliente;
use Illuminate\Support\Str;

class VentaMissingClientCreator
{
    /**
     * @param  array<int, string>  $clientNames
     * @return array<int, Cliente>
     */
    public function createMissing(array $clientNames, int $empresaId): array
    {
        $created = [];

        foreach ($this->uniqueNames($clientNames) as $clientName) {
            if ($this->exists($empresaId, $clientName)) {
                continue;
            }

            $created[] = Cliente::create([
                'empresa_id' => $empresaId,
                'nombre' => $clientName,
                'saldo_inicial' => 0,
            ]);
        }

        return $created;
    }

    private function exists(int $empresaId, string $clientName): bool
    {
        $needle = $this->normalizeName($clientName);

        return Cliente::where('empresa_id', $empresaId)
            ->get(['nombre'])
            ->contains(fn (Cliente $cliente) => $this->normalizeName($cliente->nombre) === $needle);
    }

    /**
     * @param  array<int, string>  $clientNames
     * @return array<int, string>
     */
    private function uniqueNames(array $clientNames): array
    {
        $names = [];

        foreach ($clientNames as $clientName) {
            $normalized = $this->normalizeName($clientName);

            if ($normalized === '' || isset($names[$normalized])) {
                continue;
            }

            $names[$normalized] = trim($clientName);
        }

        return array_values($names);
    }

    private function normalizeName(string $name): string
    {
        $name = Str::ascii(trim($name));
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return mb_strtolower($name);
    }
}
