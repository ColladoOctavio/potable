<?php

namespace App\Http\Controllers\Tenant;

use Illuminate\View\View;

class ReporteController
{
    public function general(): View
    {
        return view('tenant.reportes.general');
    }

    public function ventas(): View
    {
        return view('tenant.reportes.ventas');
    }

    public function gastos(): View
    {
        return view('tenant.reportes.gastos');
    }

    public function clientes(): View
    {
        return view('tenant.reportes.clientes');
    }

    public function proveedores(): View
    {
        return view('tenant.reportes.proveedores');
    }

    public function lotes(): View
    {
        return view('tenant.reportes.lotes');
    }
}
