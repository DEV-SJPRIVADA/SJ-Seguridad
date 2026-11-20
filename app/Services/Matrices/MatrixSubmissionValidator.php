<?php

namespace App\Services\Matrices;

use App\Models\Matrices\MatrixField;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Validación de los valores de un registro contra los campos de su versión.
 */
final class MatrixSubmissionValidator
{
    public const MAX_FILE_BYTES = 10 * 1024 * 1024;

    public const MAX_FILES_PER_RECORD = 10;

    /**
     * @param  Collection<int, MatrixField>  $fields
     * @param  array<string, mixed>  $payload
     * @param  array<string, int>  $existingFileCounts
     * @param  array<string, bool>  $existingSignatures
     */
    public function validate(
        Collection $fields,
        array $payload,
        array $existingFileCounts = [],
        array $existingSignatures = [],
    ): void {
        $errors = [];

        foreach ($fields as $field) {
            $key = $field->field_key;
            $value = $payload['values'][$key] ?? null;

            if ($field->required && ! $field->isFileType() && $this->isEmpty($value, $field)) {
                $errors["values.{$key}"] = "El campo {$field->label} es obligatorio.";

                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $this->validateValue($field, $value, $errors);
        }

        $errors = array_merge($errors, $this->validateAttachments($fields, $payload, $existingFileCounts, $existingSignatures));

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $value
     * @param  array<string, string>  $errors
     */
    private function validateValue(MatrixField $field, mixed $value, array &$errors): void
    {
        $key = $field->field_key;

        switch ($field->type) {
            case MatrixField::TYPE_TEXT:
                if (! is_scalar($value) || mb_strlen((string) $value) > 500) {
                    $errors["values.{$key}"] = "El campo {$field->label} admite máximo 500 caracteres.";
                }
                break;

            case MatrixField::TYPE_TEXTAREA:
                if (! is_scalar($value) || mb_strlen((string) $value) > 5000) {
                    $errors["values.{$key}"] = "El campo {$field->label} admite máximo 5000 caracteres.";
                }
                break;

            case MatrixField::TYPE_NUMBER:
            case MatrixField::TYPE_CURRENCY:
                if (! is_numeric($value)) {
                    $errors["values.{$key}"] = "El campo {$field->label} debe ser numérico.";
                } elseif (! is_finite((float) $value)) {
                    $errors["values.{$key}"] = "El campo {$field->label} no es un número válido.";
                }
                break;

            case MatrixField::TYPE_DATE:
                if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    $errors["values.{$key}"] = "El campo {$field->label} debe ser una fecha válida.";
                }
                break;

            case MatrixField::TYPE_SELECT:
            case MatrixField::TYPE_RADIO:
                if (! in_array((string) $value, $field->optionKeys(), true)) {
                    $errors["values.{$key}"] = "El valor seleccionado en {$field->label} no es válido.";
                }
                break;

            case MatrixField::TYPE_CHECKBOX:
                if (! is_array($value)) {
                    $errors["values.{$key}"] = "El campo {$field->label} debe ser una lista de opciones.";
                    break;
                }

                foreach ($value as $option) {
                    if (! in_array((string) $option, $field->optionKeys(), true)) {
                        $errors["values.{$key}"] = "Una de las opciones de {$field->label} no es válida.";
                        break;
                    }
                }
                break;
        }
    }

    /**
     * @param  Collection<int, MatrixField>  $fields
     * @param  array<string, mixed>  $payload
     * @param  array<string, int>  $existingFileCounts
     * @param  array<string, bool>  $existingSignatures
     * @return array<string, string>
     */
    private function validateAttachments(
        Collection $fields,
        array $payload,
        array $existingFileCounts,
        array $existingSignatures,
    ): array {
        $errors = [];

        foreach ($fields as $field) {
            if ($field->type === MatrixField::TYPE_FILE) {
                $files = $payload['files'][$field->field_key] ?? [];
                $files = is_array($files) ? array_filter($files, fn ($file) => $file instanceof UploadedFile) : [];
                $total = count($files) + (int) ($existingFileCounts[$field->field_key] ?? 0);

                if ($total > self::MAX_FILES_PER_RECORD) {
                    $errors["files.{$field->field_key}"] = "El campo {$field->label} admite máximo ".self::MAX_FILES_PER_RECORD.' archivos por registro.';
                }

                if ($field->required && $total === 0) {
                    $errors["files.{$field->field_key}"] = "Adjunta al menos un archivo en {$field->label}.";
                }

                foreach ($files as $file) {
                    if ($file->getSize() > self::MAX_FILE_BYTES) {
                        $errors["files.{$field->field_key}"] = "Cada archivo de {$field->label} debe pesar máximo 10 MB.";
                        break;
                    }
                }
            }

            if ($field->type === MatrixField::TYPE_SIGNATURE) {
                $hasExisting = (bool) ($existingSignatures[$field->field_key] ?? false);
                $mode = (string) ($payload['signature_mode'][$field->field_key] ?? '');
                $canvas = (string) ($payload['signature_canvas'][$field->field_key] ?? '');

                if (! $hasExisting && $field->required && $mode === '') {
                    $errors["signature_canvas.{$field->field_key}"] = "La firma de {$field->label} es obligatoria.";
                }

                if ($mode === 'draw' && $canvas === '' && ! $hasExisting && $field->required) {
                    $errors["signature_canvas.{$field->field_key}"] = "Dibuja tu firma en {$field->label}.";
                }

                if ($canvas !== '' && ! str_starts_with($canvas, 'data:image/png;base64,')) {
                    $errors["signature_canvas.{$field->field_key}"] = 'La firma dibujada no tiene un formato válido.';
                }
            }
        }

        return $errors;
    }

    private function isEmpty(mixed $value, MatrixField $field): bool
    {
        if ($field->type === MatrixField::TYPE_CHECKBOX) {
            return ! is_array($value) || $value === [];
        }

        return $value === null || $value === '' || (is_string($value) && trim($value) === '');
    }
}
