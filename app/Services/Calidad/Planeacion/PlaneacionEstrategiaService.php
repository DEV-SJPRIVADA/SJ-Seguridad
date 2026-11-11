<?php

namespace App\Services\Calidad\Planeacion;

use App\Models\QualityPlanning\QualityPlanningDofa;
use App\Models\QualityPlanning\QualityPlanningDofaItem;
use App\Models\QualityPlanning\QualityPlanningObjective;
use App\Models\QualityPlanning\QualityPlanningStrategy;
use App\Models\User;
use App\Support\UppercaseFormText;
use Illuminate\Support\Facades\DB;

/**
 * DOFA + objetivos + estrategias de Planeación estratégica (FEAT-051).
 * La estrategia cuelga del objetivo y el objetivo del DOFA: no hay huérfanos.
 */
class PlaneacionEstrategiaService
{
    public function __construct(
        private readonly PlaneacionAuditLogService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, list<string>>  $quadrants
     */
    public function createDofa(User $actor, array $data, array $quadrants): QualityPlanningDofa
    {
        return DB::transaction(function () use ($actor, $data, $quadrants): QualityPlanningDofa {
            $dofa = QualityPlanningDofa::query()->create([
                'title' => UppercaseFormText::uppercaseString((string) $data['title']),
                'period_label' => UppercaseFormText::uppercaseString((string) $data['period_label']),
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'],
                'status' => (string) ($data['status'] ?? QualityPlanningDofa::STATUS_DRAFT),
                'summary' => $this->nullableUppercase($data['summary'] ?? null),
                'created_by_user_id' => $actor->id,
            ]);

            $this->replaceItems($dofa, $quadrants);

            $this->audit->logModelChange(
                eventType: 'strategy',
                action: 'dofa_create',
                model: $dofa,
                before: null,
                after: $this->dofaSnapshot($dofa),
                userId: $actor->id,
            );

            return $dofa->load('items', 'objectives.strategies');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, list<string>>  $quadrants
     */
    public function updateDofa(User $actor, QualityPlanningDofa $dofa, array $data, array $quadrants): QualityPlanningDofa
    {
        return DB::transaction(function () use ($actor, $dofa, $data, $quadrants): QualityPlanningDofa {
            $before = $this->dofaSnapshot($dofa);

            $dofa->fill([
                'title' => UppercaseFormText::uppercaseString((string) $data['title']),
                'period_label' => UppercaseFormText::uppercaseString((string) $data['period_label']),
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'],
                'status' => (string) ($data['status'] ?? $dofa->status),
                'summary' => $this->nullableUppercase($data['summary'] ?? null),
            ])->save();

            $this->replaceItems($dofa, $quadrants);

            $this->audit->logModelChange(
                eventType: 'strategy',
                action: 'dofa_update',
                model: $dofa,
                before: $before,
                after: $this->dofaSnapshot($dofa->fresh('items')),
                userId: $actor->id,
            );

            return $dofa->fresh(['items', 'objectives.strategies']);
        });
    }

    public function activateDofa(User $actor, QualityPlanningDofa $dofa): QualityPlanningDofa
    {
        $before = ['status' => $dofa->status];
        $dofa->update(['status' => QualityPlanningDofa::STATUS_ACTIVE]);

        $this->audit->logModelChange(
            eventType: 'strategy',
            action: 'dofa_activate',
            model: $dofa,
            before: $before,
            after: ['status' => $dofa->status],
            userId: $actor->id,
        );

        return $dofa;
    }

    public function archiveDofa(User $actor, QualityPlanningDofa $dofa): QualityPlanningDofa
    {
        $before = ['status' => $dofa->status, 'archived_at' => $dofa->archived_at];

        $dofa->update([
            'status' => QualityPlanningDofa::STATUS_ARCHIVED,
            'archived_at' => now(),
        ]);

        $this->audit->logModelChange(
            eventType: 'strategy',
            action: 'dofa_archive',
            model: $dofa,
            before: $before,
            after: ['status' => $dofa->status, 'archived_at' => $dofa->archived_at],
            userId: $actor->id,
        );

        return $dofa;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createObjective(User $actor, QualityPlanningDofa $dofa, array $data): QualityPlanningObjective
    {
        $objective = $dofa->objectives()->create([
            'title' => UppercaseFormText::uppercaseString((string) $data['title']),
            'description' => $this->nullableUppercase($data['description'] ?? null),
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
            'sort' => $this->nextSort($dofa->objectives()->max('sort')),
        ]);

        $this->audit->logModelChange(
            eventType: 'strategy',
            action: 'objective_create',
            model: $objective,
            before: null,
            after: $objective->only(['id', 'dofa_id', 'title', 'starts_on', 'ends_on']),
            userId: $actor->id,
        );

        return $objective;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateObjective(User $actor, QualityPlanningObjective $objective, array $data): QualityPlanningObjective
    {
        $before = $objective->only(['title', 'description', 'starts_on', 'ends_on']);

        $objective->update([
            'title' => UppercaseFormText::uppercaseString((string) $data['title']),
            'description' => $this->nullableUppercase($data['description'] ?? null),
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
        ]);

        $this->audit->logModelChange(
            eventType: 'strategy',
            action: 'objective_update',
            model: $objective,
            before: $before,
            after: $objective->only(['title', 'description', 'starts_on', 'ends_on']),
            userId: $actor->id,
        );

        return $objective;
    }

    public function deleteObjective(User $actor, QualityPlanningObjective $objective): void
    {
        $before = $objective->only(['id', 'dofa_id', 'title']);
        $objective->delete();

        $this->audit->logModelChange(
            eventType: 'strategy',
            action: 'objective_delete',
            model: null,
            before: $before,
            after: null,
            userId: $actor->id,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createStrategy(User $actor, QualityPlanningObjective $objective, array $data): QualityPlanningStrategy
    {
        $strategy = $objective->strategies()->create([
            'title' => UppercaseFormText::uppercaseString((string) $data['title']),
            'description' => $this->nullableUppercase($data['description'] ?? null),
            'responsible' => $this->nullableUppercase($data['responsible'] ?? null),
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
            'progress' => max(0, min(100, (int) ($data['progress'] ?? 0))),
            'sort' => $this->nextSort($objective->strategies()->max('sort')),
        ]);

        $this->audit->logModelChange(
            eventType: 'strategy',
            action: 'strategy_create',
            model: $strategy,
            before: null,
            after: $strategy->only(['id', 'objective_id', 'title', 'responsible', 'progress']),
            userId: $actor->id,
        );

        return $strategy;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateStrategy(User $actor, QualityPlanningStrategy $strategy, array $data): QualityPlanningStrategy
    {
        $before = $strategy->only(['title', 'description', 'responsible', 'starts_on', 'ends_on', 'progress']);

        $strategy->update([
            'title' => UppercaseFormText::uppercaseString((string) $data['title']),
            'description' => $this->nullableUppercase($data['description'] ?? null),
            'responsible' => $this->nullableUppercase($data['responsible'] ?? null),
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
            'progress' => max(0, min(100, (int) ($data['progress'] ?? 0))),
        ]);

        $this->audit->logModelChange(
            eventType: 'strategy',
            action: 'strategy_update',
            model: $strategy,
            before: $before,
            after: $strategy->only(['title', 'description', 'responsible', 'starts_on', 'ends_on', 'progress']),
            userId: $actor->id,
        );

        return $strategy;
    }

    public function deleteStrategy(User $actor, QualityPlanningStrategy $strategy): void
    {
        $before = $strategy->only(['id', 'objective_id', 'title']);
        $strategy->delete();

        $this->audit->logModelChange(
            eventType: 'strategy',
            action: 'strategy_delete',
            model: null,
            before: $before,
            after: null,
            userId: $actor->id,
        );
    }

    /**
     * @param  array<string, list<string>>  $quadrants
     */
    private function replaceItems(QualityPlanningDofa $dofa, array $quadrants): void
    {
        $dofa->items()->delete();

        foreach (QualityPlanningDofaItem::QUADRANTS as $quadrant => $label) {
            $rows = $quadrants[$quadrant] ?? [];
            $sort = 0;

            foreach ($rows as $description) {
                $text = trim((string) $description);
                if ($text === '') {
                    continue;
                }

                $dofa->items()->create([
                    'quadrant' => $quadrant,
                    'description' => UppercaseFormText::uppercaseString($text),
                    'sort' => $sort++,
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function dofaSnapshot(QualityPlanningDofa $dofa): array
    {
        return [
            'title' => $dofa->title,
            'period_label' => $dofa->period_label,
            'starts_on' => $dofa->starts_on?->toDateString(),
            'ends_on' => $dofa->ends_on?->toDateString(),
            'status' => $dofa->status,
            'summary' => $dofa->summary,
            'items' => $dofa->items->map(fn (QualityPlanningDofaItem $item): array => [
                'quadrant' => $item->quadrant,
                'description' => $item->description,
            ])->all(),
        ];
    }

    private function nullableUppercase(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : UppercaseFormText::uppercaseString($text);
    }

    private function nextSort(mixed $max): int
    {
        return ((int) $max) + 1;
    }
}
