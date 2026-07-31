<?php

namespace App\Services\Requisitions;

use App\Models\PersonalRequisition;
use App\Models\PersonalRequisitionFichaEntry;

class PersonalRequisitionFichaSync
{
    /**
     * Mantiene sincronizada la entrada 1:1 de `personal_requisition_ficha_entries`
     * con el estado y los datos de contratacion capturados en la requisicion.
     *
     * Reglas de negocio (ver docs/briefs/FEAT-020.md #3-6):
     * - status != contratado: si la entrada propia esta pendiente se elimina; si ya
     *   esta en ficha se conserva intacta.
     * - status == contratado sin duplicado: upsert normal de la entrada propia.
     * - status == contratado con duplicado confirmado: la entrada de la otra
     *   requisicion se reasigna a la actual (sin crear una fila nueva); si la
     *   requisicion actual ya tenia su propia entrada, se descarta para respetar
     *   la relacion 1:1.
     */
    public function syncOnUpdate(
        PersonalRequisition $requisition,
        string $newStatus,
        ?string $document,
        ?string $fullName,
        bool $confirmDuplicate,
        int $userId,
    ): void {
        $ownEntry = PersonalRequisitionFichaEntry::query()
            ->where('personal_requisition_id', $requisition->id)
            ->first();

        if ($newStatus !== PersonalRequisition::STATUS_CONTRATADO) {
            if ($ownEntry !== null && $ownEntry->moved_to_ficha_at === null) {
                $ownEntry->delete();
            }

            return;
        }

        $document = trim((string) $document);
        $fullName = trim((string) $fullName);

        $otherEntry = PersonalRequisitionFichaEntry::query()
            ->where('hired_document', $document)
            ->where('personal_requisition_id', '!=', $requisition->id)
            ->first();

        if ($otherEntry !== null && $confirmDuplicate) {
            if ($ownEntry !== null && $ownEntry->id !== $otherEntry->id) {
                $ownEntry->delete();
            }

            $otherEntry->update([
                'personal_requisition_id' => $requisition->id,
                'hired_full_name' => $fullName,
            ]);

            return;
        }

        if ($ownEntry !== null) {
            $ownEntry->update([
                'hired_document' => $document,
                'hired_full_name' => $fullName,
            ]);

            return;
        }

        PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => $document,
            'hired_full_name' => $fullName,
            'created_by' => $userId,
        ]);
    }
}
