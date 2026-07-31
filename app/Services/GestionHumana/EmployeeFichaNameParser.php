<?php

namespace App\Services\GestionHumana;

class EmployeeFichaNameParser
{
    /**
     * @return array{full_name: string, first_surname: ?string, second_surname: ?string, first_name: ?string, second_name: ?string}
     */
    public static function parse(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $parts = array_values(array_filter($parts, fn (string $p): bool => $p !== ''));

        if ($parts === []) {
            return [
                'full_name' => '',
                'first_surname' => null,
                'second_surname' => null,
                'first_name' => null,
                'second_name' => null,
            ];
        }

        if (count($parts) === 1) {
            return [
                'full_name' => $parts[0],
                'first_surname' => $parts[0],
                'second_surname' => null,
                'first_name' => null,
                'second_name' => null,
            ];
        }

        if (count($parts) === 2) {
            return [
                'full_name' => implode(' ', $parts),
                'first_surname' => $parts[0],
                'second_surname' => null,
                'first_name' => $parts[1],
                'second_name' => null,
            ];
        }

        if (count($parts) === 3) {
            return [
                'full_name' => implode(' ', $parts),
                'first_surname' => $parts[0],
                'second_surname' => null,
                'first_name' => $parts[1],
                'second_name' => $parts[2],
            ];
        }

        return [
            'full_name' => implode(' ', $parts),
            'first_surname' => $parts[0],
            'second_surname' => $parts[1],
            'first_name' => $parts[2],
            'second_name' => implode(' ', array_slice($parts, 3)),
        ];
    }
}
