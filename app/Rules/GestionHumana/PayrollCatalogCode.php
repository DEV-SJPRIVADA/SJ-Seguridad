<?php

namespace App\Rules\GestionHumana;

use App\Models\PayrollCatalogItem;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PayrollCatalogCode implements ValidationRule
{
    public function __construct(
        private readonly string $catalogType,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $code = trim((string) $value);

        if ($code === '') {
            return;
        }

        $exists = PayrollCatalogItem::query()
            ->ofType($this->catalogType)
            ->where('code', $code)
            ->exists();

        if (! $exists) {
            $fail('El valor seleccionado no es valido para el catalogo '.$this->catalogType.'.');
        }
    }
}
