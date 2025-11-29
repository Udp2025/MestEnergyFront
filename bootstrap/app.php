<?php
use App\Console\Commands\RunKpiAlerts;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        RunKpiAlerts::class,
    ])
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('kpi:run-alerts')->everyFifteenMinutes();
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web([
            // ... otros middlewares del grupo web ...
            // Elimina o comenta la línea de Admin
            // \App\Http\Middleware\Admin::class,
 
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
