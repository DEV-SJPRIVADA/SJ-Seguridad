<?php

namespace App\Services\Matrices;

use App\Models\Matrices\Matrix;
use App\Models\Matrices\MatrixSubmission;
use App\Services\Audit\SystemAuditService;

/**
 * Auditoría central del módulo Matrices (wrapper delgado).
 */
final class MatrixAuditService
{
    public function __construct(
        private readonly SystemAuditService $audit,
    ) {}

    public function matrix(string $eventType, string $action, Matrix $matrix, array $metadata = []): void
    {
        $this->audit->logModelChange(
            module: 'matrices',
            eventType: $eventType,
            action: $action,
            model: $matrix,
            before: null,
            after: null,
            metadata: [
                'name' => $matrix->name,
                'area' => $matrix->area_key,
                ...$metadata,
            ],
            area: $matrix->area_key,
        );
    }

    public function submission(string $eventType, string $action, MatrixSubmission $submission, array $metadata = []): void
    {
        $this->audit->logModelChange(
            module: 'matrices',
            eventType: $eventType,
            action: $action,
            model: $submission,
            before: null,
            after: null,
            metadata: [
                'folio' => $submission->folio,
                'matrix_id' => $submission->matrix_id,
                ...$metadata,
            ],
            area: $submission->matrix?->area_key,
        );
    }
}
