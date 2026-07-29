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

    public const DOC_OK = CommercialDocumentCatalog::DOC_OK;

    public const DOC_X = CommercialDocumentCatalog::DOC_X;

    public const DOC_PENDING = CommercialDocumentCatalog::DOC_PENDING;

    public const DOC_NA = CommercialDocumentCatalog::DOC_NA;

    public const DOC_INCOMPLETE = CommercialDocumentCatalog::DOC_INCOMPLETE;

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
        if ($this->portfolio === self::PORTFOLIO_INACTIVOS) {
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
        if ($this->portfolio === self::PORTFOLIO_INACTIVOS) {
            return false;
        }

        $asOf = ($asOf ?? now())->copy()->startOfDay();

        if ($this->contract_end instanceof Carbon && $this->contract_end->copy()->startOfDay()->lt($asOf)) {
            return true;
        }

        $client = $this->relationLoaded('client') ? $this->client : $this->client()->first();

        return $client?->isDocumentationExpired($asOf) ?? false;
    }

    public function scopeFilterByVigencia(Builder $query, string $vigencia, ?Carbon $asOf = null, int $days = 30): Builder
    {
        if (! in_array($vigencia, ['expiring', 'expired'], true)) {
            return $query;
        }

        $today = ($asOf ?? now())->copy()->startOfDay();
        $inDays = $today->copy()->addDays($days);

        return $query
            ->where('portfolio', '!=', self::PORTFOLIO_INACTIVOS)
            ->where(function (Builder $outer) use ($vigencia, $today, $inDays): void {
                if ($vigencia === 'expired') {
                    $outer->where(function (Builder $q) use ($today): void {
                        $q->whereNotNull('contract_end')
                            ->whereDate('contract_end', '<', $today);
                    });

                    $outer->orWhereHas('client', fn (Builder $clientQuery) => $clientQuery->documentationExpired($today));

                    return;
                }

                $outer->where(function (Builder $q) use ($today, $inDays): void {
                    $q->whereNotNull('contract_end')
                        ->whereDate('contract_end', '>=', $today)
                        ->whereDate('contract_end', '<=', $inDays);
                });

                $outer->orWhereHas('client', fn (Builder $clientQuery) => $clientQuery->documentationExpiring($today));
            });
    }
}
