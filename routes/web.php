<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\MedicionesController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\GrupoTarifarioController;
use App\Http\Controllers\TarifasController;
use App\Http\Controllers\AreadeCargaController;
use App\Http\Controllers\BarController;
use App\Http\Controllers\TimeSeriesController;
use App\Http\Controllers\HistogramController;
use App\Http\Controllers\ScatterController;
use App\Http\Controllers\configController; // Asegúrate de que el nombre de la clase sea correcto (configController o ConfigController)
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnergyflowController;
use App\Http\Controllers\GraphController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\GroupsController;
use App\Http\Controllers\HeatmapController;
use App\Http\Controllers\GeneralClientesController;
use App\Http\Controllers\inicioController;
use App\Http\Controllers\ManageController;
use App\Http\Controllers\optimizeController;
use App\Http\Controllers\PermisosUserController;
use App\Http\Controllers\SiteAlertsController;
use App\Http\Controllers\CFEController;
use App\Http\Controllers\SensoresController;
use App\Http\Controllers\SiteAlertsInController;
use App\Http\Controllers\TiggersController;
use App\Http\Controllers\visualizeController;
use App\Http\Controllers\PreferenciasController;
use App\Http\Controllers\WidgetController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PlotProxyController;
use App\Http\Controllers\MlProxyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AnomalyController;
use App\Http\Controllers\KpiAlertController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\Admin; // Importa el middleware
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ApiDataController;
use App\Http\Controllers\PostalCodeController;
use App\Http\Controllers\EnergyDashboardController;
use App\Http\Controllers\SitesController;

// Rutas públicas
Route::get('/', function () {
    return view('auth.login');
})->name('login');

// Ruta para el dashboard (requiere autenticación y verificación)
Route::get('/dashboard', function () {
    return redirect()->route('home');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/home', HomeController::class)->middleware(['auth', 'verified'])->name('home');

Route::get('/mi-perfil', function () {
    $user = Auth::user();

    if (!$user) {
        abort(403, 'No autenticado.');
    }

    if (!$user->cliente) {
        abort(404, 'Cliente no encontrado.');
    }

    return view('clientes.show', ['cliente' => $user->cliente]);
})->name('mi-perfil');

// Rutas accesibles para cualquier usuario autenticado (admin o usuario con permisos limitados)
Route::middleware('auth')->group(function () {
    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rutas de panels (accesibles para todos los autenticados)
    Route::get('/panels', function () {
        return view('Panels.index');
    })->name('panels.index');

    // API para widgets del panel personalizable
    Route::prefix('api/widgets')->name('widgets.')->group(function () {
        Route::get('/catalog', [WidgetController::class, 'catalog'])->name('catalog');
        Route::get('/dashboard', [WidgetController::class, 'dashboard'])->name('dashboard');
        Route::post('/attach', [WidgetController::class, 'attach'])->name('attach');
        Route::patch('/{widget}', [WidgetController::class, 'update'])->name('update');
        Route::delete('/{widget}', [WidgetController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('api/alerts')->group(function () {
        Route::get('/definitions', [KpiAlertController::class, 'definitions'])->name('alerts.definitions');
        Route::get('/', [KpiAlertController::class, 'index'])->name('alerts.index');
        Route::post('/', [KpiAlertController::class, 'store'])->name('alerts.store');
        Route::patch('/{kpiAlert}', [KpiAlertController::class, 'update'])->name('alerts.update');
        Route::delete('/{kpiAlert}', [KpiAlertController::class, 'destroy'])->name('alerts.destroy');
        Route::get('/events', [KpiAlertController::class, 'events'])->name('alerts.events');
        Route::post('/events/{event}/read', [KpiAlertController::class, 'markEvent'])->name('alerts.events.read');
        Route::post('/events/read-all', [KpiAlertController::class, 'markAllEvents'])->name('alerts.events.read-all');
    });

    // Rutas generales
    Route::resource('mediciones', MedicionesController::class);
    Route::resource('grupo_tarifarios', GrupoTarifarioController::class);
    Route::resource('tarifas', TarifasController::class);
    Route::resource('areas_carga', AreadeCargaController::class);

    Route::get('/inicio', [inicioController::class, 'index'])->name('inicio');
    Route::get('/optimize', [optimizeController::class, 'index'])->name('optimize');
    Route::get('/visualize', [visualizeController::class, 'index'])->name('visualize');
    Route::get('/groups', [GroupsController::class, 'index'])->name('groups');
    Route::get('/heatmap', [HeatmapController::class, 'index'])->name('heatmap');
    Route::get('/general_clientes', [GeneralClientesController::class, 'index'])->name('general_clientes');
    Route::get('/site_alerts', [SiteAlertsController::class, 'index'])->name('site_alerts');
    Route::get('/datos_cfe', [CFEController::class, 'index'])->name('datos_cfe');
    Route::get('/vincular_sensores', [SensoresController::class, 'index'])->name('vincular_sensores');
    Route::get('/site_alerts_in', [SiteAlertsInController::class, 'index'])->name('site_alerts_in');
    Route::get('/tiggers', [TiggersController::class, 'index'])->name('tiggers');
    Route::get('/anomaly', [AnomalyController::class, 'index'])->name('anomaly');
    Route::get('/forecast', [ForecastController::class, 'index'])->name('forecast');
    Route::get('/timeseries', [TimeSeriesController::class, 'index'])->name('timeseries');
        Route::get('/histogram', [HistogramController::class, 'index'])->name('histogram');
    Route::get('/benchmarking', [BarController::class, 'index'])->name('benchmarking');
    Route::get('/scatter', [ScatterController::class, 'index'])->name('scatter');
    Route::get('/manage', [ManageController::class, 'index'])->name('manage');
    Route::get('/energyflow', [EnergyflowController::class, 'index'])->name('energyflow');
    Route::get('/permisosuser', [PermisosUserController::class, 'index'])->name('permisosuser');
    Route::get('/preferencias', [PreferenciasController::class, 'index'])->name('preferencias');
    Route::get('/reports', fn () => view('reports'))->name('reports');
    Route::get('/perfil', [PerfilController::class, 'index'])->name('perfil');
    Route::post('/{cliente}/store-file', [ClientesController::class, 'uploadFile'])->name('clientes.store_file');
    Route::get('/clientes/{cliente}/download/{fileId}', [ClientesController::class, 'downloadFile'])->name('clientes.download_file');
    Route::get('/clientes/{cliente}/contract', [ClientesController::class, 'downloadContract'])->name('clientes.contract.download');
    Route::post('/clientes/{cliente}/contract', [ClientesController::class, 'updateContract'])->name('clientes.contract.update');
    Route::delete('/clientes/{cliente}/contract', [ClientesController::class, 'deleteContract'])->name('clientes.contract.delete');
    Route::get('/usuarios', [UsuarioController::class, 'usuarios'])->name('usuarios');
    Route::get('clidash', [ClientesController::class, 'clidash'])->name('clientes.clidash');

    Route::post('/charts/plot', [PlotProxyController::class, 'plot'])->name('charts.plot');
    Route::post('/charts/data', [PlotProxyController::class, 'data'])->name('charts.data');
    Route::post('/ml/forecast', [MlProxyController::class, 'forecast'])->name('ml.forecast');
    Route::post('/ml/anomaly-detection', [MlProxyController::class, 'anomaly'])->name('ml.anomaly');

    // En prod, el ALB/proxy puede rutear /ml/* directo al servicio Python (FastAPI),
    // saltándose Laravel y causando 403 "Not authenticated" por header faltante.
    // Usamos una ruta alternativa que siempre debe llegar a Laravel.
    Route::post('/proxy/ml/forecast', [MlProxyController::class, 'forecast'])->name('ml.proxy.forecast');
    Route::post('/proxy/ml/anomaly-detection', [MlProxyController::class, 'anomaly'])->name('ml.proxy.anomaly');
 
    Route::get('/api/postal-codes/{cp}', [PostalCodeController::class, 'lookup']);
    Route::get('/clientes', [ClientesController::class,'index'])->name('clientes.index');
    Route::post('/clientes', [ClientesController::class,'store'])->name('clientes.store');
    // status update route
    Route::post('/clientes/update-status/{cliente}', [ClientesController::class,'updateStatus']);
    Route::get('/api/postal-codes/{cp}', [PostalCodeController::class,'lookup']);

    // rutas para acciones onboarding (poner dentro del group auth si aplica)
    Route::post('clientes/{cliente}/capacitacion', [App\Http\Controllers\ClientesController::class, 'confirmCapacitacion'])
        ->name('clientes.capacitacion');

    Route::post('clientes/{cliente}/go-live', [App\Http\Controllers\ClientesController::class, 'confirmGoLive'])
        ->name('clientes.go_live');

        Route::get('vincular-sensores', [SensoresController::class, 'index'])->name('sensores.index');
Route::post('sensores/vincular', [SensoresController::class, 'store'])->name('sensores.vincular');
Route::post('sensores/vincular/bulk', [SensoresController::class, 'bulkAssign'])->name('sensores.vincular.bulk');
Route::post('sites/update-name', [SensoresController::class, 'updateSiteName'])->name('sites.updateName');
Route::post('/sensores/site/update-name', [SensoresController::class, 'updateSiteName'])
    ->name('sites.updateName');

Route::get('/cfe/region', [CFEController::class, 'getRegion']);
Route::post('/cfe/store', [CFEController::class, 'store'])->name('cfe.store');
Route::get('/cfe', [CFEController::class, 'index'])->name('cfe.index');
Route::get('/sites', [SitesController::class, 'index'])->name('sites.index');        // opcional
Route::post('/sites', [SitesController::class, 'store'])->name('sites.store');
Route::post('/sites/update-name', [SitesController::class, 'updateName'])->name('sites.updateName');
Route::delete('/sites/{siteId}', [SitesController::class, 'destroyBySiteId'])->name('sites.destroyBySiteId');

 
Route::get('/energy', [EnergyDashboardController::class, 'index'])->name('energy.dashboard');
Route::get('/energy/costs', [EnergyDashboardController::class, 'costs'])->name('energy.costs');

    Route::get('/config', [configController::class, 'index'])->name('config');
    Route::get('/config/users', [configController::class, 'manageUsers'])->name('config.users');
    Route::post('/config/users', [configController::class, 'storeUser'])->name('config.users.store');
    Route::patch('/config/users/{user}', [configController::class, 'updateUser'])->name('config.users.update');
    Route::delete('/config/users/{user}', [configController::class, 'destroyUser'])->name('config.users.destroy');
 
});

// Rutas exclusivas para usuarios con rol "admin"
// Estas rutas solo serán accesibles si el usuario cumple el middleware "admin"
// Rutas para clientes y configuración, protegidas por el middleware Admin
Route::group(['middleware' => ['auth', Admin::class]], function () {
    // Clientes (vista y CRUD) – solo admin
    Route::resource('clientes', ClientesController::class);
});

Route::get('/api/energy-data', [ApiDataController::class, 'obtenerInfo']);


// Rutas para CRUD de eventos con AJAX
Route::post('/events', [ManageController::class, 'store'])->name('events.store');
Route::get('/events/{id}', [ManageController::class, 'show'])->name('events.show');
Route::put('/events/{id}', [ManageController::class, 'update'])->name('events.update');
Route::delete('/events/{id}', [ManageController::class, 'destroy'])->name('events.destroy');

Route::get('/manage/clear', function() {
    return redirect()->route('manage');
})->name('manage.clear');


require __DIR__.'/auth.php';
