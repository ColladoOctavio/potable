<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CuentaController extends Controller
{
    public function show(): View
    {
        return view('tenant.cuenta.show');
    }
}
