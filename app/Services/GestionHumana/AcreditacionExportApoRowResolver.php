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

    /** Cargo de Acreditados (`cargo`) no está en Gestionar cargos (`cargo_manager`). Informativa. */
    public const NOVEDAD_CARGO_ACREDITADO_NO_CATALOGO = 'cargo_acreditado_no_catalogo';

    /** Cargo del perfil (`position_name`) no está en Gestionar cargos (`cargo_manager`). Informativa. */
    public const NOVEDAD_CARGO_PERFIL_NO_CATALOGO = 'cargo_perfil_no_catalogo';

    /** Cargo de Acreditados y del perfil tienen distinto número de cargo de acreditación. Informativa. */
    public const NOVEDAD_CARGO_PERFIL_DISTINTO = 'cargo_perfil_distinto';

    /** Manual: cédula sin registro en Acreditados (se usa el cargo del perfil). Informativa. */
    public const NOVEDAD_SIN_ACREDITADO = 'sin_acreditado';

    public const NOVEDAD_FUERA_UNIVERSO = 'fuera_universo_export_apo';

    public const NOVEDAD_REGISTRO_NO_ENCONTRADO = 'registro_no_encontrado';

    public const POLICY_VIGENTE = 'VIGENTE';

    public const POLICY_VIGENTE_ACTUALIZAR = 'VIGENTE_ACTUALIZAR';

    /**
     * Solo Export Apo Manual: curso más reciente de un tipo del cargo sin importar su vigencia
     * (VIGENTE, ACTUALIZAR o VENCIDO). No se ofrece en Automática.
     */
    public const POLICY_CUALQUIERA = 'CUALQUIERA';

    /**
     * Fecha vinc. Export Apo: siempre el día anterior (America/Bogota).
     * Automática usa d/m/Y; Manual UI usa Y-m-d.
     */
    public static function defaultFechaVinculacion(string $format = 'd/m/Y'): string
    {
        return Carbon::now('America/Bogota')->startOfDay()->subDay()->format($format);
    }

    /**
     * @var list<string>
     */
    public const HARD_NOVEDADES = [
        self::NOVEDAD_FICHA_INCOMPLETA,
        self::NOVEDAD_FUERA_UNIVERSO,
        self::NOVEDAD_REGISTRO_NO_ENCONTRADO,
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
        // Fechavinculacion APO: siempre ayer (no se toma hire_date de ficha).
        $fechaVinculacion = self::defaultFechaVinculacion('d/m/Y');

        if ($ficha === null || $this->fichaIsIncomplete($ficha)) {
            $motivoCodes[] = self::NOVEDAD_FICHA_INCOMPLETA;
        } else {
            $nombre1 = trim((string) $ficha->first_name);
            $nombre2 = trim((string) ($ficha->second_name ?? ''));
            $apellido1 = trim((string) $ficha->first_surname);
            $apellido2 = trim((string) ($ficha->second_surname ?? ''));
            $fechaNacimiento = $this->formatDate($ficha->birth_date);
            $genero = $this->mapGenero((string) $ficha->sex);
        }

        // Número de cargo de acreditación del registro (cargo_apo → Gestionar cargos): define qué
        // tipos de curso sirven. Un supervisor puede tener registro ESCOLTA (2) y otro SUPERVISOR (4).
        $cargoResult = $this->resolveCargoAcreditacion((string) $acreditado->cargo_apo);
        $cargo = $cargoResult['value'];
        if ($cargoResult['code'] !== null) {
            $motivoCodes[] = $cargoResult['code'];
        }

        // Cruce informativo: cargo de Acreditados vs cargo del perfil, ambos vía cargo_manager.
        foreach ($this->compareAcreditadoVsPerfil((string) $acreditado->cargo, $ficha?->position_name) as $code) {
            $motivoCodes[] = $code;
        }

        $cursoResult = $this->resolveCurso((string) $acreditado->document_number, (string) $cargo, $policy);

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
            'full_name' => (string) $acreditado->full_name,
            'ficha_cargo' => (string) $acreditado->cargo,
            'vigencia_acr' => optional($acreditado->vigencia_acr)?->format('Y-m-d') ?: '',
            'fecha_solicitud' => optional($acreditado->fecha_solicitud)?->format('Y-m-d') ?: '',
            'estado' => (string) $acreditado->estado,
            'estado_label' => $acreditado->estadoLabel(),
            'renovacion' => (string) ($acreditado->renovacion ?? ''),
            'observaciones' => (string) ($acreditado->observaciones ?? ''),
            'update_url' => route('gestion-humana.acreditaciones.acreditados.update', $acreditado),
            'editable' => true,
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

    /**
     * Número de cargo de acreditación de un cargo de nómina/ficha (Gestionar cargos → `cargo_manager`).
     * Null si el cargo está vacío o no está en el catálogo activo.
     */
    public function cargoNumberByManager(?string $cargo): ?string
    {
        if ($this->normalizer->normalizeCargo($cargo) === '') {
            return null;
        }

        $row = AcreditacionCargo::query()
            ->active()
            ->get()
            ->filter(fn (AcreditacionCargo $item): bool => $this->normalizer->cargosMatch($item->cargo_manager, $cargo))
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->first();

        $number = trim((string) ($row?->cargo_acreditacion ?? ''));

        return $number !== '' ? $number : null;
    }

    /**
     * Número de cargo de acreditación de un `cargo_apo` (Gestionar cargos → `cargo_apo`). Null si no resuelve.
     */
    public function cargoNumberByApo(?string $cargoApo): ?string
    {
        $value = trim($this->resolveCargoAcreditacion((string) $cargoApo)['value']);

        return $value !== '' ? $value : null;
    }

    /**
     * Novedades informativas del cruce cargo Acreditados ↔ cargo perfil (mismo número de cargo de acreditación).
     *
     * @return list<string>
     */
    public function compareAcreditadoVsPerfil(?string $cargoAcreditado, ?string $cargoPerfil): array
    {
        $codes = [];
        $numAcreditado = $this->cargoNumberByManager($cargoAcreditado);
        $numPerfil = $this->cargoNumberByManager($cargoPerfil);

        if ($numAcreditado === null) {
            $codes[] = self::NOVEDAD_CARGO_ACREDITADO_NO_CATALOGO;
        }

        if ($cargoPerfil !== null && $numPerfil === null) {
            $codes[] = self::NOVEDAD_CARGO_PERFIL_NO_CATALOGO;
        }

        if ($numAcreditado !== null && $numPerfil !== null && $numAcreditado !== $numPerfil) {
            $codes[] = self::NOVEDAD_CARGO_PERFIL_DISTINTO;
        }

        return $codes;
    }

    /**
     * Curso más reciente de la cédula cuyo tipo (activo) tenga `cargo_acredit` = número de cargo,
     * filtrado por la política de vigencia. Usado por Automática (vía resolve) y Manual.
     *
     * @return array{codigo_curso: string, nit_escuela: string, nro: string, tipo_curso: string, estado_curso: string, codes: list<string>}
     */
    public function resolveCursoByCargoNumber(string $documentNumber, ?string $cargoNumber, string $policy): array
    {
        return $this->resolveCurso($documentNumber, (string) $cargoNumber, $this->normalizePolicy($policy));
    }

    /**
     * Etiquetas legibles de novedades (para avisos en Manual).
     *
     * @param  list<string>  $codes
     */
    public function novedadesLabel(array $codes): string
    {
        return $this->motivoLabel(array_values(array_unique($codes)));
    }

    public function normalizePolicy(string $policy): string
    {
        $policy = trim($policy);

        if ($policy === self::POLICY_VIGENTE_ACTUALIZAR) {
            return self::POLICY_VIGENTE_ACTUALIZAR;
        }

        if ($policy === self::POLICY_CUALQUIERA) {
            return self::POLICY_CUALQUIERA;
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
    private function resolveCurso(string $documentNumber, string $cargoNumber, string $policy): array
    {
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

        // Tipos de curso válidos = tipos activos con cargo_acredit igual al número de cargo de acreditación.
        $numberNorm = $this->normalizer->normalizeCargo($cargoNumber);
        $sameCargo = $cursos->filter(function (EmployeeCurso $curso) use ($numberNorm): bool {
            $tipo = $curso->cursoTipo;

            return $numberNorm !== ''
                && $tipo instanceof CursoTipo
                && (bool) $tipo->is_active
                && $this->normalizer->normalizeCargo($tipo->cargo_acredit) === $numberNorm;
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

    private function vigenciaAllowed(string $vigencia, string $policy): bool
    {
        if ($policy === self::POLICY_CUALQUIERA) {
            return true;
        }

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
            self::NOVEDAD_SIN_CURSO_MATCH => 'Sin curso de un tipo correspondiente al cargo de acreditación',
            self::NOVEDAD_CURSO_VENCIDO => 'Curso vencido sin alternativa válida',
            self::NOVEDAD_CODIGO_CURSO_VACIO => 'CódigoCurso vacío en tipo de curso',
            self::NOVEDAD_ESCUELA_NO_CATALOGO => 'Escuela no está en catálogo activo',
            self::NOVEDAD_CARGO_NO_RESOLUBLE => 'Cargo APO no está en Gestionar cargos (regístrelo en el catálogo)',
            self::NOVEDAD_CARGO_AMBIGUO => 'Cargo acreditación ambiguo',
            self::NOVEDAD_CARGO_ACREDITADO_NO_CATALOGO => 'Cargo de Acreditados no está en Gestionar cargos (regístrelo en el catálogo)',
            self::NOVEDAD_CARGO_PERFIL_NO_CATALOGO => 'Cargo del perfil no está en Gestionar cargos (regístrelo en el catálogo)',
            self::NOVEDAD_CARGO_PERFIL_DISTINTO => 'Cargo de Acreditados y del perfil tienen distinto cargo de acreditación',
            self::NOVEDAD_SIN_ACREDITADO => 'Sin registro en Acreditados: se usó el cargo del perfil',
            self::NOVEDAD_FUERA_UNIVERSO => 'Fuera del universo Export Apo (estado o sin ficha activa)',
            self::NOVEDAD_REGISTRO_NO_ENCONTRADO => 'Registro no encontrado',
        ];

        return implode('; ', array_map(
            fn (string $code): string => $labels[$code] ?? $code,
            $codes,
        ));
    }

    /**
     * Marca una fila resuelta como bloqueo duro (fuera del universo candidato).
     *
     * @param  array<string, mixed>  $resolved
     * @return array<string, mixed>
     */
    public function markFueraUniverso(array $resolved): array
    {
        return $this->appendHardBlock($resolved, self::NOVEDAD_FUERA_UNIVERSO);
    }

    /**
     * Stub de fila para un ID solicitado que ya no existe en Acreditados.
     *
     * @return array<string, mixed>
     */
    public function missingRecordStub(int $acreditadoId): array
    {
        $codes = [self::NOVEDAD_REGISTRO_NO_ENCONTRADO];

        return [
            'acreditado_id' => $acreditadoId,
            'document_number' => '',
            'cargo_apo' => '',
            'full_name' => '',
            'ficha_cargo' => '',
            'vigencia_acr' => '',
            'fecha_solicitud' => '',
            'estado' => '',
            'estado_label' => '',
            'renovacion' => '',
            'observaciones' => '',
            'update_url' => '',
            'editable' => false,
            'nombre1' => '',
            'nombre2' => '',
            'apellido1' => '',
            'apellido2' => '',
            'fecha_nacimiento' => '',
            'genero' => '',
            'cargo' => '',
            'fecha_vinculacion' => '',
            'codigo_curso' => '',
            'nit_escuela' => '',
            'nro' => '',
            'tipo_curso' => '',
            'estado_curso' => '',
            'valida' => false,
            'hard_block' => true,
            'soft_novedad' => false,
            'motivo' => $this->motivoLabel($codes),
            'motivo_codes' => $codes,
        ];
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return array<string, mixed>
     */
    private function appendHardBlock(array $resolved, string $code): array
    {
        /** @var list<string> $codes */
        $codes = array_values(array_unique(array_merge(
            array_map('strval', $resolved['motivo_codes'] ?? []),
            [$code],
        )));

        $resolved['motivo_codes'] = $codes;
        $resolved['hard_block'] = true;
        $resolved['soft_novedad'] = false;
        $resolved['valida'] = false;
        $resolved['motivo'] = $this->motivoLabel($codes);

        return $resolved;
    }
}
