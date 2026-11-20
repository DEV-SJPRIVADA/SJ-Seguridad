<?php

namespace App\Exports;

use App\Models\Calidad\MtGi11Solicitud;
use App\Support\DisplayDate;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export de la matriz MT-GI-11.
 *
 * La primera columna es el `ID`: como el consecutivo puede repetirse, es el
 * único dato que identifica la fila sin ambigüedad.
 *
 * `BaseExport::downloadFromQuery` entrega a `mapRow` el modelo y luego lee las
 * columnas del array devuelto; por eso las claves de las columnas coinciden con
 * las etiquetas y el mapeo se hace en `mapRow`.
 */
class MtGi11Export extends BaseExport
{
    /**
     * Orden de columnas del archivo. Debe coincidir con `mapRow()`.
     */
    private const LABELS = [
        'ID',
        'Fecha solicitud',
        'Usuario que solicitó',
        'Proceso',
        'Consecutivo',
        'Tipo de documento',
        'Nombre del documento',
        'Tipo de solicitud',
        'Código actual',
        'Versión actual',
        'Justificación',
        'Responsable del proceso',
        'Cargo del responsable',
        'Código de estandarización',
        'Fecha estandarización',
        'Versión actualizada',
        'Estado',
    ];

    /**
     * @param  Builder<MtGi11Solicitud>  $query
     */
    public static function downloadFilteredQuery(Builder $query): StreamedResponse
    {
        return BaseExport::downloadFromQuery(
            $query,
            self::columnDefinitions(),
            'mt_gi_11_matriz_'.now()->format('Y-m-d').'.xlsx',
            'MT-GI-11 Matrices — '.config('app.name'),
            fn (MtGi11Solicitud $solicitud): array => self::mapRow($solicitud),
            max(1, (int) config('mt_gi_11.export.chunk_size', 500)),
            (new MtGi11Solicitud)->getQualifiedKeyName(),
        );
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public static function columnDefinitions(): array
    {
        return array_map(
            fn (string $label): array => ['key' => $label, 'label' => $label],
            self::LABELS,
        );
    }

    /**
     * @return array<string, string>
     */
    private static function mapRow(MtGi11Solicitud $solicitud): array
    {
        return [
            'ID' => (string) $solicitud->id,
            'Fecha solicitud' => DisplayDate::date($solicitud->fecha_solicitud, ''),
            'Usuario que solicitó' => (string) ($solicitud->creator?->name ?? ''),
            'Proceso' => (string) ($solicitud->proceso?->name ?? ''),
            'Consecutivo' => (string) $solicitud->consecutivo,
            'Tipo de documento' => (string) ($solicitud->tipoDocumento?->name ?? ''),
            'Nombre del documento' => (string) $solicitud->nombre_documento,
            'Tipo de solicitud' => (string) ($solicitud->tipoSolicitud?->name ?? ''),
            'Código actual' => (string) ($solicitud->codigo_actual ?? ''),
            'Versión actual' => (string) ($solicitud->version_actual ?? ''),
            'Justificación' => (string) $solicitud->justificacion,
            'Responsable del proceso' => (string) $solicitud->responsable_proceso,
            'Cargo del responsable' => (string) ($solicitud->cargoResponsable?->name ?? ''),
            'Código de estandarización' => (string) ($solicitud->codigo_estandarizacion ?? ''),
            'Fecha estandarización' => DisplayDate::date($solicitud->fecha_estandarizacion, ''),
            'Versión actualizada' => $solicitud->version_actualizada !== null ? (string) $solicitud->version_actualizada : '',
            'Estado' => (string) ($solicitud->estado?->name ?? ''),
        ];
    }
}
