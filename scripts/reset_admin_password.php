<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::query()->where('email', 'admin@sjseguridad.local')->first();

if (! $user) {
    fwrite(STDERR, "missing user\n");
    exit(1);
}

$user->update([
    'password' => Illuminate\Support\Facades\Hash::make('Admin12345!'),
    'is_active' => true,
    'must_change_password' => false,
]);

fwrite(STDOUT, "password reset OK\n");
