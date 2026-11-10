<?php

namespace App\Services\Calidad\Planeacion;

use App\Models\QualityPlanning\QualityPlanningCapture;
use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\QualityPlanning\QualityPlanningIndicatorVersion;
use App\Models\User;
use App\Support\UppercaseFormText;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PlaneacionCaptureService
{
    public function __construct(
        private readonly PlaneacionPeriodService $periods,
        private readonly FormulaEvaluator $evaluator,
        private readonly PlaneacionAuditLogService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(QualityPlanningIndicator $indicator, User $actor, array $data): QualityPlanningCapture
    {
        $year = (int) $data['year'];
        $month = (int) $data['month'];
        $replaceConfirmed = (bool) ($data['replace_confirmed'] ?? false);
        $period = $this->periods->versionForCapture($indicator, $year, $month);

        if (! $period->isCapturable()) {
            throw ValidationException::withMessages([
                'month' => $period->message !== ''
                    ? $period->message
                    : 'Este periodo no admite captura.',
            ]);
        }

        /** @var QualityPlanningIndicatorVersion $version */
        $version = $period->version;
        $input = $this->normalizeInput($version, is_array($data['input_data'] ?? null) ? $data['input_data'] : []);
        $this->assertRequiredFields($version, $input);

        try {
            $result = $this->evaluator->evaluate($version->formula, $input);
        } catch (DomainException|InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'input_data' => $e->getMessage(),
            ]);
        }

        $complies = $this->complies($result, $version);
        $analysis = UppercaseFormText::uppercaseString(trim((string) ($data['analysis_text'] ?? '')));
        $improvement = UppercaseFormText::uppercaseString(trim((string) ($data['improvement_required'] ?? '')));

        if ($analysis === '') {
            $analysis = null;
        }

        if (! $complies && ($improvement === null || $improvement === '')) {
            throw ValidationException::withMessages([
                'improvement_required' => 'El plan de mejora es obligatorio cuando no se cumple la meta.',
            ]);
        }

        if ($complies) {
            $improvement = null;
        }

        $existing = QualityPlanningCapture::query()
            ->where('indicator_version_id', $version->id)
            ->where('user_id', $actor->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($existing instanceof QualityPlanningCapture && ! $replaceConfirmed) {
            throw ValidationException::withMessages([
                'replace_confirmed' => 'Ya existe una captura de '.$this->periods->monthLabel($month).' '.$year.'. Si continúa, se reemplazarán los datos actuales.',
            ]);
        }

        $payload = [
            'indicator_version_id' => $version->id,
            'user_id' => $actor->id,
            'year' => $year,
            'month' => $month,
            'input_data' => $input,
            'result_value' => $result,
            'complies' => $complies,
            'analysis_text' => $analysis,
            'improvement_required' => $improvement,
            'updated_by_user_id' => $actor->id,
        ];

        return DB::transaction(function () use ($existing, $payload, $actor, $indicator, $version): QualityPlanningCapture {
            if ($existing instanceof QualityPlanningCapture) {
                $before = [
                    'result_value' => $existing->result_value,
                    'complies' => $existing->complies,
                    'input_data' => $existing->input_data,
                ];
                $existing->fill($payload)->save();

                $this->audit->logModelChange(
                    eventType: 'capture',
                    action: 'replace',
                    model: $existing,
                    before: $before,
                    after: [
                        'result_value' => $existing->result_value,
                        'complies' => $existing->complies,
                        'input_data' => $existing->input_data,
                        'indicator_id' => $indicator->id,
                        'version' => $version->version_number,
                    ],
                    userId: $actor->id,
                );

                return $existing;
            }

            $payload['created_by_user_id'] = $actor->id;
            $capture = QualityPlanningCapture::query()->create($payload);

            $this->audit->logModelChange(
                eventType: 'capture',
                action: 'create',
                model: $capture,
                before: null,
                after: [
                    'result_value' => $capture->result_value,
                    'complies' => $capture->complies,
                    'indicator_id' => $indicator->id,
                    'version' => $version->version_number,
                    'year' => $capture->year,
                    'month' => $capture->month,
                ],
                userId: $actor->id,
            );

            return $capture;
        });
    }

    public function complies(float $result, QualityPlanningIndicatorVersion $version): bool
    {
        $target = (float) $version->target_value;

        return match ((string) $version->target_operator) {
            '>=' => $result >= $target,
            '<=' => $result <= $target,
            '>' => $result > $target,
            '<' => $result < $target,
            '=', '==' => abs($result - $target) < 0.0001,
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, float>
     */
    public function normalizeInput(QualityPlanningIndicatorVersion $version, array $input): array
    {
        $normalized = [];

        foreach ($version->fields ?? [] as $field) {
            if (! is_array($field)) {
                continue;
            }

            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $raw = $input[$key] ?? null;
            if ($raw === null || $raw === '') {
                continue;
            }

            $normalized[$key] = (float) $raw;
        }

        return $normalized;
    }

    /**
     * @param  array<string, float>  $input
     */
    public function assertRequiredFields(QualityPlanningIndicatorVersion $version, array $input): void
    {
        $errors = [];

        foreach ($version->fields ?? [] as $field) {
            if (! is_array($field) || empty($field['required'])) {
                continue;
            }

            $key = (string) ($field['key'] ?? '');
            if ($key === '' || array_key_exists($key, $input)) {
                continue;
            }

            $label = (string) ($field['label'] ?? $key);
            $errors[$key] = 'El campo '.$label.' es obligatorio.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'input_data' => array_values($errors),
            ]);
        }
    }

    public function existingCapture(
        QualityPlanningIndicatorVersion $version,
        User $user,
        int $year,
        int $month,
    ): ?QualityPlanningCapture {
        return QualityPlanningCapture::query()
            ->where('indicator_version_id', $version->id)
            ->where('user_id', $user->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }
}
