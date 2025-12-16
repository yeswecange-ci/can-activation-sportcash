<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'force.json' => \App\Http\Middleware\ForceJsonResponse::class,
        ]);

        // Faire confiance aux proxies (Coolify, Nginx, etc.) pour HTTPS
        $middleware->trustProxies(at: '*');
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Calculer les gagnants automatiquement toutes les 5 minutes
        $schedule->command('pronostic:calculate-winners')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
