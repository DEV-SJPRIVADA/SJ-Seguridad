<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:doctor', function () {
    $adminEmail = env('ADMIN_EMAIL', 'admin@sjseguridad.local');
    $adminPassword = env('ADMIN_PASSWORD', 'ChangeMe123!');
    $checks = [];

    try {
        DB::connection()->getPdo();
        $checks[] = ['label' => 'Conexion a base de datos', 'ok' => true, 'detail' => config('database.default').' / '.config('database.connections.'.config('database.default').'.database')];
    } catch (Throwable $exception) {
        $checks[] = ['label' => 'Conexion a base de datos', 'ok' => false, 'detail' => $exception->getMessage()];
    }

    $usersTableExists = false;

    try {
        $usersTableExists = Schema::hasTable('users');
        $checks[] = ['label' => 'Tabla users', 'ok' => $usersTableExists, 'detail' => $usersTableExists ? 'Disponible' : 'No existe'];
    } catch (Throwable $exception) {
        $checks[] = ['label' => 'Tabla users', 'ok' => false, 'detail' => $exception->getMessage()];
    }

    if ($usersTableExists) {
        $admin = User::query()->where('email', $adminEmail)->first();
        $checks[] = ['label' => 'Usuario admin semilla', 'ok' => (bool) $admin, 'detail' => $adminEmail];
        $checks[] = ['label' => 'Cantidad de usuarios', 'ok' => User::query()->count() > 0, 'detail' => (string) User::query()->count()];

        if ($admin) {
            $checks[] = ['label' => 'Admin activo', 'ok' => (bool) $admin->is_active, 'detail' => $admin->is_active ? 'Si' : 'No'];
            $checks[] = ['label' => 'Admin rol super-admin', 'ok' => $admin->hasRole('super-admin'), 'detail' => $admin->roles->pluck('name')->implode(', ')];
            $checks[] = ['label' => 'Clave semilla vigente', 'ok' => Hash::check($adminPassword, $admin->password), 'detail' => 'ADMIN_PASSWORD actual'];
        }
    }

    $qualityDocsReady = Schema::hasTable('quality_documents')
        && Schema::hasTable('quality_document_areas')
        && Schema::hasTable('quality_document_users');
    $checks[] = [
        'label' => 'Tablas documentos Calidad',
        'ok' => $qualityDocsReady,
        'detail' => $qualityDocsReady
            ? 'Disponibles'
            : 'Ejecutar: php artisan migrate --path=database/migrations/2026_05_09_100000_create_quality_documents_tables.php',
    ];

    $this->newLine();
    $this->info('Diagnostico local de autenticacion');
    $this->table(
        ['Chequeo', 'Estado', 'Detalle'],
        collect($checks)->map(fn (array $check) => [
            $check['label'],
            $check['ok'] ? 'OK' : 'ERROR',
            $check['detail'],
        ])->all()
    );

    $hasErrors = collect($checks)->contains(fn (array $check) => ! $check['ok']);

    if ($hasErrors) {
        $this->warn('Se detectaron inconsistencias. Ejecuta app:stabilize-local o revisa la configuracion indicada.');

        return self::FAILURE;
    }

    $this->info('Entorno local listo para iniciar sesion.');

    return self::SUCCESS;
})->purpose('Verifica si el entorno local esta listo para autenticacion');

Artisan::command('app:restore-admin', function () {
    $this->info('Restaurando roles, permisos y administrador semilla...');

    Artisan::call('db:seed', [
        '--class' => RoleAndPermissionSeeder::class,
        '--force' => true,
    ]);

    $this->output->write(Artisan::output());

    $admin = User::query()->where('email', env('ADMIN_EMAIL', 'admin@sjseguridad.local'))->first();

    if (! $admin) {
        $this->error('No fue posible restaurar el administrador semilla.');

        return self::FAILURE;
    }

    $this->table(
        ['Campo', 'Valor'],
        [
            ['Email', $admin->email],
            ['Activo', $admin->is_active ? 'Si' : 'No'],
            ['Cambio obligatorio', $admin->must_change_password ? 'Si' : 'No'],
            ['Roles', $admin->roles->pluck('name')->implode(', ')],
        ]
    );

    $this->info('Administrador semilla restaurado correctamente.');

    return self::SUCCESS;
})->purpose('Restaura el usuario administrador semilla y los permisos base');

Artisan::command('app:stabilize-local', function () {
    $this->info('Limpiando cache de la aplicacion...');
    Artisan::call('optimize:clear');
    $this->output->write(Artisan::output());

    $this->call('app:restore-admin');

    return $this->call('app:doctor');
})->purpose('Limpia cache y restablece el estado minimo para iniciar sesion en local');
