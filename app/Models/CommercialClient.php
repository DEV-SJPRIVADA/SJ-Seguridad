<?php

namespace App\Models;

use App\Services\Comercial\CommercialClientChecklistService;
use App\Support\CommercialDocumentCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class CommercialClient extends Model
{
    protected $fillable = [
        'nit',
        'name',
        'phone',
        'address',
        'city',
        'legal_rep_name',
        'legal_rep_doc',
        'documentation_expires_on',
        'alert_days_before',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'documentation_expires_on' => 'date',
            'alert_days_before' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (CommercialClient $client): void {
            app(CommercialClientChecklistService::class)->ensureItemsForClient($client);
        });
    }

    public static function normalizeNit(?string $nit): string
    {
        $nit = trim((string) $nit);
        $nit = str_replace(['.', ' ', ','], '', $nit);

        return $nit;
    }

    public function setNitAttribute(?string $value): void
    {
        $this->attributes['nit'] = self::normalizeNit($value);
    }

    public function services(): HasMany
    {
        return $this->hasMany(CommercialService::class);
    }

    public function activeServices(): HasMany
    {
        return $this->services()->where('is_active', true);
    }

    /**
     * Servicios no dados de baja con el boton Inactivar (independiente del portafolio y del vencimiento).
     */
    public function activeOperationalServices(): HasMany
    {
        return $this->services()->where('is_active', true);
    }

    /**
     * Servicios operativos con contrato no vencido (KPI / dashboard; no define estado del cliente).
     */
    public function vigenteOperationalServices(): HasMany
    {
        $today = now()->startOfDay();

        return $this->services()
            ->where('is_active', true)
            ->where('portfolio', '!=', CommercialService::PORTFOLIO_INACTIVOS)
            ->where(function (Builder $query) use ($today): void {
                $query->whereNull('contract_end')
                    ->orWhereDate('contract_end', '>=', $today);
            });
    }

    public function isClientActive(): bool
    {
        return $this->activeOperationalServices()->exists();
    }

    public function documentItems(): HasMany
    {
        return $this->hasMany(CommercialClientDocumentItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function documentationAlertDays(): int
    {
        return $this->alert_days_before ?? CommercialDocumentCatalog::DEFAULT_ALERT_DAYS;
    }

    public function isDocumentationExpired(?Carbon $asOf = null): bool
    {
        if (! $this->documentation_expires_on instanceof Carbon) {
            return false;
        }

        $asOf = ($asOf ?? now())->copy()->startOfDay();

        return $this->documentation_expires_on->copy()->startOfDay()->lt($asOf);
    }

    public function isDocumentationExpiringSoon(?Carbon $asOf = null): bool
    {
        if (! $this->documentation_expires_on instanceof Carbon) {
            return false;
        }

        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $expires = $this->documentation_expires_on->copy()->startOfDay();

        if ($expires->lt($asOf)) {
            return false;
        }

        $limit = $asOf->copy()->addDays($this->documentationAlertDays());

        return $expires->lte($limit);
    }

    public function documentationVigenciaLabel(): ?string
    {
        if ($this->isDocumentationExpired()) {
            return 'Doc. vencida';
        }

        if ($this->isDocumentationExpiringSoon()) {
            return 'Doc. por vencer';
        }

        return null;
    }

    public function scopeDocumentationExpired(Builder $query, ?Carbon $asOf = null): Builder
    {
        $today = ($asOf ?? now())->copy()->startOfDay();

        return $query->whereNotNull('documentation_expires_on')
            ->whereDate('documentation_expires_on', '<', $today);
    }

    public function scopeDocumentationExpiring(Builder $query, ?Carbon $asOf = null): Builder
    {
        $today = ($asOf ?? now())->copy()->startOfDay();
        $defaultDays = CommercialDocumentCatalog::DEFAULT_ALERT_DAYS;

        $query->whereNotNull('documentation_expires_on')
            ->whereDate('documentation_expires_on', '>=', $today);

        if ($query->getConnection()->getDriverName() === 'sqlite') {
            return $query->whereRaw(
                'julianday(documentation_expires_on) <= julianday(?) + COALESCE(alert_days_before, ?)',
                [$today->toDateString(), $defaultDays]
            );
        }

        return $query->whereRaw(
            'documentation_expires_on <= DATE_ADD(?, INTERVAL COALESCE(alert_days_before, ?) DAY)',
            [$today->toDateString(), $defaultDays]
        );
    }
}
