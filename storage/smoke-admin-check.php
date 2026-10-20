<?php
// Smoke helper (se elimina despues): estado del usuario admin local para login UI.
use App\Models\User;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$admin = User::query()->where('email', env('ADMIN_EMAIL', 'admin@sjseguridad.local'))->first();

if (! $admin) {
    echo 'NO ADMIN';

    return;
}

echo $admin->email
    .' | activo='.($admin->is_active ? '1' : '0')
   .' | must_change='.($admin->must_change_password ? '1' : '0')
    .' | seleccion_view='.($admin->can('seleccion.view') ? '1' : '0')
    .' | seleccion_edit='.($admin->can('seleccion.edit') ? '1' : '0')
    .' | board='.($admin->can('view.board.gestion_humana.seleccion') ? '1' : '0');
