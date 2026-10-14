<?php

namespace App\Models;

use Database\Factories\InasistenciaCartaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InasistenciaCarta extends Model
{
    /** @use HasFactory<InasistenciaCartaFactory> */
    use HasFactory;

    protected $table = 'inasistencia_cartas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_number',
        'full_name',
        'cargo',
        'ciudad',
        'fecha_ini_aus',
        'dias',
        'firma_name',
        'cargo_firma',
        'signatory_id',
        'generated_by',
        'generated_at',
        'word_template_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_ini_aus' => 'date',
            'dias' => 'integer',
            'generated_at' => 'datetime',
        ];
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function signatory(): BelongsTo
    {
        return $this->belongsTo(PayrollCatalogItem::class, 'signatory_id');
    }

    public function wordTemplate(): BelongsTo
    {
        return $this->belongsTo(TerminationLetterDocumentTemplate::class, 'word_template_id');
    }
}
