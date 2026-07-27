<?php

namespace App\Exports;

use App\Models\PersonalRequisition;
use Illuminate\Support\Collection;

class PersonalRequisitionFullExport
{
    /**
     * @return list<string>
     */
    public static function relationNames(): array
    {
        return [
            'requester',
            'manager',
            'position',
            'requestReason',
            'client',
            'city',
            'clientType',
            'programmingType',
            'uniform',
            'contractType',
            'recruiter',
        ];
    }

    /**
     * @return list<array{key: string|\Closure, label: string}>
     */
    public static function columns(): array
    {
        $statusLabels = PersonalRequisition::statuses();
        $sexLabels = [
            'masculino' => 'Masculino',
            'femenino' => 'Femenino',
            'indiferente' => 'Indiferente',
        ];
        $areaLabel = fn (?string $key): string => $key
            ? (string) (config('access.areas.'.$key) ?? $key)
            : '';

        return [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'code', 'label' => 'Codigo'],
            ['key' => fn ($r) => $r->request_date?->format('Y-m-d'), 'label' => 'Fecha solicitud'],
            ['key' => 'leader_name', 'label' => 'Lider solicitante'],
            ['key' => fn ($r) => $r->requester?->name ?? '—', 'label' => 'Usuario solicitante'],
            ['key' => fn ($r) => $r->requester?->email ?? '—', 'label' => 'Correo solicitante'],
            ['key' => fn ($r) => $areaLabel($r->requesting_area_key), 'label' => 'Area solicitante'],
            ['key' => fn ($r) => $r->requestReason?->name ?? '—', 'label' => 'Motivo'],
            ['key' => fn ($r) => $r->position?->name ?? '—', 'label' => 'Cargo'],
            ['key' => fn ($r) => $sexLabels[$r->sex] ?? $r->sex ?? '—', 'label' => 'Sexo'],
            ['key' => 'quantity', 'label' => 'Cantidad'],
            ['key' => 'replacement_document', 'label' => 'Cedula reemplazo/movimiento'],
            ['key' => 'replacement_name', 'label' => 'Nombre reemplazo/movimiento'],
            ['key' => fn ($r) => $areaLabel($r->operating_area_key), 'label' => 'Area operativa'],
            ['key' => fn ($r) => $r->client?->name ?? '—', 'label' => 'Cliente'],
            ['key' => fn ($r) => $r->city?->name ?? '—', 'label' => 'Ciudad'],
            ['key' => fn ($r) => $r->clientType?->name ?? '—', 'label' => 'Tipo de cliente'],
            ['key' => fn ($r) => $r->programmingType?->name ?? '—', 'label' => 'Tipo de programacion'],
            ['key' => 'required_profile', 'label' => 'Perfil requerido'],
            ['key' => fn ($r) => $r->uniform?->name ?? '—', 'label' => 'Dotacion'],
            ['key' => 'service_structure', 'label' => 'Estructura del servicio'],
            ['key' => 'cost_center', 'label' => 'Centro de costo'],
            ['key' => 'requester_observation', 'label' => 'Observaciones solicitante'],
            ['key' => fn ($r) => $r->contractType?->name ?? '—', 'label' => 'Tipo de contrato'],
            ['key' => 'contract_duration', 'label' => 'Duracion contrato'],
            ['key' => 'base_salary', 'label' => 'Salario base'],
            ['key' => 'transport_allowance', 'label' => 'Auxilio transporte'],
            ['key' => 'mobility_allowance', 'label' => 'Auxilio movilidad'],
            ['key' => 'statutory_bonus', 'label' => 'Prima legal'],
            ['key' => 'non_statutory_bonus', 'label' => 'Prima extralegal'],
            ['key' => 'other_allowances', 'label' => 'Otros auxilios'],
            ['key' => 'leasing_contract', 'label' => 'Contrato leasing'],
            ['key' => fn ($r) => $r->recruiter?->name ?? $r->recruiter_name ?? '—', 'label' => 'Encargado seleccion'],
            ['key' => 'recruiter_name', 'label' => 'Nombre reclutador (texto)'],
            ['key' => fn ($r) => $r->hiring_date?->format('Y-m-d'), 'label' => 'Fecha contratacion'],
            ['key' => 'human_resources_observation', 'label' => 'Observaciones GH'],
            ['key' => fn ($r) => $statusLabels[$r->status] ?? $r->status, 'label' => 'Estado'],
            ['key' => fn ($r) => $r->manager?->name ?? '—', 'label' => 'Gestionado por'],
            ['key' => fn ($r) => $r->status_changed_at?->format('Y-m-d H:i'), 'label' => 'Cambio estado'],
            ['key' => fn ($r) => $r->closed_at?->format('Y-m-d H:i'), 'label' => 'Cierre'],
            ['key' => fn ($r) => $r->created_at?->format('Y-m-d H:i'), 'label' => 'Creado'],
            ['key' => fn ($r) => $r->updated_at?->format('Y-m-d H:i'), 'label' => 'Actualizado'],
        ];
    }

    /**
     * @param  Collection<int, PersonalRequisition>  $requisitions
     */
    public static function download(Collection $requisitions, string $fileName, string $title): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return (new BaseExport($requisitions, self::columns(), $fileName, $title))->download();
    }
}
