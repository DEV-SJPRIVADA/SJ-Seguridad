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

    public static function severityFor(string $module, string $eventType, string $action): string
    {
        $catalog = match ($module) {
            'indicadores' => self::INDICADORES_EVENTS,
            'admin' => self::ADMIN_EVENTS,
            'requisitions' => self::REQUISITIONS_EVENTS,
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
