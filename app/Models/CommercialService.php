<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class CommercialService extends Model
{
    public const PORTFOLIO_SEG_FISICA = 'seg_fisica';
    public const PORTFOLIO_MONITOREO = 'monitoreo';
    public const PORTFOLIO_OCASIONALES = 'ocasionales';
    public const PORTFOLIO_INACTIVOS = 'inactivos';

    public const DOC_OK = 'ok';
    public const DOC_X = 'x';
    public const DOC_PENDING = 'pending';
    public const DOC_NA = 'na';
    public const DOC_INCOMPLETE = 'incomplete';

    protected $fillable = [
        'commercial_client_id',
        'portfolio',
        'contract_number',
        'advisor_name',
        'commercial_sector_id',
        'commercial_client_type_id',
        'commercial_service_type_id',
        'service_description',
        'contact_name',
        'contact_role',
        'contact_phone',
        'contact_email',
        'contract_start',
        'contract_end',
        'duration_months',
        'doc_economic_proposal',
        'doc_fo_co_02',
        'doc_laft_or_queries',
        'doc_rut',
        'doc_financials',
        'doc_legal_rep_id',
        'doc_chamber',
        'doc_preinstall',
        'doc_contract',
        'doc_annex_2',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'contract_start' => 'date',
            'contract_end' => 'date',
        ];
    }

    public static function portfolios(): array
    {
        return [
            self::PORTFOLIO_SEG_FISICA => 'Seg. Fisica',
            self::PORTFOLIO_MONITOREO => 'Monitoreo',
            self::PORTFOLIO_OCASIONALES => 'Ocasionales',
            self::PORTFOLIO_INACTIVOS => 'Inactivos',
        ];
    }

    public static function documentStatuses(): array
    {
        return [
            self::DOC_OK => 'OK',
            self::DOC_X => 'X',
            self::DOC_PENDING => 'Pendiente',
            self::DOC_NA => 'N/A',
            self::DOC_INCOMPLETE => 'Incompleto',
        ];
    }

    public static function documentFields(): array
    {
        return [
            'doc_economic_proposal' => 'P. economica',
            'doc_fo_co_02' => 'FO-CO-02',
            'doc_laft_or_queries' => 'LAFT / Consultas',
            'doc_rut' => 'RUT',
            'doc_financials' => 'EE.FF',
            'doc_legal_rep_id' => 'CC RL',
            'doc_chamber' => 'Camara comercio',
            'doc_preinstall' => 'Preinst',
            'doc_contract' => 'Contrato',
            'doc_annex_2' => 'Anexo 2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(CommercialClient::class, 'commercial_client_id');
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(CommercialSector::class, 'commercial_sector_id');
    }

    public function clientType(): BelongsTo
    {
        return $this->belongsTo(CommercialClientType::class, 'commercial_client_type_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(CommercialServiceType::class, 'commercial_service_type_id');
    }

    public function isExpiringSoon(int $days = 60): bool
    {
        if (! $this->contract_end instanceof Carbon) {
            return false;
        }

        if ($this->portfolio === self::PORTFOLIO_INACTIVOS) {
            return false;
        }

        return $this->contract_end->lte(now()->addDays($days)) && $this->contract_end->gte(now()->startOfDay());
    }

    public function isExpired(): bool
    {
        if (! $this->contract_end instanceof Carbon) {
            return false;
        }

        if ($this->portfolio === self::PORTFOLIO_INACTIVOS) {
            return false;
        }

        return $this->contract_end->lt(now()->startOfDay());
    }
}
