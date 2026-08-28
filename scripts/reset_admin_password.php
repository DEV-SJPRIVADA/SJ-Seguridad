<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$user = User::query()->where('email', 'admin@sjseguridad.local')->first();

if (! $user) {
    fwrite(STDERR, "missing user\n");
    exit(1);
}

$user->update([
    'password' => Hash::make('Admin12345!'),
    'is_active' => true,
    'must_change_password' => false,
]);

fwrite(STDOUT, "password reset OK\n");
