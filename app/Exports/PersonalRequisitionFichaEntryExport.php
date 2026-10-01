<?php

namespace App\Exports;

use App\Models\PersonalRequisitionFichaEntry;
use App\Support\DisplayDate;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonalRequisitionFichaEntryExport
{
    /**
     * @return list<string>
     */
    public static function relationNames(): array
    {
        return [
            'requisition.position',
            'requisition.client',
            'requisition.city',
            'movedBy',
            'profile',
        ];
    }

    /**
     * @return list<array{key: string|\Closure, label: string}>
     */
    public static function columns(): array
    {
        return [
            ['key' => fn ($e) => $e->requisitionCode() ?? '—', 'label' => 'Codigo requisicion'],
            ['key' => 'hired_document', 'label' => 'Cedula'],
            ['key' => 'hired_full_name', 'label' => 'Nombre completo'],
            ['key' => fn ($e) => $e->positionName() ?? '—', 'label' => 'Cargo'],
            ['key' => fn ($e) => $e->clientName() ?? '—', 'label' => 'Cliente'],
            ['key' => fn ($e) => $e->cityName() ?? '—', 'label' => 'Ciudad'],
            ['key' => fn ($e) => DisplayDate::date($e->hireDate()), 'label' => 'Fecha ingreso'],
            ['key' => fn ($e) => DisplayDate::date($e->terminationDate()), 'label' => 'Fecha retiro'],
            ['key' => fn ($e) => $e->moved_to_ficha_at === null ? 'Pendiente' : 'En ficha', 'label' => 'Estado'],
            ['key' => fn ($e) => DisplayDate::dateTime($e->moved_to_ficha_at), 'label' => 'Agregado a ficha'],
            ['key' => fn ($e) => $e->movedBy?->name ?? '—', 'label' => 'Agregado por'],
        ];
    }

    /**
     * Columnas del listado Pendientes (sin acciones UI).
     *
     * @return list<array{key: string|\Closure, label: string}>
     */
    public static function pendientesColumns(): array
    {
        return [
            ['key' => fn (PersonalRequisitionFichaEntry $e) => $e->requisitionCode() ?? '—', 'label' => 'Codigo requisicion'],
            ['key' => 'hired_document', 'label' => 'Cedula'],
            ['key' => 'hired_full_name', 'label' => 'Nombre completo'],
            ['key' => fn (PersonalRequisitionFichaEntry $e) => $e->positionName() ?? '—', 'label' => 'Cargo'],
            ['key' => fn (PersonalRequisitionFichaEntry $e) => $e->clientName() ?? '—', 'label' => 'Cliente'],
            ['key' => fn (PersonalRequisitionFichaEntry $e) => $e->cityName() ?? '—', 'label' => 'Ciudad'],
            ['key' => fn (PersonalRequisitionFichaEntry $e) => DisplayDate::date($e->hireDate()), 'label' => 'Fecha ingreso'],
            ['key' => fn (PersonalRequisitionFichaEntry $e) => DisplayDate::date($e->terminationDate()), 'label' => 'Fecha retiro'],
            [
                'key' => fn (PersonalRequisitionFichaEntry $e) => $e->isRehirePending() ? 'Reingreso' : 'Nuevo',
                'label' => 'Tipo',
            ],
            ['key' => fn () => 'Pendiente', 'label' => 'Estado'],
        ];
    }

    /**
     * @param  Collection<int, PersonalRequisitionFichaEntry>  $entries
     */
    public static function download(Collection $entries, string $fileName, string $title): StreamedResponse
    {
        return (new BaseExport($entries, self::columns(), $fileName, $title))->download();
    }

    /**
     * @param  Collection<int, PersonalRequisitionFichaEntry>  $entries
     */
    public static function downloadPendientes(Collection $entries, string $fileName, string $title = 'Ficha empleados — Pendientes'): StreamedResponse
    {
        return (new BaseExport($entries, self::pendientesColumns(), $fileName, $title))->download();
    }
}
