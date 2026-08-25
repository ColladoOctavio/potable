<?php

namespace App\Services\Tenant\Ventas;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class VentaMessagePendingPayload
{
    public function fromResolution(VentaMessageResolution $resolution, ?int $loteId = null, ?float $pesoBolsaKg = null): array
    {
        if (! $resolution->isReady()) {
            throw new InvalidArgumentException('Solo se puede guardar una accion pendiente con ventas listas.');
        }

        return [
            'fecha' => $resolution->parsed->fecha?->toDateString(),
            'lote_id' => $loteId,
            'peso_bolsa_kg' => $pesoBolsaKg,
            'lineas' => array_map(fn (ResolvedVentaLine $line) => [
                'line_number' => $line->parsed->lineNumber,
                'raw' => $line->parsed->raw,
                'cliente_id' => $line->clienteId,
                'cliente_nombre' => $line->clienteNombre,
                'bolsas' => $line->parsed->bolsas,
                'precio_por_bolsa' => $line->parsed->precioPorBolsa,
            ], $resolution->readyLines()),
        ];
    }

    public function fromMissingResolution(VentaMessageResolution $resolution, ?int $loteId = null, ?float $pesoBolsaKg = null): array
    {
        if (! $resolution->parsed->isValid() || $resolution->missingLines() === []) {
            throw new InvalidArgumentException('Solo se puede guardar una accion pendiente con clientes faltantes.');
        }

        return [
            'fecha' => $resolution->parsed->fecha?->toDateString(),
            'lote_id' => $loteId,
            'peso_bolsa_kg' => $pesoBolsaKg,
            'missing_client_names' => $resolution->missingClientNames(),
            'lineas' => array_map(fn (ParsedVentaLine $line) => [
                'line_number' => $line->lineNumber,
                'raw' => $line->raw,
                'cliente_nombre' => $line->clienteNombre,
                'bolsas' => $line->bolsas,
                'precio_por_bolsa' => $line->precioPorBolsa,
            ], $resolution->parsed->lineas),
        ];
    }

    public function toResolution(array $payload): VentaMessageResolution
    {
        $parsedLines = [];
        $resolvedLines = [];

        foreach ($payload['lineas'] ?? [] as $line) {
            $parsedLine = $this->parsedLineFromPayload($line);

            $parsedLines[] = $parsedLine;
            $resolvedLines[] = new ResolvedVentaLine(
                parsed: $parsedLine,
                clienteId: (int) $line['cliente_id'],
                clienteNombre: (string) $line['cliente_nombre'],
            );
        }

        return new VentaMessageResolution(
            parsed: new ParsedVentaMessage($this->fechaFromPayload($payload), $parsedLines, []),
            lineas: $resolvedLines,
        );
    }

    public function toParsedMessage(array $payload): ParsedVentaMessage
    {
        return new ParsedVentaMessage(
            fecha: $this->fechaFromPayload($payload),
            lineas: array_map(fn (array $line) => $this->parsedLineFromPayload($line), $payload['lineas'] ?? []),
            errores: [],
        );
    }

    private function fechaFromPayload(array $payload): CarbonImmutable
    {
        return CarbonImmutable::parse($payload['fecha'])->startOfDay();
    }

    private function parsedLineFromPayload(array $line): ParsedVentaLine
    {
        return new ParsedVentaLine(
            lineNumber: (int) $line['line_number'],
            raw: (string) $line['raw'],
            bolsas: (float) $line['bolsas'],
            precioPorBolsa: (float) $line['precio_por_bolsa'],
            clienteNombre: (string) $line['cliente_nombre'],
        );
    }
}
