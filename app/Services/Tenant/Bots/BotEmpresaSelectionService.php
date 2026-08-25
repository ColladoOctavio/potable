<?php

namespace App\Services\Tenant\Bots;

use App\Models\Central\BotUserLink;
use App\Models\Tenant\Empresa;
use App\Services\Central\Bots\BotUserLinkStore;
use Illuminate\Support\Str;

class BotEmpresaSelectionService
{
    public function __construct(private readonly BotUserLinkStore $links) {}

    public function listEmpresas(BotUserLink $link): string
    {
        $empresas = $this->empresas();
        $actual = $empresas->firstWhere('id', (int) $link->empresa_id);
        $message = 'Empresa actual: '.($actual?->nombre ?? 'Sin empresa')."\n\n";

        if ($empresas->isEmpty()) {
            return $message.'No hay empresas cargadas en este tenant.';
        }

        $message .= "Empresas disponibles:\n";

        foreach ($empresas->values() as $index => $empresa) {
            $message .= ($index + 1).'. '.$empresa->nombre."\n";
        }

        return rtrim($message)."\n\nRespondé /usar NUMERO para cambiar.";
    }

    public function useEmpresa(BotUserLink $link, string $text): string
    {
        $selection = $this->parseSelection($text);

        if ($selection === null) {
            return 'No pude leer que empresa queres usar. Proba con /usar 1.';
        }

        $empresas = $this->empresas()->values();
        $empresa = $empresas->get($selection - 1);

        if (! $empresa) {
            return 'No encontre esa empresa. Envia /empresa para ver las opciones.';
        }

        $this->links->changeEmpresa($link, (int) $empresa->id);

        return 'Listo. Ahora voy a cargar ventas en '.$empresa->nombre.'.';
    }

    public function currentEmpresaName(int $empresaId): ?string
    {
        return Empresa::whereKey($empresaId)->value('nombre');
    }

    protected function empresas()
    {
        return Empresa::orderBy('nombre')->get(['id', 'nombre']);
    }

    private function parseSelection(string $text): ?int
    {
        $text = mb_strtolower(Str::ascii(trim($text)));

        if (! preg_match('/^\/?usar\s+(?<number>\d+)$/', $text, $matches)) {
            return null;
        }

        return max(1, (int) $matches['number']);
    }
}
