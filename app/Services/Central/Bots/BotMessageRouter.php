<?php

namespace App\Services\Central\Bots;

use App\Models\Central\BotUserLink;
use App\Models\Central\Tenant;
use App\Services\Tenant\Bots\BotEmpresaSelectionService;
use App\Services\Tenant\Bots\VentaBotConversationService;
use Illuminate\Support\Str;
use InvalidArgumentException;

class BotMessageRouter
{
    public function __construct(
        private readonly BotLinkCodeService $codes,
        private readonly BotUserLinkStore $links,
        private readonly BotEmpresaSelectionService $empresas,
        private readonly VentaBotConversationService $ventas,
    ) {}

    public function handleMessage(
        string $channel,
        string $externalUserId,
        string $text,
        ?string $externalUsername = null,
        ?string $externalDisplayName = null,
    ): string {
        $text = trim($text);

        if ($this->isLinkCommand($text)) {
            return $this->handleLinkCommand($channel, $externalUserId, $text, $externalUsername, $externalDisplayName);
        }

        $link = $this->links->findLinkedContext($channel, $externalUserId);

        if (! $link) {
            return "Tu cuenta de Telegram no esta vinculada a Potable.\n\nEntra a Potable, abri Bot Telegram y genera un codigo. Despues mandame /vincular CODIGO.";
        }

        $this->links->touchSeen($link);

        return $this->withTenant($link, fn () => $this->handleLinkedMessage($link, $channel, $externalUserId, $text));
    }

    private function handleLinkCommand(
        string $channel,
        string $externalUserId,
        string $text,
        ?string $externalUsername,
        ?string $externalDisplayName,
    ): string {
        $code = $this->linkCodeFrom($text);

        if (! $code) {
            return 'Usa /vincular CODIGO. El codigo se genera desde Potable.';
        }

        try {
            $link = $this->codes->consumeCode($channel, $code, $externalUserId, $externalUsername, $externalDisplayName);
        } catch (InvalidArgumentException $exception) {
            return $exception->getMessage();
        }

        return $this->withTenant($link, function () use ($link) {
            $empresa = $this->empresas->currentEmpresaName((int) $link->empresa_id) ?? 'la empresa seleccionada';
            $message = $this->linkSuccessMessage($link);

            return "{$message}\n\nEmpresa actual: {$empresa}.\n\nPodes cambiarla con /empresa.";
        });
    }

    private function linkSuccessMessage(BotUserLink $link): string
    {
        if ($link->wasRecentlyCreated) {
            return 'Listo, Telegram quedo vinculado a Potable.';
        }

        if ($link->changedTenantOnLastLink()) {
            return "Listo, actualice la vinculacion de esta cuenta de Telegram.\n\nEstaba vinculada a la cuenta de Potable ".$this->accountLabel($link->previousUserEmail, $link->previousUserId).' y ahora quedo vinculada a '.$this->accountLabel($link->linkedUserEmail, (int) $link->user_id).'.';
        }

        return 'Listo, actualice la vinculacion de esta cuenta de Telegram a Potable.';
    }

    private function accountLabel(?string $email, ?int $userId): string
    {
        if ($email !== null && $email !== '') {
            return '"'.$email.'"';
        }

        return $userId ? 'usuario #'.$userId : 'otra cuenta';
    }

    private function handleLinkedMessage(BotUserLink $link, string $channel, string $externalUserId, string $text): string
    {
        if ($this->isEmpresaCommand($text)) {
            return $this->empresas->listEmpresas($link);
        }

        if ($this->isUseEmpresaCommand($text)) {
            return $this->empresas->useEmpresa($link, $text);
        }

        $empresaNombre = $this->empresas->currentEmpresaName((int) $link->empresa_id);

        if (! $empresaNombre) {
            return 'La empresa vinculada ya no existe o no esta disponible. Envia /empresa para elegir otra.';
        }

        return $this->ventas->handleMessage(
            channel: $channel,
            externalUserId: $externalUserId,
            text: $text,
            userId: (int) $link->user_id,
            empresaId: (int) $link->empresa_id,
            empresaNombre: $empresaNombre,
        );
    }

    protected function withTenant(BotUserLink $link, callable $callback): string
    {
        $tenant = $link->tenant ?: Tenant::findOrFail($link->tenant_id);

        tenancy()->initialize($tenant);

        try {
            return $callback();
        } finally {
            tenancy()->end();
        }
    }

    private function isLinkCommand(string $text): bool
    {
        return str_starts_with($this->normalizeCommand($text), '/vincular');
    }

    private function linkCodeFrom(string $text): ?string
    {
        if (! preg_match('/^\/vincular\s+(?<code>[a-z0-9-]+)$/i', trim($text), $matches)) {
            return null;
        }

        return $matches['code'];
    }

    private function isEmpresaCommand(string $text): bool
    {
        return $this->normalizeCommand($text) === '/empresa';
    }

    private function isUseEmpresaCommand(string $text): bool
    {
        return str_starts_with($this->normalizeCommand($text), '/usar ');
    }

    private function normalizeCommand(string $text): string
    {
        return mb_strtolower(Str::ascii(trim($text)));
    }
}
