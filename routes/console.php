<?php

use App\Models\User;
use App\Services\GestionHumana\EmployeeCursoEscuelaBackfillService;
use App\Services\GestionHumana\EmployeeFichaEmploymentPeriodService;
use App\Services\GestionHumana\EmployeeFichaNameEncodingHealService;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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

Artisan::command('app:sync-permissions', function () {
    $this->info('Sincronizando permisos desde config/access.php...');

    $result = PermissionCatalog::sync();

    $superAdminRole = Role::findOrCreate('super-admin', 'web');
    $allPermissions = Permission::query()->pluck('name')->all();
    $superAdminRole->syncPermissions($allPermissions);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->table(
        ['Metrica', 'Valor'],
        [
            ['Permisos configurados', (string) $result['synced']],
            ['Permisos huerfanos eliminados', (string) $result['deleted']],
            ['Permisos en super-admin', (string) count($allPermissions)],
        ]
    );

    $this->info('Permisos sincronizados. Rol super-admin actualizado con el catalogo completo.');

    return self::SUCCESS;
})->purpose('Sincroniza permisos desde config y actualiza rol super-admin');

Artisan::command('cursos:backfill-escuelas {--limit= : Maximo de filas a escanear}', function () {
    $limitOption = $this->option('limit');
    $limit = $limitOption !== null && $limitOption !== '' ? max(1, (int) $limitOption) : null;

    /** @var EmployeeCursoEscuelaBackfillService $service */
    $service = app(EmployeeCursoEscuelaBackfillService::class);
    $stats = $service->backfill($limit);

    $this->info('Backfill escuelas desde No.CURSO + catalogo Escuelas');
    $this->table(
        ['Metrica', 'Valor'],
        [
            ['Escaneados', (string) $stats['scanned']],
            ['Actualizados', (string) $stats['updated']],
            ['Sin codigo en No.CURSO', (string) $stats['skipped_no_codigo']],
            ['Codigo no en catalogo', (string) $stats['skipped_no_catalog']],
            ['Ya tenian escuela', (string) $stats['skipped_already_filled']],
        ]
    );

    return self::SUCCESS;
})->purpose('Completa codigo/NIT/nombre de escuela en registros de curso desde No.CURSO');

Artisan::command('ficha:backfill-active-periods {--limit= : Maximo de perfiles a escanear} {--user= : ID usuario opened_by}', function () {
    $limitOption = $this->option('limit');
    $limit = $limitOption !== null && $limitOption !== '' ? max(1, (int) $limitOption) : null;
    $userId = max(0, (int) $this->option('user'));

    /** @var EmployeeFichaEmploymentPeriodService $service */
    $service = app(EmployeeFichaEmploymentPeriodService::class);
    $stats = $service->backfillMissingActivePeriods($limit, $userId);

    $this->info('Backfill periodos activos para perfiles activo sin vinculo abierto');
    $this->table(
        ['Metrica', 'Valor'],
        [
            ['Escaneados', (string) $stats['scanned']],
            ['Periodos abiertos', (string) $stats['opened']],
            ['Omitidos', (string) $stats['skipped']],
            ['Fallidos', (string) $stats['failed']],
        ]
    );

    return self::SUCCESS;
})->purpose('Abre periodo laboral para empleados activo en ficha sin periodo abierto');

Artisan::command('ficha:fix-name-encoding {--limit= : Maximo de registros a escanear por tabla}', function () {
    $limitOption = $this->option('limit');
    $limit = $limitOption !== null && $limitOption !== '' ? max(1, (int) $limitOption) : null;

    /** @var EmployeeFichaNameEncodingHealService $service */
    $service = app(EmployeeFichaNameEncodingHealService::class);
    $stats = $service->heal($limit);

    $this->info('Reparacion de nombres con ? (Ñ/Ó perdido por encoding)');
    $this->table(
        ['Metrica', 'Valor'],
        [
            ['Perfiles escaneados', (string) $stats['scanned']],
            ['Perfiles actualizados', (string) $stats['updated_profiles']],
            ['Entradas ficha actualizadas', (string) $stats['updated_entries']],
            ['Requisiciones actualizadas', (string) $stats['updated_requisitions']],
        ]
    );

    return self::SUCCESS;
})->purpose('Corrige nombres de empleados con ? (MU?OZ→MUÑOZ, LE?N→LEÓN)');
