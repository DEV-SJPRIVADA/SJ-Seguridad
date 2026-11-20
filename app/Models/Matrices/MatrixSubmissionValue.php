<?php

namespace App\Models\Matrices;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatrixSubmissionValue extends Model
{
    protected $table = 'matrix_submission_values';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'submission_id',
        'field_id',
        'value_text',
        'value_number',
        'value_date',
        'value_bool',
        'value_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value_number' => 'decimal:4',
            'value_date' => 'date',
            'value_bool' => 'boolean',
            'value_json' => 'array',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(MatrixSubmission::class, 'submission_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(MatrixField::class, 'field_id');
    }

    /**
     * Valor legible según el tipo de campo.
     */
    public function displayValue(): string
    {
        $field = $this->field;

        if ($field === null) {
            return '';
        }

        return match ($field->type) {
            MatrixField::TYPE_NUMBER => $this->value_number !== null
                ? rtrim(rtrim((string) $this->value_number, '0'), '.')
                : '',
            MatrixField::TYPE_CURRENCY => $this->value_number !== null
                ? '$ '.number_format((float) $this->value_number, 2, ',', '.')
                : '',
            MatrixField::TYPE_DATE => $this->value_date?->format('d/m/Y') ?? '',
            MatrixField::TYPE_SELECT, MatrixField::TYPE_RADIO => $this->optionLabel((string) ($this->value_text ?? '')),
            MatrixField::TYPE_CHECKBOX => implode(', ', array_map(
                fn ($value): string => $this->optionLabel((string) $value),
                is_array($this->value_json) ? $this->value_json : []
            )),
            default => (string) ($this->value_text ?? ''),
        };
    }

    public function optionLabel(string $key): string
    {
        if ($key === '') {
            return '';
        }

        foreach ($this->field?->options ?? [] as $option) {
            if (is_array($option) && (string) ($option['key'] ?? '') === $key) {
                return (string) ($option['label'] ?? $key);
            }
        }

        return $key;
    }
}
