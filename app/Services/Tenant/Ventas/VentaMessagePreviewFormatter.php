<?php

namespace App\Services\Tenant\Ventas;

class VentaMessagePreviewFormatter
{
    public function format(VentaMessageResolution $resolution, ?string $empresaNombre = null): string
    {
        if ($resolution->parsed->errores !== []) {
            return $this->formatErrors($resolution->parsed->errores);
        }

        if ($resolution->missingLines() !== []) {
            return $this->formatMissingClients($resolution, $empresaNombre);
        }

        return $this->formatReady($resolution, $empresaNombre);
    }

    /**
     * @param  array<int, string>  $errores
     */
    private function formatErrors(array $errores): string
    {
        return "No pude leer el mensaje completo:\n\n"
            .implode("\n", array_map(fn (string $error) => "- {$error}", $errores));
    }

    private function formatMissingClients(VentaMessageResolution $resolution, ?string $empresaNombre): string
    {
        $message = $this->empresaHeader($empresaNombre)."Encontré ventas, pero faltan clientes cargados:\n\n";

        foreach ($resolution->missingClientNames() as $clientName) {
            $message .= "- {$clientName}\n";
        }

        if ($resolution->readyLines() !== []) {
            $message .= "\nVentas que sí reconocí:\n";

            foreach ($resolution->readyLines() as $line) {
                $message .= '- '.$this->formatLine($line)."\n";
            }
        }

        return rtrim($message)."\n\nNo cargue ninguna venta. Responde SI para crear esos clientes y cargar las ventas, o NO para cancelar.";
    }

    private function formatReady(VentaMessageResolution $resolution, ?string $empresaNombre): string
    {
        $fecha = $resolution->parsed->fecha?->format('d/m/Y') ?? '-';
        $message = $this->empresaHeader($empresaNombre)."Ventas listas para cargar ({$fecha}):\n\n";

        foreach ($resolution->readyLines() as $line) {
            $message .= '- '.$this->formatLine($line)."\n";
        }

        $message .= "\nTotal bolsas: ".$this->formatQuantity($resolution->totalBolsas());
        $message .= "\nTotal: ".$this->formatMoney($resolution->totalImporte());
        $message .= "\n\nRespondé SI para cargar o NO para cancelar.";

        return $message;
    }

    private function formatLine(ResolvedVentaLine $line): string
    {
        $clientName = $line->clienteNombre ?? $line->parsed->clienteNombre;

        return $clientName.': '
            .$this->formatQuantity($line->parsed->bolsas)
            .' bolsas x '
            .$this->formatMoney($line->parsed->precioPorBolsa)
            .' = '
            .$this->formatMoney($line->importeTotal());
    }

    private function empresaHeader(?string $empresaNombre): string
    {
        return $empresaNombre ? "Empresa: {$empresaNombre}\n\n" : '';
    }

    private function formatMoney(float $value): string
    {
        return '$ '.number_format($value, 2, ',', '.');
    }

    private function formatQuantity(float $value): string
    {
        if (floor($value) === $value) {
            return number_format($value, 0, ',', '.');
        }

        return number_format($value, 2, ',', '.');
    }
}
