<?php

namespace App\Models;

use App\Support\CommercialDocumentCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class CommercialService extends Model
{
    public const PORTFOLIO_SEG_FISICA = 'seg_fisica';

    public const PORTFOLIO_MONITOREO = 'monitoreo';

    public const PORTFOLIO_OCASIONALES = 'ocasionales';

    public const PORTFOLIO_INACTIVOS = 'inactivos';

    public const ESTADO_INACTIVO = 'Inactivo';

    public const ESTADO_VENCIDO = 'Vencido';

    public const ESTADO_POR_VENCER = 'Por vencer';

    public const ESTADO_ACTIVO = 'Activo';

    public const CONTRACT_ESTADO_WINDOW_DAYS = 30;

    /** @deprecated Use ESTADO_* constants */
    public const VIGENCIA_INACTIVO = self::ESTADO_INACTIVO;

    /** @deprecated Use ESTADO_* constants */
    public const VIGENCIA_VENCIDO = self::ESTADO_VENCIDO;

    /** @deprecated Use ESTADO_* constants */
    public const VIGENCIA_POR_VENCER = self::ESTADO_POR_VENCER;

    /** @deprecated Use ESTADO_* constants */
    public const VIGENCIA_ACTIVO = self::ESTADO_ACTIVO;

    /** @deprecated Use CONTRACT_ESTADO_WINDOW_DAYS */
    public const CONTRACT_VIGENCIA_WINDOW_DAYS = self::CONTRACT_ESTADO_WINDOW_DAYS;

    public const DOC_OK = CommercialDocumentCatalog::DOC_OK;

    public const DOC_X = CommercialDocumentCatalog::DOC_X;

    public const DOC_PENDING = CommercialDocumentCatalog::DOC_PENDING;

    public const DOC_NA = CommercialDocumentCatalog::DOC_NA;

    public const DOC_INCOMPLETE = CommercialDocumentCatalog::DOC_INCOMPLETE;

    protected $attributes = [
        'is_active' => true,
    ];

    protected $fillable = [
        'commercial_client_id',
        'portfolio',
        'is_active',
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
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'contract_start' => 'date',
            'contract_end' => 'date',
            'is_active' => 'boolean',
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

    /** @deprecated Use CommercialDocumentCatalog::documentStatuses() */
    public static function documentStatuses(): array
    {
        return CommercialDocumentCatalog::documentStatuses();
    }

    /** @deprecated Use CommercialDocumentCatalog::documentFields() */
    public static function documentFields(): array
    {
        return CommercialDocumentCatalog::documentFields();
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

    public function isExpiringSoon(int $days = 60, ?Carbon $asOf = null): bool
    {
        if ($this->is_active === false || $this->portfolio === self::PORTFOLIO_INACTIVOS) {
            return false;
        }

        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $limit = $asOf->copy()->addDays($days);

        if ($this->contract_end instanceof Carbon) {
            $contractEnd = $this->contract_end->copy()->startOfDay();
            if ($contractEnd->lte($limit) && $contractEnd->gte($asOf)) {
                return true;
            }
        }

        $client = $this->relationLoaded('client') ? $this->client : $this->client()->first();

        return $client?->isDocumentationExpiringSoon($asOf) ?? false;
    }

    public function isExpired(?Carbon $asOf = null): bool
    {
        if ($this->is_active === false || $this->portfolio === self::PORTFOLIO_INACTIVOS) {
            return false;
        }

        $asOf = ($asOf ?? now())->copy()->startOfDay();

        if ($this->contract_end instanceof Carbon && $this->contract_end->copy()->startOfDay()->lt($asOf)) {
            return true;
        }

        $client = $this->relationLoaded('client') ? $this->client : $this->client()->first();

        return $client?->isDocumentationExpired($asOf) ?? false;
    }

    public function serviceEstadoLabel(?Carbon $asOf = null, int $windowDays = self::CONTRACT_ESTADO_WINDOW_DAYS): string
    {
        if ($this->is_active === false) {
            return self::ESTADO_INACTIVO;
        }

        $asOf = ($asOf ?? now())->copy()->startOfDay();

        if ($this->contract_end instanceof Carbon) {
            $end = $this->contract_end->copy()->startOfDay();

            if ($end->lt($asOf)) {
                return self::ESTADO_VENCIDO;
            }

            $limit = $asOf->copy()->addDays($windowDays);

            if ($end->lte($limit)) {
                return self::ESTADO_POR_VENCER;
            }

            return self::ESTADO_ACTIVO;
        }

        return self::ESTADO_ACTIVO;
    }

    /** @deprecated Use serviceEstadoLabel() */
    public function contractVigenciaLabel(?Carbon $asOf = null, int $windowDays = self::CONTRACT_ESTADO_WINDOW_DAYS): string
    {
        return $this->serviceEstadoLabel($asOf, $windowDays);
    }

    public function scopeFilterByContractEstado(Builder $query, string $estado, ?Carbon $asOf = null, int $days = self::CONTRACT_ESTADO_WINDOW_DAYS): Builder
    {
        if (! in_array($estado, ['expiring', 'expired'], true)) {
            return $query;
        }

        $today = ($asOf ?? now())->copy()->startOfDay();
        $inDays = $today->copy()->addDays($days);

        return $query
            ->where('is_active', true)
            ->where(function (Builder $outer) use ($estado, $today, $inDays): void {
                if ($estado === 'expired') {
                    $outer->whereNotNull('contract_end')
                        ->whereDate('contract_end', '<', $today);

                    return;
                }

                $outer->whereNotNull('contract_end')
                    ->whereDate('contract_end', '>=', $today)
                    ->whereDate('contract_end', '<=', $inDays);
            });
    }

    /** @deprecated Use scopeFilterByContractEstado() */
    public function scopeFilterByVigencia(Builder $query, string $vigencia, ?Carbon $asOf = null, int $days = self::CONTRACT_ESTADO_WINDOW_DAYS): Builder
    {
        return $query->filterByContractEstado($vigencia, $asOf, $days);
    }
}
