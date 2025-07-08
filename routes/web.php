<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\MedicionesController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\GrupoTarifarioController;
use App\Http\Controllers\TarifasController;
use App\Http\Controllers\AreadeCargaController;
use App\Http\Controllers\BenchmarkController;
use App\Http\Controllers\configController; // Asegúrate de que el nombre de la clase sea correcto (configController o ConfigController)
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnergyflowController;
use App\Http\Controllers\GraphController;
use App\Http\Controllers\GroupsController;
use App\Http\Controllers\HeatmapController;
use App\Http\Controllers\inicioController;
use App\Http\Controllers\ManageController;
use App\Http\Controllers\optimizeController;
use App\Http\Controllers\PermisosUserController;
use App\Http\Controllers\SiteAlertsController;
use App\Http\Controllers\TiggersController;
use App\Http\Controllers\visualizeController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\PreferenciasController;
use App\Http\Controllers\WidgetController;
use App\Http\Controllers\PerfilController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\Admin; // Importa el middleware
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ApiDataController;

// Rutas públicas
Route::get('/', function () {
    return view('auth.login');
})->name('login');

// Ruta para el dashboard (requiere autenticación y verificación)
Route::get('/dashboard', function () {
    return view('inicio');
})->middleware(['auth', 'verified'])->name('dashboard');

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
    Route::get('/panels', [PanelController::class, 'index'])->name('panels.index');
    Route::post('/panels/save', [PanelController::class, 'save'])->name('panels.save');
    Route::get('/panels/get', [PanelController::class, 'get'])->name('panels.get');

    // CRUD de panels y rutas para widgets
    Route::get('/panels/create', [PanelController::class, 'create'])->name('panels.create');
    Route::post('/panels', [PanelController::class, 'store'])->name('panels.store');
    Route::get('/panels/{panel}', [PanelController::class, 'edit'])->name('panels.edit');
    Route::put('/panels/{panel}', [PanelController::class, 'update'])->name('panels.update');
    Route::delete('/panels/{panel}', [PanelController::class, 'destroy'])->name('panels.destroy');

    Route::post('/panels/{panel}/widgets', [WidgetController::class, 'store'])->name('widgets.store');
    Route::put('/panels/{panel}/widgets/{widget}', [WidgetController::class, 'update'])->name('widgets.update');
    Route::delete('/panels/{panel}/widgets/{widget}', [WidgetController::class, 'destroy'])->name('widgets.destroy');

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
    Route::get('/site_alerts', [SiteAlertsController::class, 'index'])->name('site_alerts');
    Route::get('/tiggers', [TiggersController::class, 'index'])->name('tiggers');
    Route::get('/benchmark', [BenchmarkController::class, 'index'])->name('benchmark');
    Route::get('/manage', [ManageController::class, 'index'])->name('manage');
    Route::get('/energyflow', [EnergyflowController::class, 'index'])->name('energyflow');
    Route::get('/permisosuser', [PermisosUserController::class, 'index'])->name('permisosuser');
    Route::get('/preferencias', [PreferenciasController::class, 'index'])->name('preferencias');
    Route::get('/perfil', [PerfilController::class, 'index'])->name('perfil');
    Route::post('/{cliente}/store-file', [ClientesController::class, 'uploadFile'])->name('clientes.store_file');
    Route::get('/clientes/{cliente}/download/{fileId}', [ClientesController::class, 'downloadFile'])->name('clientes.download_file');
    Route::get('/usuarios', [UsuarioController::class, 'usuarios'])->name('usuarios');
    Route::get('clidash', [ClientesController::class, 'clidash'])->name('clientes.clidash');
 

 
 
 
});

// Rutas exclusivas para usuarios con rol "admin"
// Estas rutas solo serán accesibles si el usuario cumple el middleware "admin"
// Rutas para clientes y configuración, protegidas por el middleware Admin
Route::group(['middleware' => ['auth', Admin::class]], function () {
    // Clientes (vista y CRUD) – solo admin
    Route::resource('clientes', ClientesController::class);
 

    // Configuración del sistema – solo admin
    Route::get('/config', [configController::class, 'index'])->name('config');
});

Route::get('/api/energy-data', [ApiDataController::class, 'obtenerInfo']);

require __DIR__.'/auth.php';
