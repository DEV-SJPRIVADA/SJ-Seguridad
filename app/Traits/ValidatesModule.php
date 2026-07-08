<?php

namespace App\Traits;

trait ValidatesModule
{
    protected function abortIfUnknownModule(string $module): void
    {
        abort_unless(array_key_exists($module, config('access.areas', [])), 404);
    }
}
