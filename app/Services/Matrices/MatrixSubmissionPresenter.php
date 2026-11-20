<?php

namespace App\Services\Matrices;

use App\Models\Matrices\Matrix;
use App\Models\Matrices\MatrixField;
use App\Models\Matrices\MatrixSubmission;
use App\Models\Matrices\MatrixSubmissionAttachment;
use Illuminate\Support\Collection;

/**
 * Presentación común de registros para el listado, el detalle y el export.
 */
final class MatrixSubmissionPresenter
{
    public const MAX_TABLE_FIELDS = 6;

    /**
     * Campos de la versión vigente.
     *
     * @return Collection<int, MatrixField>
     */
    public function fieldsFor(Matrix $matrix): Collection
    {
        $version = $matrix->currentVersion;

        if ($version === null) {
            return collect();
        }

        return $version->fields()->get();
    }

    /**
     * Campos visibles como columnas del listado.
     *
     * @return Collection<int, MatrixField>
     */
    public function tableFields(Matrix $matrix): Collection
    {
        return $this->fieldsFor($matrix)
            ->filter(fn (MatrixField $field): bool => $field->showInTable())
            ->take(self::MAX_TABLE_FIELDS)
            ->values();
    }

    /**
     * Campos que se pueden usar como filtro del listado.
     *
     * @return Collection<int, MatrixField>
     */
    public function filterFields(Matrix $matrix): Collection
    {
        return $this->fieldsFor($matrix)
            ->filter(fn (MatrixField $field): bool => $field->isFilterable())
            ->filter(fn (MatrixField $field): bool => in_array($field->type, [
                MatrixField::TYPE_SELECT,
                MatrixField::TYPE_RADIO,
                MatrixField::TYPE_CHECKBOX,
            ], true))
            ->values();
    }

    /**
     * @return array<string, string> field_key => valor legible
     */
    public function displayMap(MatrixSubmission $submission): array
    {
        $map = [];

        foreach ($submission->values as $value) {
            $key = $value->field?->field_key;

            if ($key === null) {
                continue;
            }

            $map[$key] = $value->displayValue();
        }

        return $map;
    }

    /**
     * @return array<string, string> field_key => valor para exportar
     */
    public function exportMap(MatrixSubmission $submission): array
    {
        return $this->displayMap($submission);
    }

    /**
     * Campos de la versión del registro (para editar/detalle).
     *
     * @return Collection<int, MatrixField>
     */
    public function fieldsForSubmission(MatrixSubmission $submission): Collection
    {
        return $submission->version?->fields()->get() ?? collect();
    }

    /**
     * @return Collection<int, MatrixSubmissionAttachment>
     */
    public function attachmentsByField(MatrixSubmission $submission, MatrixField $field): Collection
    {
        return $submission->attachments
            ->where('field_id', $field->id)
            ->values();
    }
}
