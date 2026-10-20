<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequisitionContractType extends Model
{
    use HasFactory;

    /**
     * Duraciones permitidas (meses) para contrato a término fijo → label UI.
     *
     * @var array<string, string>
     */
    public const FIXED_TERM_DURATIONS = [
        '6' => '6 meses',
        '12' => '12 meses',
    ];

    protected $fillable = ['name', 'is_active', 'sort_order'];

    /**
     * Contrato a término fijo (catálogo por nombre «Fijo»): duración 6 o 12 meses.
     */
    public function isFixedTerm(): bool
    {
        return mb_strtolower(trim((string) $this->name)) === 'fijo';
    }
}
