<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisition;
use App\Models\PersonalRequisitionFichaEntry;
use App\Support\SpanishNameEncodingFixer;
use Illuminate\Support\Facades\Schema;

/**
 * Corrige nombres con `?` (Ñ/Ó perdidos) en ficha y entradas asociadas.
 */
class EmployeeFichaNameEncodingHealService
{
    /**
     * @return array{scanned: int, updated_profiles: int, updated_entries: int, updated_requisitions: int}
     */
    public function heal(?int $limit = null): array
    {
        $stats = [
            'scanned' => 0,
            'updated_profiles' => 0,
            'updated_entries' => 0,
            'updated_requisitions' => 0,
        ];

        $profileQuery = EmployeeFichaProfile::query()
            ->where(function ($query): void {
                $query->where('full_name', 'like', '%?%')
                    ->orWhere('first_name', 'like', '%?%')
                    ->orWhere('second_name', 'like', '%?%')
                    ->orWhere('first_surname', 'like', '%?%')
                    ->orWhere('second_surname', 'like', '%?%');
            })
            ->orderBy('id');

        if ($limit !== null) {
            $profileQuery->limit($limit);
        }

        foreach ($profileQuery->cursor() as $profile) {
            $stats['scanned']++;
            $payload = $this->fixedNamePayload([
                'full_name' => $profile->full_name,
                'first_name' => $profile->first_name,
                'second_name' => $profile->second_name,
                'first_surname' => $profile->first_surname,
                'second_surname' => $profile->second_surname,
            ]);

            if ($payload === []) {
                continue;
            }

            $profile->update($payload);
            $stats['updated_profiles']++;
        }

        if (Schema::hasTable('personal_requisition_ficha_entries')) {
            $entryQuery = PersonalRequisitionFichaEntry::query()
                ->where(function ($query): void {
                    $query->where('hired_full_name', 'like', '%?%')
                        ->orWhere('first_name', 'like', '%?%')
                        ->orWhere('second_name', 'like', '%?%')
                        ->orWhere('first_surname', 'like', '%?%')
                        ->orWhere('second_surname', 'like', '%?%');
                })
                ->orderBy('id');

            if ($limit !== null) {
                $entryQuery->limit($limit);
            }

            foreach ($entryQuery->cursor() as $entry) {
                $payload = $this->fixedNamePayload([
                    'hired_full_name' => $entry->hired_full_name,
                    'first_name' => $entry->first_name,
                    'second_name' => $entry->second_name,
                    'first_surname' => $entry->first_surname,
                    'second_surname' => $entry->second_surname,
                ], fullNameKey: 'hired_full_name');

                if ($payload === []) {
                    continue;
                }

                $entry->update($payload);
                $stats['updated_entries']++;
            }
        }

        if (Schema::hasTable('personal_requisitions') && Schema::hasColumn('personal_requisitions', 'hired_full_name')) {
            $reqQuery = PersonalRequisition::query()
                ->where('hired_full_name', 'like', '%?%')
                ->orderBy('id');

            if ($limit !== null) {
                $reqQuery->limit($limit);
            }

            foreach ($reqQuery->cursor() as $requisition) {
                $fixed = SpanishNameEncodingFixer::fixPersonName((string) $requisition->hired_full_name);
                if ($fixed === (string) $requisition->hired_full_name) {
                    continue;
                }

                $requisition->update(['hired_full_name' => $fixed]);
                $stats['updated_requisitions']++;
            }
        }

        return $stats;
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, string|null>
     */
    private function fixedNamePayload(array $fields, string $fullNameKey = 'full_name'): array
    {
        $payload = [];

        foreach ($fields as $key => $value) {
            if (! is_string($value) || $value === '' || ! str_contains($value, '?')) {
                continue;
            }

            $fixed = SpanishNameEncodingFixer::fixPersonName($value);
            if ($fixed !== $value) {
                $payload[$key] = $fixed;
            }
        }

        if ($payload === []) {
            return [];
        }

        // Si se corrigieron partes y no full_name, recomponer full_name cuando haya partes.
        if (! array_key_exists($fullNameKey, $payload)) {
            $parts = [
                $payload['first_surname'] ?? $fields['first_surname'] ?? null,
                $payload['second_surname'] ?? $fields['second_surname'] ?? null,
                $payload['first_name'] ?? $fields['first_name'] ?? null,
                $payload['second_name'] ?? $fields['second_name'] ?? null,
            ];
            $composed = trim(implode(' ', array_filter(
                $parts,
                static fn (mixed $part): bool => is_string($part) && $part !== '',
            )));

            if ($composed !== '' && str_contains((string) ($fields[$fullNameKey] ?? ''), '?')) {
                $payload[$fullNameKey] = SpanishNameEncodingFixer::fixPersonName(
                    (string) ($fields[$fullNameKey] ?? $composed)
                );
            }
        }

        return $payload;
    }
}
