<?php

use App\Http\Middleware\EnsureIndicadorAccess;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\EnsurePurchaseTabAccess;
use App\Http\Middleware\EnsureRequisitionTabAccess;
use App\Http\Middleware\EnsureSupplyTabAccess;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('comercial:send-documentation-notification-digest')
            ->dailyAt('06:00')
            ->timezone('America/Bogota');

        $schedule->command('comercial:send-service-contract-notification-digest')
            ->dailyAt('06:00')
            ->timezone('America/Bogota');

        $schedule->command('audit:purge --force')
            ->monthlyOn(1, '03:00')
            ->timezone('America/Bogota');
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'password.changed' => EnsurePasswordIsChanged::class,
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'supply.tab' => EnsureSupplyTabAccess::class,
            'purchase.tab' => EnsurePurchaseTabAccess::class,
            'requisition.tab' => EnsureRequisitionTabAccess::class,
            'indicador.tab' => EnsureIndicadorAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tu sesion expiro. Inicia sesion nuevamente.',
                ], 419);
            }

            return redirect()
                ->guest(route('login'))
                ->with('status', 'Tu sesion expiro. Por seguridad, inicia sesion nuevamente.');
        });
    })->create();
