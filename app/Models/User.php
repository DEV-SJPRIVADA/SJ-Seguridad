<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\QualityDocument;
use App\Services\Access\BoardAccessService;
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
        $tabs = collect([]);
        $canManageAll = $this->can('manage.users') || $this->can('manage.area.gestion_humana');
        $canViewBoard = $this->can("view.board.{$moduleKey}.requisiciones");
        $canTrackModule = $canManageAll || ($this->hasAssignedArea() && $this->area_key === $moduleKey);

        if ($this->can('requisitions.tab.dashboard') || $canManageAll) {
            $tabs->push('dashboard');
        }

        // El tab Solicitar se muestra si:
        // - El usuario tiene permiso explícito 'requisitions.tab.solicitar' O
        // - Tiene permiso genérico de ver el tablero de requisiciones de ese módulo O
        // - El usuario puede gestionar usuarios o el área de gestión humana
        if ($this->can('requisitions.tab.solicitar') || $canViewBoard || $canManageAll) {
            $tabs->push('solicitar');
        }

        if ($canTrackModule && ($this->can('requisitions.tab.seguimiento') || $this->can('requisitions.tab.solicitar') || $canViewBoard || $canManageAll)) {
            $tabs->push('seguimiento');
        }

        if ($this->can('requisitions.tab.gestion') || $this->can('manage.requisitions') || $canManageAll) {
            $tabs->push('gestion');
        }

        if ($this->can('manage.requisition.parameters') || $canManageAll) {
            $tabs->push('parametros');
        }

        return $tabs->unique()->values();
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
        $tabs = collect([]);

        // Acceso granular por pestaña
        if ($this->can("supply.tab.my_requests") || $this->can("view.board.{$moduleKey}.suministros")) {
            $tabs->push('mis_solicitudes');
        }

        if ($this->can("supply.tab.quality") || $this->can('approve.supply.quality') || $this->can('manage.users')) {
            $tabs->push('aprobacion_insumos');
            $tabs->push('insumos_aprobados');
        }

        if ($this->can("supply.tab.catalog") || $this->can('manage.supply.catalog') || $this->can('manage.users')) {
            $tabs->push('catalogo');
        }

        return $tabs->unique()->values();
    }

    public function canAccessSupplyTab(string $moduleKey, string $tab): bool
    {
        return match ($tab) {
            'my_requests' => $this->can('supply.tab.my_requests') || $this->can("view.board.{$moduleKey}.suministros"),
            'quality' => $this->can('supply.tab.quality') || $this->can('approve.supply.quality') || $this->can('manage.users'),
            'catalog' => $this->can('supply.tab.catalog') || $this->can('manage.supply.catalog') || $this->can('manage.users'),
            default => false,
        };
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
        $tabs = collect([]);

        if ($this->can('operations.view') || $this->can('operations.manage')) {
            $tabs->push('dashboard');
            $tabs->push('jefes');
        }

        if ($this->can('operations.capture') || $this->can('operations.manage')) {
            $tabs->push('captura');
        }

        if ($this->can('operations.manage')) {
            $tabs->push('periodos');
            $tabs->push('pesos');
            $tabs->push('documentos');
            $tabs->push('madre');
            $tabs->push('auditoria');
        }

        return $tabs->unique()->values();
    }

    public function canAccessIndicadorTab(string $tab): bool
    {
        return match ($tab) {
            'dashboard' => $this->can('operations.view') || $this->can('operations.manage'),
            'capture' => $this->can('operations.capture') || $this->can('operations.manage'),
            'leaders' => $this->can('operations.view') || $this->can('operations.manage'),
            'leaders_manage' => $this->can('operations.manage'),
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
            'jefes' => route('indicadores.leaders.index'),
            'periodos' => route('indicadores.admin.periods.index'),
            'pesos' => route('indicadores.admin.weights'),
            'documentos' => route('indicadores.admin.documents.index'),
            'madre' => route('indicadores.admin.mother.index'),
            'auditoria' => route('indicadores.admin.audit.index'),
            default => route('dashboard', ['module' => 'operaciones']),
        };
    }
}
