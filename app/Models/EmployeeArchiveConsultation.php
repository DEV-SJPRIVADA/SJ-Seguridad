<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeArchiveConsultation extends Model
{
    protected $fillable = [
        'user_id',
        'document_numbers',
        'consultation_types',
        'documents_requested',
        'documents_matched',
        'documents_not_found',
        'delivered_to',
    ];

    protected function casts(): array
    {
        return [
            'document_numbers' => 'array',
            'consultation_types' => 'array',
            'documents_not_found' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EmployeeArchiveConsultationItem::class);
    }

    public function conceptLabel(): string
    {
        return implode(' · ', $this->typeLabels());
    }

    /**
     * @return list<string>
     */
    public function typeLabels(): array
    {
        /** @var array<string, string> $labels */
        $labels = config('employee_ficha.archive_consultation_types', []);

        return array_values(array_filter(
            array_map(fn (string $key): ?string => $labels[$key] ?? null, $this->consultation_types ?? []),
            fn (?string $label): bool => $label !== null && $label !== '',
        ));
    }
}
