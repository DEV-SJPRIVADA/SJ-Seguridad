<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\QualityDocument;
use App\Services\Access\BoardAccessService;
use App\Services\Access\RequisitionAccessService;
use App\Services\Access\SupplyAccessService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected string $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'area_key',
        'sede_id',
        'email',
        'password',
        'is_active',
        'must_change_password',
        'last_login_at',
        'created_by',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'must_change_password' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(SupplySite::class, 'sede_id');
    }

    public function hasAssignedSite(): bool
    {
        return $this->sede_id !== null;
    }

    public function areaLabel(): ?string
    {
        return config("access.areas.{$this->area_key}");
    }

    public function hasAssignedArea(): bool
    {
        return is_string($this->area_key) && $this->area_key !== '';
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function requisitionBoardTabsFor(string $moduleKey): Collection
    {
        return collect(app(RequisitionAccessService::class)->visibleTabsFor($this, $moduleKey));
    }

    public function canAccessRequisitionTab(string $moduleKey, string $tab): bool
    {
        return app(RequisitionAccessService::class)->canAccessTab($this, $moduleKey, $tab);
    }

    public function canViewRequisitionsBoardFor(string $areaKey): bool
    {
        return app(RequisitionAccessService::class)->canViewRequisitionsBoard($this, $areaKey);
    }

    public function defaultRequisitionBoardUrl(string $moduleKey): string
    {
        $tabs = $this->requisitionBoardTabsFor($moduleKey);
        $firstTab = $tabs->first();

        return match ($firstTab) {
            'dashboard' => route('requisitions.dashboard', ['module' => $moduleKey]),
            'solicitar' => route('requisitions.create', ['module' => $moduleKey]),
            'seguimiento' => route('requisitions.tracking', ['module' => $moduleKey]),
            'gestion' => route('requisitions.manage', ['module' => $moduleKey]),
            'parametros' => route('requisitions.parameters', ['module' => $moduleKey]),
            default => route('dashboard', ['module' => $moduleKey]),
        };
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function supplyBoardTabsFor(string $moduleKey): Collection
    {
        return collect(app(SupplyAccessService::class)->visibleTabsFor($this, $moduleKey));
    }

    public function canAccessSupplyTab(string $moduleKey, string $tab): bool
    {
        return app(SupplyAccessService::class)->canAccessTab($this, $moduleKey, $tab);
    }

    public function canViewSupplyBoardFor(string $areaKey): bool
    {
        return app(SupplyAccessService::class)->canViewSupplyBoard($this, $areaKey);
    }

    public function defaultSupplyBoardUrl(string $moduleKey): string
    {
        $tabs = $this->supplyBoardTabsFor($moduleKey);
        $firstTab = $tabs->first();

        return match ($firstTab) {
            'mis_solicitudes' => route('supplies.index', ['module' => $moduleKey]),
            'aprobacion_insumos' => route('supplies.approval.index', ['module' => $moduleKey]),
            'insumos_aprobados' => route('supplies.approved.index', ['module' => $moduleKey]),
            'catalogo' => route('supplies.products.index', ['module' => $moduleKey]),
            default => route('dashboard', ['module' => $moduleKey]),
        };
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function qualityDocumentBoardTabsFor(string $moduleKey): Collection
    {
        $tabs = collect([]);
        $canManage = $this->can('manage.quality.documents');
        $hasAreaAccess = $this->can("view.area.{$moduleKey}") || $this->can("manage.area.{$moduleKey}");
        $hasPersonalDocuments = QualityDocument::hasActiveForUser($this->id);

        if ($canManage && $moduleKey === 'calidad') {
            $tabs->push('administrar');
        }

        if ($hasAreaAccess) {
            $tabs->push('biblioteca');
        }

        if ($hasPersonalDocuments) {
            $tabs->push('mis_documentos');
        }

        return $tabs->unique()->values();
    }

    public function defaultQualityDocumentBoardUrl(string $moduleKey): string
    {
        $tabs = $this->qualityDocumentBoardTabsFor($moduleKey);
        $firstTab = $tabs->first();

        return match ($firstTab) {
            'administrar' => route('quality-documents.admin.index', ['module' => $moduleKey]),
            'biblioteca' => route('quality-documents.library.index', ['module' => $moduleKey]),
            'mis_documentos' => route('quality-documents.mine.index', ['module' => $moduleKey]),
            default => route('dashboard', ['module' => $moduleKey]),
        };
    }

    public function canViewDocumentsBoardFor(string $areaKey): bool
    {
        return app(BoardAccessService::class)->canViewDocumentsBoard($this, $areaKey);
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function indicadorBoardTabsFor(): Collection
    {
        $allowed = collect([
            'dashboard' => $this->can('operations.view') || $this->can('operations.manage'),
            'captura' => $this->can('operations.capture') || $this->can('operations.manage'),
            'consolidado' => $this->can('operations.manage'),
            'ajustes' => $this->can('operations.manage'),
        ])->filter()->keys();

        return collect(array_keys(config('access.indicador_tabs', [])))
            ->filter(fn (string $key) => $allowed->contains($key))
            ->values();
    }

    public function canAccessIndicadorTab(string $tab): bool
    {
        return match ($tab) {
            'dashboard' => $this->can('operations.view') || $this->can('operations.manage'),
            'capture' => $this->can('operations.capture') || $this->can('operations.manage'),
            'manage' => $this->can('operations.manage'),
            default => false,
        };
    }

    public function defaultIndicadorBoardUrl(): string
    {
        $tabs = $this->indicadorBoardTabsFor();
        $firstTab = $tabs->first();

        return match ($firstTab) {
            'dashboard' => route('indicadores.dashboard'),
            'captura' => route('indicadores.index'),
            'ajustes' => route('indicadores.admin.ajustes'),
            'consolidado' => route('indicadores.admin.consolidado.index'),
            default => route('dashboard', ['module' => 'operaciones']),
        };
    }
}
