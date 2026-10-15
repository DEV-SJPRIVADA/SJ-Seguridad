<?php

namespace App\Http\Requests\Concerns;

use App\Support\UppercaseFormText;

/**
 * Tras validar, normaliza textos a mayúsculas en `validated()` e `input()`/`string()`.
 */
trait UppercasesValidatedText
{
    /**
     * Claves adicionales a excluir (además de UppercaseFormText::DEFAULT_EXCEPT).
     *
     * @return list<string>
     */
    protected function uppercaseExceptKeys(): array
    {
        return [];
    }

    protected function passedValidation(): void
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validator->validated();

        $this->merge(UppercaseFormText::apply($validated, $this->uppercaseExceptKeys()));
    }

    /**
     * @param  array-key|null  $key
     * @param  mixed  $default
     * @return ($key is null ? array<string, mixed> : mixed)
     */
    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if ($key === null && is_array($validated)) {
            return UppercaseFormText::apply($validated, $this->uppercaseExceptKeys());
        }

        if (is_string($validated) && is_string($key) && $this->shouldUppercaseKey($key)) {
            return UppercaseFormText::uppercaseString($validated);
        }

        return $validated;
    }

    private function shouldUppercaseKey(string $key): bool
    {
        $keyLower = mb_strtolower($key);

        if (str_ends_with($keyLower, '_code') || str_contains($keyLower, 'email')) {
            return false;
        }

        foreach ([...UppercaseFormText::DEFAULT_EXCEPT, ...$this->uppercaseExceptKeys()] as $except) {
            if ($keyLower === mb_strtolower((string) $except)) {
                return false;
            }
        }

        return true;
    }
}
