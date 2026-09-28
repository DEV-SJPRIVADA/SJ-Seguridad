<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionCargo;
use App\Models\CursoEscuela;
use App\Models\CursoTipo;
use App\Models\EmployeeCurso;
use App\Models\EmployeeFichaProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class AcreditacionExportApoRowResolver
{
    public const NOVEDAD_FICHA_INCOMPLETA = 'ficha_incompleta';

    public const NOVEDAD_SIN_CURSO_MATCH = 'sin_curso_match';

    public const NOVEDAD_CURSO_VENCIDO = 'curso_vencido_sin_alterna';

    public const NOVEDAD_CODIGO_CURSO_VACIO = 'codigo_curso_vacio';

    public const NOVEDAD_ESCUELA_NO_CATALOGO = 'escuela_no_catalogo';

    public const NOVEDAD_CARGO_NO_RESOLUBLE = 'cargo_acreditacion_no_resoluble';

    public const NOVEDAD_CARGO_AMBIGUO = 'cargo_acreditacion_ambiguo';

    public const POLICY_VIGENTE = 'VIGENTE';

    public const POLICY_VIGENTE_ACTUALIZAR = 'VIGENTE_ACTUALIZAR';

    /**
     * @var list<string>
     */
    public const HARD_NOVEDADES = [
        self::NOVEDAD_FICHA_INCOMPLETA,
    ];

    public function __construct(
        private readonly AcreditacionCargoMatchNormalizer $normalizer,
    ) {}

    /**
     * Resuelve una fila candidata a columnas de negocio + valida/motivo (solo preview).
     *
     * @return array{
     *     acreditado_id: int,
     *     document_number: string,
     *     cargo_apo: string,
     *     nombre1: string,
     *     nombre2: string,
     *     apellido1: string,
     *     apellido2: string,
     *     fecha_nacimiento: string,
     *     genero: string,
     *     cargo: string,
     *     fecha_vinculacion: string,
     *     codigo_curso: string,
     *     nit_escuela: string,
     *     nro: string,
     *     tipo_curso: string,
     *     estado_curso: string,
     *     valida: bool,
     *     hard_block: bool,
     *     soft_novedad: bool,
     *     motivo: string,
     *     motivo_codes: list<string>,
     * }
     */
    public function resolve(AcreditacionAcreditado $acreditado, string $vigenciaPolicy): array
    {
        $policy = $this->normalizePolicy($vigenciaPolicy);
        $motivoCodes = [];

        $ficha = EmployeeFichaProfile::query()
            ->where('document_number', $acreditado->document_number)
            ->where('employment_status', EmployeeFichaProfile::STATUS_ACTIVO)
            ->orderByDesc('id')
            ->first();

        $nombre1 = '';
        $nombre2 = '';
        $apellido1 = '';
        $apellido2 = '';
        $fechaNacimiento = '';
        $genero = '';
        $fechaVinculacion = '';

        if ($ficha === null || $this->fichaIsIncomplete($ficha)) {
            $motivoCodes[] = self::NOVEDAD_FICHA_INCOMPLETA;
        } else {
            $nombre1 = trim((string) $ficha->first_name);
            $nombre2 = trim((string) ($ficha->second_name ?? ''));
            $apellido1 = trim((string) $ficha->first_surname);
            $apellido2 = trim((string) ($ficha->second_surname ?? ''));
            $fechaNacimiento = $this->formatDate($ficha->birth_date);
            $genero = $this->mapGenero((string) $ficha->sex);
            $fechaVinculacion = $this->formatDate($ficha->hire_date);
        }

        $cargoResult = $this->resolveCargoAcreditacion((string) $acreditado->cargo_apo);
        $cargo = $cargoResult['value'];
        if ($cargoResult['code'] !== null) {
            $motivoCodes[] = $cargoResult['code'];
        }

        $cursoResult = $this->resolveCurso(
            (string) $acreditado->document_number,
            (string) $acreditado->cargo_apo,
            $policy,
            (string) $cargo,
        );

        $codigoCurso = $cursoResult['codigo_curso'];
        $nitEscuela = $cursoResult['nit_escuela'];
        $nro = $cursoResult['nro'];
        $tipoCurso = $cursoResult['tipo_curso'];
        $estadoCurso = $cursoResult['estado_curso'];

        foreach ($cursoResult['codes'] as $code) {
            $motivoCodes[] = $code;
        }

        $motivoCodes = array_values(array_unique($motivoCodes));
        $hardBlock = $this->hasHardBlock($motivoCodes);
        $softNovedad = ! $hardBlock && $motivoCodes !== [];
        $valida = $motivoCodes === [];

        return [
            'acreditado_id' => (int) $acreditado->id,
            'document_number' => (string) $acreditado->document_number,
            'cargo_apo' => (string) $acreditado->cargo_apo,
            'nombre1' => $nombre1,
            'nombre2' => $nombre2,
            'apellido1' => $apellido1,
            'apellido2' => $apellido2,
            'fecha_nacimiento' => $fechaNacimiento,
            'genero' => $genero,
            'cargo' => $cargo,
            'fecha_vinculacion' => $fechaVinculacion,
            'codigo_curso' => $codigoCurso,
            'nit_escuela' => $nitEscuela,
            'nro' => $nro,
            'tipo_curso' => $tipoCurso,
            'estado_curso' => $estadoCurso,
            'valida' => $valida,
            'hard_block' => $hardBlock,
            'soft_novedad' => $softNovedad,
            'motivo' => $this->motivoLabel($motivoCodes),
            'motivo_codes' => $motivoCodes,
        ];
    }

    public function normalizePolicy(string $policy): string
    {
        $policy = trim($policy);

        if ($policy === self::POLICY_VIGENTE_ACTUALIZAR) {
            return self::POLICY_VIGENTE_ACTUALIZAR;
        }

        return self::POLICY_VIGENTE;
    }

    /**
     * @param  list<string>  $codes
     */
    public function hasHardBlock(array $codes): bool
    {
        foreach ($codes as $code) {
            if (in_array($code, self::HARD_NOVEDADES, true)) {
                return true;
            }
        }

        return false;
    }

    private function fichaIsIncomplete(EmployeeFichaProfile $ficha): bool
    {
        return trim((string) $ficha->document_number) === ''
            || trim((string) $ficha->first_name) === ''
            || trim((string) $ficha->first_surname) === ''
            || $ficha->birth_date === null
            || trim((string) ($ficha->sex ?? '')) === ''
            || $ficha->hire_date === null;
    }

    /**
     * SuperVigilancia: Genero 1 = masculino, 2 = femenino.
     * Acepta M/F, 1/2 o sex textual.
     */
    private function mapGenero(string $sex): string
    {
        $raw = trim($sex);
        $norm = $this->normalizer->normalizeCargo($raw);

        if (in_array($norm, ['1', 'M', 'MASCULINO', 'HOMBRE', 'MALE'], true)) {
            return '1';
        }

        if (in_array($norm, ['2', 'F', 'FEMENINO', 'MUJER', 'FEMALE'], true)) {
            return '2';
        }

        return $raw;
    }

    /**
     * @return array{value: string, code: string|null}
     */
    private function resolveCargoAcreditacion(string $cargoApo): array
    {
        $apoNorm = $this->normalizer->normalizeCargo($cargoApo);

        if ($apoNorm === '') {
            return ['value' => '', 'code' => self::NOVEDAD_CARGO_NO_RESOLUBLE];
        }

        /** @var Collection<int, AcreditacionCargo> $actives */
        $actives = AcreditacionCargo::query()
            ->active()
            ->get()
            ->filter(fn (AcreditacionCargo $row): bool => $this->normalizer->cargosMatch($row->cargo_apo, $cargoApo))
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        if ($actives->isEmpty()) {
            return ['value' => '', 'code' => self::NOVEDAD_CARGO_NO_RESOLUBLE];
        }

        $distinct = $actives
            ->map(fn (AcreditacionCargo $row): string => trim((string) $row->cargo_acreditacion))
            ->unique()
            ->values();

        if ($distinct->count() > 1) {
            return [
                'value' => (string) $actives->first()?->cargo_acreditacion,
                'code' => self::NOVEDAD_CARGO_AMBIGUO,
            ];
        }

        return [
            'value' => (string) $actives->first()?->cargo_acreditacion,
            'code' => null,
        ];
    }

    /**
     * @return array{
     *     codigo_curso: string,
     *     nit_escuela: string,
     *     nro: string,
     *     tipo_curso: string,
     *     estado_curso: string,
     *     codes: list<string>,
     * }
     */
    private function resolveCurso(
        string $documentNumber,
        string $cargoApo,
        string $policy,
        string $cargoAcreditacionCode,
    ): array {
        $empty = [
            'codigo_curso' => '',
            'nit_escuela' => '',
            'nro' => '',
            'tipo_curso' => '',
            'estado_curso' => '',
            'codes' => [],
        ];

        /** @var Collection<int, EmployeeCurso> $cursos */
        $cursos = EmployeeCurso::query()
            ->with(['cursoTipo', 'cursoEscuela'])
            ->where('document_number', $documentNumber)
            ->get();

        if ($cursos->isEmpty()) {
            $empty['codes'][] = self::NOVEDAD_SIN_CURSO_MATCH;

            return $empty;
        }

        $apoNorm = $this->normalizer->normalizeCargo($cargoApo);
        $codeNorm = $this->normalizer->normalizeCargo($cargoAcreditacionCode);
        $sameCargo = $cursos->filter(function (EmployeeCurso $curso) use ($apoNorm, $codeNorm): bool {
            $tipo = $curso->cursoTipo;
            if (! $tipo instanceof CursoTipo) {
                return false;
            }

            return $this->tipoMatchesCargo($tipo, $apoNorm, $codeNorm);
        });

        if ($sameCargo->isEmpty()) {
            $fallback = $this->pickNewestCurso($cursos);
            if ($fallback instanceof EmployeeCurso) {
                $empty['tipo_curso'] = trim((string) ($fallback->cursoTipo?->tipo_curso ?? ''));
                $empty['estado_curso'] = $fallback->computeVigencia();
            }

            $empty['codes'][] = self::NOVEDAD_SIN_CURSO_MATCH;

            return $empty;
        }

        $byPolicy = $sameCargo->filter(function (EmployeeCurso $curso) use ($policy): bool {
            return $this->vigenciaAllowed($curso->computeVigencia(), $policy);
        });

        if ($byPolicy->isEmpty()) {
            $hadVencido = $sameCargo->contains(
                fn (EmployeeCurso $curso): bool => $curso->computeVigencia() === EmployeeCurso::VIGENCIA_VENCIDO
            );

            // Mostrar en UI el mejor candidato del cargo aunque no cumpla política.
            $fallback = $this->pickNewestCurso($sameCargo);
            if ($fallback instanceof EmployeeCurso) {
                $empty['tipo_curso'] = trim((string) ($fallback->cursoTipo?->tipo_curso ?? ''));
                $empty['estado_curso'] = $fallback->computeVigencia();
            }

            $empty['codes'][] = $hadVencido
                ? self::NOVEDAD_CURSO_VENCIDO
                : self::NOVEDAD_SIN_CURSO_MATCH;

            return $empty;
        }

        $chosen = $this->pickNewestCurso($byPolicy);

        if (! $chosen instanceof EmployeeCurso) {
            $empty['codes'][] = self::NOVEDAD_SIN_CURSO_MATCH;

            return $empty;
        }

        $codes = [];
        $codigoCurso = trim((string) ($chosen->cursoTipo?->cursos ?? ''));
        if ($codigoCurso === '') {
            $codes[] = self::NOVEDAD_CODIGO_CURSO_VACIO;
        }

        $nro = trim((string) ($chosen->numero_curso ?? ''));
        $tipoCurso = trim((string) ($chosen->cursoTipo?->tipo_curso ?? ''));
        $estadoCurso = $chosen->computeVigencia();
        $escuelaResult = $this->resolveNitEscuela($chosen);
        $nitEscuela = $escuelaResult['nit'];

        if (! $escuelaResult['valid']) {
            $codes[] = self::NOVEDAD_ESCUELA_NO_CATALOGO;
        }

        return [
            'codigo_curso' => $codigoCurso,
            'nit_escuela' => $nitEscuela,
            'nro' => $nro,
            'tipo_curso' => $tipoCurso,
            'estado_curso' => $estadoCurso,
            'codes' => $codes,
        ];
    }

    /**
     * NitEscuela: 1) snapshot `escuela_nit` del registro; 2) FK escuela activa;
     * 3) código extraído de No.CURSO (ECSP0015-M… → 15) → catálogo Escuelas.
     *
     * @return array{nit: string, valid: bool}
     */
    private function resolveNitEscuela(EmployeeCurso $curso): array
    {
        $nitSnapshot = trim((string) ($curso->escuela_nit ?? ''));
        if ($nitSnapshot !== '') {
            return [
                'nit' => $nitSnapshot,
                'valid' => $this->escuelaIsValid($curso, $nitSnapshot),
            ];
        }

        if ($curso->curso_escuela_id !== null) {
            $escuela = $curso->cursoEscuela;
            if ($escuela instanceof CursoEscuela && $escuela->is_active) {
                $nitFk = trim((string) $escuela->nit);
                if ($nitFk !== '') {
                    return ['nit' => $nitFk, 'valid' => true];
                }
            }
        }

        $codigo = CursoEscuela::extractCodigoFromNumeroCurso((string) ($curso->numero_curso ?? ''));
        if ($codigo === null) {
            return ['nit' => '', 'valid' => false];
        }

        $escuela = CursoEscuela::findActiveByNormalizedCodigo($codigo);
        if (! $escuela instanceof CursoEscuela) {
            return ['nit' => '', 'valid' => false];
        }

        $nit = trim((string) $escuela->nit);

        return [
            'nit' => $nit,
            'valid' => $nit !== '',
        ];
    }

    /**
     * @param  Collection<int, EmployeeCurso>  $cursos
     */
    private function pickNewestCurso(Collection $cursos): ?EmployeeCurso
    {
        $chosen = $cursos
            ->sort(function (EmployeeCurso $a, EmployeeCurso $b): int {
                $fa = $a->fecha_expedicion?->format('Y-m-d') ?? '';
                $fb = $b->fecha_expedicion?->format('Y-m-d') ?? '';
                $cmp = strcmp($fb, $fa);
                if ($cmp !== 0) {
                    return $cmp;
                }

                return $b->id <=> $a->id;
            })
            ->first();

        return $chosen instanceof EmployeeCurso ? $chosen : null;
    }

    private function tipoMatchesCargo(CursoTipo $tipo, string $apoNorm, string $cargoAcreditacionCode): bool
    {
        if ($apoNorm === '') {
            return false;
        }

        $tipoNorm = preg_replace('/\s+/u', '', $this->normalizer->normalizeCargo($tipo->tipo_curso)) ?? '';
        $expectedF = 'F.'.$apoNorm;
        $expectedR = 'R.'.$apoNorm;
        $isFamiliaFR = (bool) preg_match('/^[FR]\./', $tipoNorm)
            || $tipoNorm === $expectedF
            || $tipoNorm === $expectedR;

        if (! $isFamiliaFR) {
            return false;
        }

        if ($tipoNorm === $expectedF || $tipoNorm === $expectedR) {
            return true;
        }

        $cargoAcredit = trim((string) ($tipo->cargo_acredit ?? ''));
        if ($cargoAcredit === '') {
            return false;
        }

        $acreditNorm = $this->normalizer->normalizeCargo($cargoAcredit);
        if ($acreditNorm === $apoNorm) {
            return true;
        }

        return $cargoAcreditacionCode !== '' && $acreditNorm === $cargoAcreditacionCode;
    }

    private function vigenciaAllowed(string $vigencia, string $policy): bool
    {
        if ($policy === self::POLICY_VIGENTE_ACTUALIZAR) {
            return in_array($vigencia, [
                EmployeeCurso::VIGENCIA_VIGENTE,
                EmployeeCurso::VIGENCIA_ACTUALIZAR,
            ], true);
        }

        return $vigencia === EmployeeCurso::VIGENCIA_VIGENTE;
    }

    private function escuelaIsValid(EmployeeCurso $curso, string $nitEscuela): bool
    {
        if ($curso->curso_escuela_id !== null) {
            $escuela = $curso->cursoEscuela;
            if ($escuela instanceof CursoEscuela && $escuela->is_active) {
                return true;
            }
        }

        $nit = trim($nitEscuela);
        if ($nit === '') {
            return false;
        }

        return CursoEscuela::query()
            ->active()
            ->get()
            ->contains(fn (CursoEscuela $row): bool => trim((string) $row->nit) === $nit);
    }

    private function formatDate(mixed $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        try {
            return Carbon::parse($date)->format('d/m/Y');
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * @param  list<string>  $codes
     */
    private function motivoLabel(array $codes): string
    {
        if ($codes === []) {
            return '';
        }

        $labels = [
            self::NOVEDAD_FICHA_INCOMPLETA => 'Ficha incompleta (bloqueo duro)',
            self::NOVEDAD_SIN_CURSO_MATCH => 'Sin curso F|R válido para el cargo',
            self::NOVEDAD_CURSO_VENCIDO => 'Curso vencido sin alternativa válida',
            self::NOVEDAD_CODIGO_CURSO_VACIO => 'CódigoCurso vacío en tipo de curso',
            self::NOVEDAD_ESCUELA_NO_CATALOGO => 'Escuela no está en catálogo activo',
            self::NOVEDAD_CARGO_NO_RESOLUBLE => 'Cargo acreditación no resoluble',
            self::NOVEDAD_CARGO_AMBIGUO => 'Cargo acreditación ambiguo',
        ];

        return implode('; ', array_map(
            fn (string $code): string => $labels[$code] ?? $code,
            $codes,
        ));
    }
}
