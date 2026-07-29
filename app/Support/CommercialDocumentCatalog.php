<?php

namespace App\Support;

final class CommercialDocumentCatalog
{
    public const DOC_OK = 'ok';

    public const DOC_X = 'x';

    public const DOC_PENDING = 'pending';

    public const DOC_NA = 'na';

    public const DOC_INCOMPLETE = 'incomplete';

    public const DEFAULT_ALERT_DAYS = 30;

    /**
     * @return array<string, string>
     */
    public static function documentFields(): array
    {
        return [
            'doc_economic_proposal' => 'P. economica',
            'doc_fo_co_02' => 'FO-CO-02',
            'doc_laft_or_queries' => 'LAFT / Consultas',
            'doc_rut' => 'RUT',
            'doc_financials' => 'EE.FF',
            'doc_legal_rep_id' => 'CC RL',
            'doc_chamber' => 'Camara comercio',
            'doc_preinstall' => 'Preinst',
            'doc_contract' => 'Contrato',
            'doc_annex_2' => 'Anexo 2',
        ];
    }

    /**
     * @return list<string>
     */
    public static function documentKeys(): array
    {
        return array_keys(self::documentFields());
    }

    /**
     * @return array<string, string>
     */
    public static function documentStatuses(): array
    {
        return [
            self::DOC_OK => 'OK',
            self::DOC_X => 'X',
            self::DOC_PENDING => 'Pendiente',
            self::DOC_NA => 'N/A',
            self::DOC_INCOMPLETE => 'Incompleto',
        ];
    }

    /**
     * @return list<string>
     */
    public static function documentStatusValues(): array
    {
        return array_keys(self::documentStatuses());
    }

    public static function statusLabel(?string $status): string
    {
        if ($status === null || $status === '') {
            return '—';
        }

        return self::documentStatuses()[$status] ?? $status;
    }
}
