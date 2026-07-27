<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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
        'doc_economic_proposal_tracks_expiry',
        'doc_economic_proposal_expires_on',
        'doc_fo_co_02',
        'doc_fo_co_02_tracks_expiry',
        'doc_fo_co_02_expires_on',
        'doc_laft_or_queries',
        'doc_laft_or_queries_tracks_expiry',
        'doc_laft_or_queries_expires_on',
        'doc_rut',
        'doc_rut_tracks_expiry',
        'doc_rut_expires_on',
        'doc_financials',
        'doc_financials_tracks_expiry',
        'doc_financials_expires_on',
        'doc_legal_rep_id',
        'doc_legal_rep_id_tracks_expiry',
        'doc_legal_rep_id_expires_on',
        'doc_chamber',
        'doc_chamber_tracks_expiry',
        'doc_chamber_expires_on',
        'doc_preinstall',
        'doc_preinstall_tracks_expiry',
        'doc_preinstall_expires_on',
        'doc_contract',
        'doc_contract_tracks_expiry',
        'doc_contract_expires_on',
        'doc_annex_2',
        'doc_annex_2_tracks_expiry',
        'doc_annex_2_expires_on',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        $casts = [
            'contract_start' => 'date',
            'contract_end' => 'date',
        ];

        foreach (self::documentExpiryFields() as $meta) {
            $casts[$meta['tracks']] = 'boolean';
            $casts[$meta['expires']] = 'date';
        }

        return $casts;
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

    /**
     * Map document field => expiry column names.
     *
     * @return array<string, array{tracks: string, expires: string}>
     */
    public static function documentExpiryFields(): array
    {
        $map = [];

        foreach (array_keys(self::documentFields()) as $field) {
            $map[$field] = [
                'tracks' => "{$field}_tracks_expiry",
                'expires' => "{$field}_expires_on",
            ];
        }

        return $map;
    }

    /**
     * Statuses that allow enabling document expiry tracking.
     *
     * @return list<string>
     */
    public static function documentStatusesWithExpiry(): array
    {
        return [
            self::DOC_OK,
            self::DOC_X,
            self::DOC_PENDING,
            self::DOC_INCOMPLETE,
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

    /**
     * @return Collection<int, Carbon>
     */
    public function trackedDocumentExpiryDates(): Collection
    {
        $dates = collect();

        foreach (self::documentExpiryFields() as $meta) {
            if (! $this->{$meta['tracks']}) {
                continue;
            }

            $expires = $this->{$meta['expires']};
            if ($expires instanceof Carbon) {
                $dates->push($expires->copy()->startOfDay());
            }
        }

        return $dates;
    }

    public function earliestDocumentExpiry(): ?Carbon
    {
        $dates = $this->trackedDocumentExpiryDates();

        if ($dates->isEmpty()) {
            return null;
        }

        return $dates->sortBy(fn (Carbon $date) => $date->timestamp)->first();
    }

    public function hasDocumentExpiringSoon(int $days = 60, ?Carbon $asOf = null): bool
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $limit = $asOf->copy()->addDays($days);

        return $this->trackedDocumentExpiryDates()
            ->contains(fn (Carbon $date) => $date->gte($asOf) && $date->lte($limit));
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

        return $this->hasDocumentExpiringSoon($days, $asOf);
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

        return $this->trackedDocumentExpiryDates()
            ->contains(fn (Carbon $date) => $date->lt($asOf));
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

                    foreach (self::documentExpiryFields() as $meta) {
                        $outer->orWhere(function (Builder $q) use ($meta, $today): void {
                            $q->where($meta['tracks'], true)
                                ->whereNotNull($meta['expires'])
                                ->whereDate($meta['expires'], '<', $today);
                        });
                    }

                    return;
                }

                $outer->where(function (Builder $q) use ($today, $inDays): void {
                    $q->whereNotNull('contract_end')
                        ->whereDate('contract_end', '>=', $today)
                        ->whereDate('contract_end', '<=', $inDays);
                });

                foreach (self::documentExpiryFields() as $meta) {
                    $outer->orWhere(function (Builder $q) use ($meta, $today, $inDays): void {
                        $q->where($meta['tracks'], true)
                            ->whereNotNull($meta['expires'])
                            ->whereDate($meta['expires'], '>=', $today)
                            ->whereDate($meta['expires'], '<=', $inDays);
                    });
                }
            });
    }
}
