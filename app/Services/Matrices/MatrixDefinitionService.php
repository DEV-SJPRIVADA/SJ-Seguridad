<?php

namespace App\Services\Matrices;

use App\Models\Matrices\Matrix;
use App\Models\Matrices\MatrixField;
use App\Models\Matrices\MatrixVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Alta, edición y publicación de la definición de una matriz.
 *
 * Publicar congela la versión: los registros siempre apuntan a la versión
 * con la que se capturaron, aunque la matriz se edite después.
 */
final class MatrixDefinitionService
{
    /**
     * Tipos disponibles en V1. El campo "tabla" queda para V1.1.
     *
     * @var list<string>
     */
    public const V1_TYPES = [
        MatrixField::TYPE_TEXT,
        MatrixField::TYPE_TEXTAREA,
        MatrixField::TYPE_NUMBER,
        MatrixField::TYPE_CURRENCY,
        MatrixField::TYPE_DATE,
        MatrixField::TYPE_SELECT,
        MatrixField::TYPE_RADIO,
        MatrixField::TYPE_CHECKBOX,
        MatrixField::TYPE_FILE,
        MatrixField::TYPE_SIGNATURE,
    ];

    /**
     * @param  array{name: string, description?: ?string, area_key: string, fields: array<int, array<string, mixed>>}  $data
     */
    public function create(User $user, array $data): Matrix
    {
        return DB::transaction(function () use ($user, $data): Matrix {
            $matrix = Matrix::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'area_key' => $data['area_key'],
                'owner_user_id' => $user->id,
                'status' => Matrix::STATUS_DRAFT,
            ]);

            $matrix->forceFill(['code' => sprintf('MX-%05d', $matrix->id)])->save();

            $version = $this->createVersion($matrix);
            $this->replaceFields($version, $data['fields']);

            return $matrix->refresh();
        });
    }

    /**
     * Guarda los cambios sobre un borrador. Si la matriz ya estaba publicada,
     * crea una versión nueva en estado borrador sin tocar la versión vigente.
     *
     * @param  array{name: string, description?: ?string, area_key: string, fields: array<int, array<string, mixed>>}  $data
     */
    public function update(Matrix $matrix, array $data): Matrix
    {
        return DB::transaction(function () use ($matrix, $data): Matrix {
            $matrix->fill([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'area_key' => $data['area_key'],
            ])->save();

            $version = $this->draftVersionFor($matrix);
            $this->replaceFields($version, $data['fields']);

            return $matrix->refresh();
        });
    }

    public function publish(Matrix $matrix, User $user): MatrixVersion
    {
        return DB::transaction(function () use ($matrix, $user): MatrixVersion {
            $version = $this->latestDraftVersion($matrix);

            if ($version === null) {
                throw ValidationException::withMessages([
                    'matrix' => 'No hay cambios pendientes por publicar.',
                ]);
            }

            if ($version->fields()->count() === 0) {
                throw ValidationException::withMessages([
                    'fields' => 'Agrega al menos un campo antes de publicar la matriz.',
                ]);
            }

            $version->forceFill([
                'status' => MatrixVersion::STATUS_PUBLISHED,
                'published_at' => now(),
                'published_by_user_id' => $user->id,
            ])->save();

            $matrix->forceFill([
                'status' => Matrix::STATUS_PUBLISHED,
                'current_version_id' => $version->id,
                'archived_at' => null,
            ])->save();

            return $version;
        });
    }

    public function archive(Matrix $matrix): void
    {
        DB::transaction(function () use ($matrix): void {
            $matrix->forceFill([
                'status' => Matrix::STATUS_ARCHIVED,
                'archived_at' => now(),
            ])->save();
        });
    }

    public function latestDraftVersion(Matrix $matrix): ?MatrixVersion
    {
        $current = $matrix->currentVersion;

        if ($current instanceof MatrixVersion && $current->isDraft()) {
            return $current;
        }

        return MatrixVersion::query()
            ->where('matrix_id', $matrix->id)
            ->where('status', MatrixVersion::STATUS_DRAFT)
            ->orderByDesc('version_number')
            ->first();
    }

    private function draftVersionFor(Matrix $matrix): MatrixVersion
    {
        return $this->latestDraftVersion($matrix) ?? $this->createVersion($matrix);
    }

    private function createVersion(Matrix $matrix): MatrixVersion
    {
        $next = (int) MatrixVersion::query()
            ->where('matrix_id', $matrix->id)
            ->max('version_number') + 1;

        return MatrixVersion::create([
            'matrix_id' => $matrix->id,
            'version_number' => $next,
            'status' => MatrixVersion::STATUS_DRAFT,
        ]);
    }

    /**
     * Reemplaza los campos de una versión en borrador.
     *
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function replaceFields(MatrixVersion $version, array $fields): void
    {
        $fields = $this->normalizeFields($fields);

        if ($fields === []) {
            throw ValidationException::withMessages([
                'fields' => 'Agrega al menos un campo a la matriz.',
            ]);
        }

        MatrixField::query()
            ->where('matrix_version_id', $version->id)
            ->delete();

        foreach ($fields as $index => $field) {
            MatrixField::create([
                'matrix_version_id' => $version->id,
                'field_key' => $field['field_key'],
                'label' => $field['label'],
                'type' => $field['type'],
                'section' => $field['section'],
                'help' => $field['help'],
                'placeholder' => $field['placeholder'],
                'required' => $field['required'],
                'options' => $field['options'],
                'rules' => $field['rules'],
                'default_value' => null,
                'sort' => $index + 1,
                'col_span' => $field['col_span'],
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    public function normalizeFields(array $fields): array
    {
        $normalized = [];
        $usedKeys = [];

        foreach ($fields as $field) {
            $type = (string) ($field['type'] ?? '');

            if (! in_array($type, self::V1_TYPES, true)) {
                continue;
            }

            $label = trim((string) ($field['label'] ?? ''));

            if ($label === '') {
                throw ValidationException::withMessages([
                    'fields' => 'Todos los campos deben tener un nombre.',
                ]);
            }

            $fieldKey = trim((string) ($field['field_key'] ?? ''));

            if ($fieldKey === '' || in_array($fieldKey, $usedKeys, true)) {
                do {
                    $fieldKey = 'f_'.Str::lower(Str::random(8));
                } while (in_array($fieldKey, $usedKeys, true));
            }

            $usedKeys[] = $fieldKey;

            $options = $this->normalizeOptions($field['options'] ?? [], $type);

            $normalized[] = [
                'field_key' => $fieldKey,
                'label' => $label,
                'type' => $type,
                'section' => $this->nullableString($field['section'] ?? null, 120),
                'help' => $this->nullableString($field['help'] ?? null, 255),
                'placeholder' => $this->nullableString($field['placeholder'] ?? null, 160),
                'required' => (bool) ($field['required'] ?? false),
                'options' => $options,
                'rules' => [
                    'show_in_table' => (bool) ($field['show_in_table'] ?? false),
                    'filterable' => (bool) ($field['filterable'] ?? false),
                    'preserve_case' => (bool) ($field['preserve_case'] ?? false),
                ],
                'col_span' => in_array((int) ($field['col_span'] ?? 12), [4, 6, 12], true)
                    ? (int) ($field['col_span'] ?? 12)
                    : 12,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, mixed>  $options
     * @return array<int, array{key: string, label: string}>|null
     */
    private function normalizeOptions(array $options, string $type): ?array
    {
        if (! in_array($type, [MatrixField::TYPE_SELECT, MatrixField::TYPE_RADIO, MatrixField::TYPE_CHECKBOX], true)) {
            return null;
        }

        $normalized = [];
        $usedKeys = [];

        foreach ($options as $option) {
            $label = '';

            if (is_string($option)) {
                $label = trim($option);
            } elseif (is_array($option)) {
                $label = trim((string) ($option['label'] ?? ''));
            }

            if ($label === '') {
                continue;
            }

            $key = is_array($option) ? trim((string) ($option['key'] ?? '')) : '';

            if ($key === '' || in_array($key, $usedKeys, true)) {
                $key = Str::slug($label, '_');

                if ($key === '') {
                    $key = 'opt_'.(count($usedKeys) + 1);
                }

                $base = $key;
                $suffix = 2;

                while (in_array($key, $usedKeys, true)) {
                    $key = $base.'_'.$suffix;
                    $suffix++;
                }
            }

            $usedKeys[] = $key;
            $normalized[] = ['key' => $key, 'label' => $label];
        }

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'fields' => 'Los campos de selección necesitan al menos una opción.',
            ]);
        }

        return $normalized;
    }

    private function nullableString(mixed $value, int $max): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        return Str::limit($value, $max, '');
    }
}
