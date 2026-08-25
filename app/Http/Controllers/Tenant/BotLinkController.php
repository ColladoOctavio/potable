<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\BotUserLink;
use App\Services\Central\Bots\BotLinkCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class BotLinkController extends Controller
{
    public function show(Request $request): View
    {
        $tenantId = $request->session()->get('tenant_id');
        $empresaId = $request->session()->get('empresa_id');
        $links = BotUserLink::query()
            ->with('user')
            ->where('channel', 'telegram')
            ->where('tenant_id', $tenantId)
            ->where('empresa_id', $empresaId)
            ->where('active', true);

        return view('tenant.bots.telegram', [
            'activeLink' => (clone $links)
                ->where('user_id', $request->user()->id)
                ->latest('linked_at')
                ->first(),
            'linkingDisabledForAdmin' => $request->user()->isPlatformAdmin(),
            'otherActiveLinks' => $request->user()->isPlatformAdmin()
                ? (clone $links)->where('user_id', '!=', $request->user()->id)->latest('linked_at')->get()
                : collect(),
            'generatedCode' => $request->session()->get('bot_link_code'),
            'expiresAt' => $request->session()->get('bot_link_expires_at'),
        ]);
    }

    public function store(Request $request, BotLinkCodeService $codes): RedirectResponse
    {
        if ($request->user()->isPlatformAdmin()) {
            return redirect()
                ->route('bots.telegram.index')
                ->with('bot_link_error', 'Los administradores de PoTable no pueden vincular Telegram desde la interfaz. Ingresá con la cuenta del cliente para generar el código.');
        }

        if (RateLimiter::tooManyAttempts($this->linkCodeThrottleKey($request), 5)) {
            $seconds = RateLimiter::availableIn($this->linkCodeThrottleKey($request));

            return redirect()
                ->route('bots.telegram.index')
                ->with('bot_link_error', "Generaste demasiados códigos. Probá de nuevo en {$seconds} segundos.");
        }

        RateLimiter::hit($this->linkCodeThrottleKey($request), 600);

        $issue = $codes->createCode(
            channel: 'telegram',
            userId: $request->user()->id,
            tenantId: (string) $request->session()->get('tenant_id'),
            empresaId: (int) $request->session()->get('empresa_id'),
        );

        return redirect()
            ->route('bots.telegram.index')
            ->with('bot_link_code', $issue->code)
            ->with('bot_link_expires_at', $issue->linkCode->expires_at?->format('d/m/Y H:i'));
    }

    private function linkCodeThrottleKey(Request $request): string
    {
        return implode('|', [
            'bot-link-code',
            $request->user()->id,
            $request->session()->get('tenant_id'),
            $request->session()->get('empresa_id'),
        ]);
    }
}
