<?php

namespace App\Exports;

use App\Models\ClienteInternoSolicitud;
use App\Services\GestionHumana\ClienteInternoDatatableService;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClienteInternoExport extends BaseExport
{
    /**
     * @param  Collection<int, ClienteInternoSolicitud>  $solicitudes
     */
    public function __construct(Collection $solicitudes)
    {
        $data = $solicitudes
            ->map(fn (ClienteInternoSolicitud $solicitud): array => self::mapSolicitud($solicitud))
            ->values();

        parent::__construct(
            $data,
            self::columnDefinitions(),
            'cliente_interno_solicitudes_'.now()->format('Y-m-d').'.xlsx',
            'Cliente interno — Solicitudes — '.config('app.name'),
        );
    }

    /**
     * @param  Collection<int, ClienteInternoSolicitud>  $solicitudes
     */
    public static function downloadCollection(Collection $solicitudes): StreamedResponse
    {
        return (new self($solicitudes))->download();
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public static function columnDefinitions(): array
    {
        return [
            ['key' => 'fecha_solicitud', 'label' => 'Fecha de solicitud'],
            ['key' => 'anio', 'label' => 'Año'],
            ['key' => 'mes', 'label' => 'Mes'],
            ['key' => 'nombre_apellidos', 'label' => 'Nombre y apellidos'],
            ['key' => 'cedula', 'label' => 'Cédula'],
            ['key' => 'correo_electronico', 'label' => 'Correo electrónico'],
            ['key' => 'solicitud', 'label' => 'Solicitud'],
            ['key' => 'fecha_respuesta', 'label' => 'Fecha de respuesta'],
            ['key' => 'estado', 'label' => 'Estado'],
            ['key' => 'novedad', 'label' => 'Novedad'],
            ['key' => 'dias_respuesta', 'label' => 'Días de respuesta'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function mapSolicitud(ClienteInternoSolicitud $solicitud): array
    {
        $mes = (int) $solicitud->mes;

        return [
            'fecha_solicitud' => optional($solicitud->fecha_solicitud)?->format('Y-m-d'),
            'anio' => $solicitud->anio,
            'mes' => mb_strtoupper((string) (ClienteInternoDatatableService::MESES[$mes] ?? $mes), 'UTF-8'),
            'nombre_apellidos' => $solicitud->nombre_apellidos,
            'cedula' => $solicitud->cedula,
            'correo_electronico' => $solicitud->correo_electronico,
            'solicitud' => $solicitud->tipoSolicitud?->name,
            'fecha_respuesta' => optional($solicitud->fecha_respuesta)?->format('Y-m-d'),
            'estado' => $solicitud->estado?->name,
            'novedad' => $solicitud->novedad,
            'dias_respuesta' => $solicitud->dias_respuesta,
        ];
    }
}
