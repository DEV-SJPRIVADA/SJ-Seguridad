<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeArchiveConsultationItem extends Model
{
    /** @var list<string> */
    private const MONTH_LABELS = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    protected $fillable = [
        'employee_archive_consultation_id',
        'personal_requisition_ficha_entry_id',
        'document_number',
        'full_name',
        'archive_shelf',
        'archive_box',
        'concept',
        'delivered_to',
        'received',
        'observation',
        'week_of_month',
        'month_number',
        'month_label',
    ];

    protected function casts(): array
    {
        return [
            'received' => 'boolean',
        ];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(EmployeeArchiveConsultation::class, 'employee_archive_consultation_id');
    }

    public function fichaEntry(): BelongsTo
    {
        return $this->belongsTo(PersonalRequisitionFichaEntry::class, 'personal_requisition_ficha_entry_id');
    }

    public static function weekOfMonth(\DateTimeInterface $date): int
    {
        return (int) ceil(((int) $date->format('j')) / 7);
    }

    public static function monthLabel(int $monthNumber): string
    {
        return self::MONTH_LABELS[$monthNumber] ?? (string) $monthNumber;
    }

    /**
     * @return array{week_of_month: int, month_number: int, month_label: string}
     */
    public static function calendarMeta(\DateTimeInterface $date): array
    {
        $monthNumber = (int) $date->format('n');

        return [
            'week_of_month' => self::weekOfMonth($date),
            'month_number' => $monthNumber,
            'month_label' => self::monthLabel($monthNumber),
        ];
    }
}
