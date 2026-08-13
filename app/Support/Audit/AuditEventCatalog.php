<?php

namespace App\Support\Audit;

class AuditEventCatalog
{
    public const SEVERITY_INFO = 'info';

    public const SEVERITY_AUDIT = 'audit';

    public const SEVERITY_SECURITY = 'security';

    /**
     * @var array<string, array<string, array{severity: string, log_by_default: bool}>>
     */
    private const INDICADORES_EVENTS = [
        'admin_action' => [
            'dashboard_view' => ['severity' => self::SEVERITY_INFO, 'log_by_default' => true],
            'consolidado_view' => ['severity' => self::SEVERITY_INFO, 'log_by_default' => true],
            'capture_user_enable' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'capture_user_disable' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'indicator_capture' => [
            'create' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'update' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'period' => [
            'create' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'close' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'reopen' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'indicator_targets' => [
            'update' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'export' => [
            'dashboard_pdf' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'management_pptx' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'leader_excel' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'leader_pdf' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'consolidado_excel' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'consolidado_pdf' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'improvement' => [
            'create' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'update' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
    ];

    /**
     * @var array<string, array<string, array{severity: string, log_by_default: bool}>>
     */
    private const ADMIN_EVENTS = [
        'user_management' => [
            'create' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'update' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'activate' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'deactivate' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'role_sync' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'permissions_sync' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'password_reset' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'notification_config' => [
            'email_attach' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'email_detach' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
    ];

    /**
     * @var array<string, array<string, array{severity: string, log_by_default: bool}>>
     */
    private const REQUISITIONS_EVENTS = [
        'requisition' => [
            'create' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'status_change' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'management_approval' => [
            'approve' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'reject' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'export' => [
            'manage_excel' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'tracking_excel' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
    ];

    /**
     * @var array<string, array<string, array{severity: string, log_by_default: bool}>>
     */
    private const SUPPLIES_EVENTS = [
        'supply_request' => [
            'create' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'quality_approve' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'quality_reject' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'supply_product' => [
            'create' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'update' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'activate' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'deactivate' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'export' => [
            'my_requests_excel' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'approval_queue_excel' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'approved_list_excel' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'approved_request_excel' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'request_detail_excel' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'catalog_excel' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
    ];

    /**
     * @var array<string, array<string, array{severity: string, log_by_default: bool}>>
     */
    private const COMMERCIAL_EVENTS = [
        'client' => [
            'create' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'update' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'service' => [
            'create' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'update' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'activate' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'deactivate' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'checklist' => [
            'update' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'parameter' => [
            'create' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'update' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'delete' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'import' => [
            'matrix' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'export' => [
            'clients_excel' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'services_excel' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'checklist_excel' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'import_template_data' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
    ];

    /**
     * @var array<string, array<string, array{severity: string, log_by_default: bool}>>
     */
    private const QUALITY_DOCUMENTS_EVENTS = [
        'document' => [
            'create' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'update' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'activate' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'deactivate' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'delete' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'export' => [
            'admin_excel' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'library_excel' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'mine_excel' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
    ];

    /**
     * @var array<string, array<string, array{severity: string, log_by_default: bool}>>
     */
    private const PURCHASE_REQUESTS_EVENTS = [
        'purchase_request' => [
            'create' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'resubmit' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'director_approval' => [
            'approve' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'reject' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'compras_processing' => [
            'status_change' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'supply_compras' => [
            'status_change' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'export' => [
            'supply_pdf' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'supply_excel' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
    ];

    /**
     * @var array<string, array<string, array{severity: string, log_by_default: bool}>>
     */
    private const FICHA_EMPLEADOS_EVENTS = [
        'ficha_entry' => [
            'promote' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'create' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'ficha_profile' => [
            'update' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'status_change' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'import' => [
            'profiles' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
        'export' => [
            'masivos_excel' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
            'import_template_data' => ['severity' => self::SEVERITY_AUDIT, 'log_by_default' => true],
        ],
    ];

    public static function severityFor(string $module, string $eventType, string $action): string
    {
        $catalog = match ($module) {
            'indicadores' => self::INDICADORES_EVENTS,
            'admin' => self::ADMIN_EVENTS,
            'requisitions' => self::REQUISITIONS_EVENTS,
            'commercial' => self::COMMERCIAL_EVENTS,
            'supplies' => self::SUPPLIES_EVENTS,
            'purchase_requests' => self::PURCHASE_REQUESTS_EVENTS,
            'quality_documents' => self::QUALITY_DOCUMENTS_EVENTS,
            'ficha_empleados' => self::FICHA_EMPLEADOS_EVENTS,
            default => null,
        };

        if ($catalog === null) {
            return self::SEVERITY_AUDIT;
        }

        return $catalog[$eventType][$action]['severity'] ?? self::SEVERITY_AUDIT;
    }

    /**
     * Event/action pairs excluded from the global admin UI unless show_info=1.
     *
     * @return array<int, array{event_type: string, action: string}>
     */
    public static function globalUiExcludedEventTypes(): array
    {
        $excluded = [];

        foreach (self::INDICADORES_EVENTS as $eventType => $actions) {
            foreach ($actions as $action => $definition) {
                if ($definition['severity'] === self::SEVERITY_INFO) {
                    $excluded[] = [
                        'event_type' => $eventType,
                        'action' => $action,
                    ];
                }
            }
        }

        return $excluded;
    }
}
