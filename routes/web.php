<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Central\TenantSelectionController;
use App\Http\Controllers\Tenant\CrudController;
use App\Http\Controllers\Tenant\CuentaController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\EmpresaActivaController;
use App\Http\Controllers\Tenant\ReporteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/tenants', [TenantSelectionController::class, 'index'])->name('tenants.index');
    Route::post('/tenants/{tenant}/seleccionar', [TenantSelectionController::class, 'seleccionar'])->name('tenants.seleccionar');
});

Route::middleware(['auth', 'tenant.selected', 'empresa.active'])->group(function () {
    Route::post('/empresa-activa', [EmpresaActivaController::class, 'update'])->name('empresa-activa.update');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    foreach (['empresas', 'lotes', 'clientes', 'proveedores', 'categorias-gastos'] as $resource) {
        Route::get("/{$resource}", [CrudController::class, 'index'])->defaults('resource', $resource)->name("{$resource}.index");
        Route::get("/{$resource}/crear", [CrudController::class, 'create'])->defaults('resource', $resource)->name("{$resource}.create");
        Route::post("/{$resource}", [CrudController::class, 'store'])->defaults('resource', $resource)->name("{$resource}.store");
        Route::get("/{$resource}/{id}/editar", [CrudController::class, 'edit'])->defaults('resource', $resource)->name("{$resource}.edit");
        Route::put("/{$resource}/{id}", [CrudController::class, 'update'])->defaults('resource', $resource)->name("{$resource}.update");
        Route::delete("/{$resource}/{id}", [CrudController::class, 'destroy'])->defaults('resource', $resource)->name("{$resource}.destroy");
    }

    Route::view('/gastos', 'tenant.gastos.index')->name('gastos.index');
    Route::view('/ventas', 'tenant.ventas.index')->name('ventas.index');
    Route::view('/movimientos-cuenta', 'tenant.movimientos.index')->name('movimientos-cuenta.index');
    Route::get('/cuenta', [CuentaController::class, 'show'])->name('cuenta.show');

    Route::get('/reportes/general', [ReporteController::class, 'general'])->name('reportes.general');
    Route::get('/reportes/ventas', [ReporteController::class, 'ventas'])->name('reportes.ventas');
    Route::get('/reportes/gastos', [ReporteController::class, 'gastos'])->name('reportes.gastos');
    Route::get('/reportes/clientes', [ReporteController::class, 'clientes'])->name('reportes.clientes');
    Route::get('/reportes/proveedores', [ReporteController::class, 'proveedores'])->name('reportes.proveedores');
    Route::get('/reportes/lotes', [ReporteController::class, 'lotes'])->name('reportes.lotes');
});
