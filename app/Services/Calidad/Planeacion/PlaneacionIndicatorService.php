<?php

namespace App\Services\Calidad\Planeacion;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\QualityPlanning\QualityPlanningIndicatorVersion;
use App\Support\UppercaseFormText;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaneacionIndicatorService
{
    public function __construct(
        private readonly PlaneacionAuditLogService $audit,
        private readonly FormulaEvaluator $evaluator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $userId): QualityPlanningIndicator
    {
        $fields = $this->normalizeFields($data['fields'] ?? []);
        $this->assertFormula($data['formula'] ?? '', $fields);

        return DB::transaction(function () use ($data, $fields, $userId): QualityPlanningIndicator {
            $indicator = QualityPlanningIndicator::query()->create([
                'code' => (string) $data['code'],
                'created_by_user_id' => $userId,
            ]);

            $version = $indicator->versions()->create([
                'version_number' => 1,
                'status' => QualityPlanningIndicatorVersion::STATUS_DRAFT,
                ...$this->versionAttributes($data, $fields),
            ]);

            $this->audit->logModelChange(
                eventType: 'indicator',
                action: 'create',
                model: $indicator,
                before: null,
                after: [
                    'code' => $indicator->code,
                    'name' => $version->name,
                    'version' => 1,
                ],
                userId: $userId,
            );

            return $indicator->fresh(['versions']) ?? $indicator;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(QualityPlanningIndicator $indicator, array $data, int $userId): QualityPlanningIndicator
    {
        $this->assertNotArchived($indicator);

        $fields = $this->normalizeFields($data['fields'] ?? []);
        $this->assertFormula($data['formula'] ?? '', $fields);

        return DB::transaction(function () use ($indicator, $data, $fields, $userId): QualityPlanningIndicator {
            $indicator->load('versions');
            $working = $indicator->workingVersion();
            abort_unless($working instanceof QualityPlanningIndicatorVersion, 404);

            $before = [
                'code' => $indicator->code,
                'name' => $working->name,
                'version' => $working->version_number,
                'status' => $working->status,
            ];

            $indicator->forceFill(['code' => (string) $data['code']])->save();

            if ($working->isFrozen()) {
                $working = $indicator->versions()->create([
                    'version_number' => ((int) $indicator->versions()->max('version_number')) + 1,
                    'status' => QualityPlanningIndicatorVersion::STATUS_DRAFT,
                    ...$this->versionAttributes($data, $fields),
                ]);
            } else {
                $working->fill($this->versionAttributes($data, $fields))->save();
            }

            $this->audit->logModelChange(
                eventType: 'indicator',
                action: 'update',
                model: $indicator,
                before: $before,
                after: [
                    'code' => $indicator->code,
                    'name' => $working->name,
                    'version' => $working->version_number,
                    'status' => $working->status,
                ],
                userId: $userId,
            );

            return $indicator->fresh(['versions', 'currentPublishedVersion']) ?? $indicator;
        });
    }

    public function publish(QualityPlanningIndicator $indicator, int $userId): QualityPlanningIndicator
    {
        $this->assertNotArchived($indicator);

        return DB::transaction(function () use ($indicator, $userId): QualityPlanningIndicator {
            $indicator->load('versions');
            $draft = $indicator->draftVersion();

            if (! $draft instanceof QualityPlanningIndicatorVersion) {
                throw ValidationException::withMessages([
                    'status' => 'Solo se puede publicar un borrador.',
                ]);
            }

            $previous = $indicator->versions
                ->where('status', QualityPlanningIndicatorVersion::STATUS_PUBLISHED)
                ->all();

            foreach ($previous as $published) {
                $published->forceFill([
                    'status' => QualityPlanningIndicatorVersion::STATUS_SUPERSEDED,
                ])->save();
            }

            $draft->forceFill([
                'status' => QualityPlanningIndicatorVersion::STATUS_PUBLISHED,
                'published_at' => now(),
                'published_by_user_id' => $userId,
            ])->save();

            $indicator->forceFill([
                'current_published_version_id' => $draft->id,
            ])->save();

            $this->audit->logModelChange(
                eventType: 'indicator',
                action: 'publish',
                model: $indicator,
                before: ['status' => QualityPlanningIndicatorVersion::STATUS_DRAFT],
                after: [
                    'status' => QualityPlanningIndicatorVersion::STATUS_PUBLISHED,
                    'version' => $draft->version_number,
                ],
                userId: $userId,
            );

            return $indicator->fresh(['versions', 'currentPublishedVersion']) ?? $indicator;
        });
    }

    public function archive(QualityPlanningIndicator $indicator, int $userId): QualityPlanningIndicator
    {
        $this->assertNotArchived($indicator);

        $indicator->forceFill(['archived_at' => now()])->save();

        $this->audit->logModelChange(
            eventType: 'indicator',
            action: 'archive',
            model: $indicator,
            before: ['archived_at' => null],
            after: ['archived_at' => optional($indicator->archived_at)?->toDateTimeString()],
            userId: $userId,
        );

        return $indicator;
    }

    /**
     * Historial de versiones con el diff respecto a la anterior.
     *
     * @return list<array{
     *     id: int,
     *     version_number: int,
     *     status: string,
     *     status_label: string,
     *     published_at: string|null,
     *     name: string,
     *     formula: string,
     *     changes: list<string>
     * }>
     */
    public function versionHistory(QualityPlanningIndicator $indicator): array
    {
        $indicator->loadMissing('versions');
        $rows = [];
        $previous = null;

        foreach ($indicator->versions->sortBy('version_number')->values() as $version) {
            $rows[] = [
                'id' => (int) $version->id,
                'version_number' => (int) $version->version_number,
                'status' => (string) $version->status,
                'status_label' => $version->statusLabel(),
                'published_at' => $version->published_at?->format('d/m/Y H:i'),
                'name' => (string) $version->name,
                'formula' => (string) $version->formula,
                'changes' => $previous instanceof QualityPlanningIndicatorVersion
                    ? $this->diffVersions($previous, $version)
                    : ['Versión inicial.'],
            ];
            $previous = $version;
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function diffVersions(
        QualityPlanningIndicatorVersion $from,
        QualityPlanningIndicatorVersion $to,
    ): array {
        $changes = [];

        if ((string) $from->name !== (string) $to->name) {
            $changes[] = 'Nombre: '.$from->name.' → '.$to->name;
        }
        if ((string) $from->objective !== (string) $to->objective) {
            $changes[] = 'Objetivo actualizado.';
        }
        if ((string) $from->insumos !== (string) $to->insumos) {
            $changes[] = 'Insumos para la medición actualizados.';
        }
        if ((string) $from->medicion !== (string) $to->medicion) {
            $changes[] = 'Medición actualizada.';
        }
        if ((string) $from->process_key !== (string) $to->process_key) {
            $changes[] = 'Proceso actualizado.';
        }
        if ((string) $from->formula !== (string) $to->formula) {
            $changes[] = 'Fórmula: '.$from->formula.' → '.$to->formula;
        }
        if ((string) $from->target_operator !== (string) $to->target_operator
            || (float) $from->target_value !== (float) $to->target_value) {
            $changes[] = 'Meta: '.$from->target_operator.' '.$this->formatScalar($from->target_value)
                .' → '.$to->target_operator.' '.$this->formatScalar($to->target_value);
        }
        if ($this->formatScalar($from->critical_value) !== $this->formatScalar($to->critical_value)) {
            $changes[] = 'Valor crítico actualizado.';
        }
        if ((string) $from->unit !== (string) $to->unit) {
            $changes[] = 'Unidad actualizada.';
        }

        $fromFields = collect($from->fields ?? [])->filter(fn ($field): bool => is_array($field) && (string) ($field['key'] ?? '') !== '');
        $toFields = collect($to->fields ?? [])->filter(fn ($field): bool => is_array($field) && (string) ($field['key'] ?? '') !== '');
        $fromByKey = $fromFields->keyBy(fn (array $field): string => (string) $field['key']);
        $toByKey = $toFields->keyBy(fn (array $field): string => (string) $field['key']);

        foreach ($toByKey as $key => $field) {
            if (! $fromByKey->has($key)) {
                $changes[] = 'Campo agregado: '.(string) ($field['label'] ?? $key).' ('.$key.')';

                continue;
            }
            $previous = $fromByKey->get($key);
            if ((string) ($previous['label'] ?? '') !== (string) ($field['label'] ?? '')) {
                $changes[] = 'Etiqueta de '.$key.': '.(string) ($previous['label'] ?? '').' → '.(string) ($field['label'] ?? '');
            }
        }
        foreach ($fromByKey as $key => $field) {
            if (! $toByKey->has($key)) {
                $changes[] = 'Campo quitado: '.(string) ($field['label'] ?? $key).' ('.$key.')';
            }
        }

        return $changes !== [] ? $changes : ['Sin cambios de definición.'];
    }

    private function formatScalar(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return (string) $value;
    }

    /**
     * @param  array<int, mixed>  $fields
     * @return list<array{key: string, label: string, decimals: int, required: bool, sort: int}>
     */
    public function normalizeFields(array $fields): array
    {
        $normalized = [];

        foreach (array_values($fields) as $index => $field) {
            if (! is_array($field)) {
                continue;
            }

            $normalized[] = [
                'key' => (string) ($field['key'] ?? ''),
                'label' => UppercaseFormText::uppercaseString((string) ($field['label'] ?? '')),
                'decimals' => in_array((int) ($field['decimals'] ?? 2), [0, 2], true) ? (int) ($field['decimals'] ?? 2) : 2,
                'required' => (bool) ($field['required'] ?? false),
                'sort' => $index + 1,
            ];
        }

        return $normalized;
    }

    /**
     * @param  list<array{key: string, label: string, decimals: int, required: bool, sort: int}>  $fields
     */
    public function assertFormula(string $formula, array $fields): void
    {
        $keys = array_values(array_filter(array_column($fields, 'key')));
        $errors = $this->evaluator->validate($formula, $keys);

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'formula' => $errors,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{key: string, label: string, decimals: int, required: bool, sort: int}>  $fields
     * @return array<string, mixed>
     */
    private function versionAttributes(array $data, array $fields): array
    {
        return [
            'name' => (string) $data['name'],
            'objective' => $data['objective'] ?? null,
            'insumos' => $data['insumos'] ?? null,
            'medicion' => $data['medicion'] ?? null,
            'process_key' => ($data['process_key'] ?? null) ?: null,
            'unit' => (string) ($data['unit'] ?? QualityPlanningIndicatorVersion::UNIT_PERCENTAGE),
            'frequency' => (string) ($data['frequency'] ?? QualityPlanningIndicatorVersion::FREQUENCY_MONTHLY),
            'target_value' => $data['target_value'],
            'target_operator' => (string) ($data['target_operator'] ?? '>='),
            'critical_value' => $data['critical_value'] ?? null,
            'formula' => (string) $data['formula'],
            'fields' => $fields,
        ];
    }

    private function assertNotArchived(QualityPlanningIndicator $indicator): void
    {
        if ($indicator->isArchived()) {
            throw ValidationException::withMessages([
                'status' => 'El indicador está archivado y no se puede modificar.',
            ]);
        }
    }
}
