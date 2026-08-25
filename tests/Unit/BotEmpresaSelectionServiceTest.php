<?php

namespace Tests\Unit;

use App\Models\Central\BotUserLink;
use App\Services\Central\Bots\BotUserLinkStore;
use App\Services\Tenant\Bots\BotEmpresaSelectionService;
use PHPUnit\Framework\TestCase;

class BotEmpresaSelectionServiceTest extends TestCase
{
    public function test_lists_available_companies_and_current_company(): void
    {
        $message = $this->service([
            ['id' => 4, 'nombre' => 'Campo La Papa SA'],
            ['id' => 9, 'nombre' => 'Agro Andina SRL'],
        ])->listEmpresas($this->link(4));

        $this->assertStringContainsString('Empresa actual: Campo La Papa SA', $message);
        $this->assertStringContainsString('1. Campo La Papa SA', $message);
        $this->assertStringContainsString('2. Agro Andina SRL', $message);
        $this->assertStringContainsString('Respondé /usar NUMERO para cambiar.', $message);
    }

    public function test_changes_current_company_by_number(): void
    {
        $links = new FakeBotUserLinkStoreForEmpresaSelection;
        $service = $this->service([
            ['id' => 4, 'nombre' => 'Campo La Papa SA'],
            ['id' => 9, 'nombre' => 'Agro Andina SRL'],
        ], $links);
        $link = $this->link(4);

        $message = $service->useEmpresa($link, '/usar 2');

        $this->assertSame('Listo. Ahora voy a cargar ventas en Agro Andina SRL.', $message);
        $this->assertSame(9, $links->empresaId);
        $this->assertSame($link, $links->link);
    }

    public function test_rejects_invalid_company_selection(): void
    {
        $service = $this->service([
            ['id' => 4, 'nombre' => 'Campo La Papa SA'],
        ]);

        $this->assertSame('No encontre esa empresa. Envia /empresa para ver las opciones.', $service->useEmpresa($this->link(4), '/usar 2'));
        $this->assertSame('No pude leer que empresa queres usar. Proba con /usar 1.', $service->useEmpresa($this->link(4), '/usar campo'));
    }

    private function service(array $empresas, ?FakeBotUserLinkStoreForEmpresaSelection $links = null): BotEmpresaSelectionService
    {
        $links ??= new FakeBotUserLinkStoreForEmpresaSelection;

        return new class($links, $empresas) extends BotEmpresaSelectionService
        {
            public function __construct(BotUserLinkStore $links, private readonly array $empresas)
            {
                parent::__construct($links);
            }

            protected function empresas()
            {
                return collect($this->empresas)->map(fn (array $empresa) => (object) $empresa);
            }
        };
    }

    private function link(int $empresaId): BotUserLink
    {
        $link = new BotUserLink;
        $link->setRawAttributes([
            'channel' => 'telegram',
            'external_user_id' => '123',
            'user_id' => 7,
            'tenant_id' => 'cliente1',
            'empresa_id' => $empresaId,
            'active' => true,
        ], true);

        return $link;
    }
}

class FakeBotUserLinkStoreForEmpresaSelection extends BotUserLinkStore
{
    public ?BotUserLink $link = null;

    public ?int $empresaId = null;

    public function changeEmpresa(BotUserLink $link, int $empresaId): void
    {
        $this->link = $link;
        $this->empresaId = $empresaId;
    }
}
