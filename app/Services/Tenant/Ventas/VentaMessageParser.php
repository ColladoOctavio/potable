<?php

namespace App\Services\Tenant\Ventas;

use Carbon\CarbonImmutable;

class VentaMessageParser
{
    public function parse(string $message, ?int $defaultYear = null): ParsedVentaMessage
    {
        $lineas = $this->nonEmptyLines($message);
        $errores = [];

        if ($lineas === []) {
            return new ParsedVentaMessage(null, [], ['El mensaje esta vacio.']);
        }

        $fecha = $this->parseDate($lineas[0]['text'], $defaultYear ?? (int) CarbonImmutable::now()->year, $lineas[0]['number'], $errores);
        $ventas = [];

        foreach (array_slice($lineas, 1) as $linea) {
            $venta = $this->parseSaleLine($linea['text'], $linea['number'], $errores);

            if ($venta) {
                $ventas[] = $venta;
            }
        }

        if (count($lineas) === 1) {
            $errores[] = 'Agrega al menos una venta debajo de la fecha.';
        }

        return new ParsedVentaMessage($fecha, $ventas, $errores);
    }

    /**
     * @return array<int, array{number:int, text:string}>
     */
    private function nonEmptyLines(string $message): array
    {
        $lines = preg_split('/\R/u', $message) ?: [];
        $result = [];

        foreach ($lines as $index => $line) {
            $line = trim($line);

            if ($line !== '') {
                $result[] = ['number' => $index + 1, 'text' => $line];
            }
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $errores
     */
    private function parseDate(string $text, int $defaultYear, int $lineNumber, array &$errores): ?CarbonImmutable
    {
        if (! preg_match('/^(?<day>\d{1,2})[\/.-](?<month>\d{1,2})(?:[\/.-](?<year>\d{2,4}))?$/', $text, $matches)) {
            $errores[] = "Linea {$lineNumber}: la fecha debe ser como 1/5 o 01/05/2026.";

            return null;
        }

        $year = isset($matches['year']) && $matches['year'] !== ''
            ? (int) $matches['year']
            : $defaultYear;

        if ($year < 100) {
            $year += 2000;
        }

        try {
            return CarbonImmutable::createSafe($year, (int) $matches['month'], (int) $matches['day'])->startOfDay();
        } catch (\Throwable) {
            $errores[] = "Linea {$lineNumber}: la fecha no es valida.";

            return null;
        }
    }

    /**
     * @param  array<int, string>  $errores
     */
    private function parseSaleLine(string $text, int $lineNumber, array &$errores): ?ParsedVentaLine
    {
        $pattern = '/^(?<bolsas>\d+(?:[.,]\d+)?)\s*(?:bolsas?|b)?\s+(?:a\s*)?\$?\s*(?<precio>\d[\d.,]*)\s+(?<cliente>.+)$/iu';

        if (! preg_match($pattern, $text, $matches)) {
            $errores[] = "Linea {$lineNumber}: no pude leer la venta. Usa: 1700 bolsas \$8500 Marcos.";

            return null;
        }

        $bolsas = $this->parseNumber($matches['bolsas']);
        $precio = $this->parseNumber($matches['precio']);
        $cliente = trim($matches['cliente']);

        if ($bolsas === null || $bolsas <= 0) {
            $errores[] = "Linea {$lineNumber}: la cantidad de bolsas debe ser mayor a 0.";

            return null;
        }

        if ($precio === null || $precio < 0) {
            $errores[] = "Linea {$lineNumber}: el precio por bolsa no es valido.";

            return null;
        }

        if ($cliente === '') {
            $errores[] = "Linea {$lineNumber}: falta el nombre del cliente.";

            return null;
        }

        return new ParsedVentaLine($lineNumber, $text, $bolsas, $precio, $cliente);
    }

    private function parseNumber(string $value): ?float
    {
        $value = preg_replace('/[^\d.,-]/', '', trim($value));

        if ($value === null || $value === '') {
            return null;
        }

        $commaPosition = strrpos($value, ',');
        $dotPosition = strrpos($value, '.');

        if ($commaPosition !== false && $dotPosition !== false) {
            $decimalSeparator = $commaPosition > $dotPosition ? ',' : '.';
            $thousandsSeparator = $decimalSeparator === ',' ? '.' : ',';
            $value = str_replace($thousandsSeparator, '', $value);
            $value = str_replace($decimalSeparator, '.', $value);
        } elseif ($commaPosition !== false) {
            $value = $this->normalizeSingleSeparatorNumber($value, ',');
        } elseif ($dotPosition !== false) {
            $value = $this->normalizeSingleSeparatorNumber($value, '.');
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function normalizeSingleSeparatorNumber(string $value, string $separator): string
    {
        $parts = explode($separator, $value);

        if (count($parts) > 2) {
            return $this->hasThousandsGroups($parts) ? implode('', $parts) : $value;
        }

        [$first, $last] = $parts;

        if (strlen($last) === 3 && strlen($first) <= 3) {
            return $first.$last;
        }

        return $separator === ',' ? $first.'.'.$last : $value;
    }

    /**
     * @param  array<int, string>  $parts
     */
    private function hasThousandsGroups(array $parts): bool
    {
        if ($parts[0] === '' || strlen($parts[0]) > 3) {
            return false;
        }

        foreach (array_slice($parts, 1) as $part) {
            if (strlen($part) !== 3) {
                return false;
            }
        }

        return true;
    }
}
