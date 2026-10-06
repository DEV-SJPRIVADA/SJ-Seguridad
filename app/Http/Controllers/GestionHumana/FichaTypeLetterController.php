<?php

namespace App\Http\Controllers\GestionHumana;

use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\GenerateFichaTypeLettersRequest;
use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\PayrollCatalogItem;
use App\Models\TerminationLetterDocumentTemplate;
use App\Models\WordDocumentType;
use App\Services\Access\FichaEmpleadosAccessService;
use App\Services\GestionHumana\EmployeeFichaAuditLogService;
use App\Services\GestionHumana\Letter\FichaTypeLetterPackGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FichaTypeLetterController extends Controller
{
    public function __construct(
        private readonly FichaEmpleadosAccessService $fichaEmpleadosAccess,
        private readonly FichaTypeLetterPackGeneratorService $packGenerator,
        private readonly EmployeeFichaAuditLogService $auditLogService,
    ) {}

    public function templates(EmployeeFichaEmploymentPeriod $period, string $typeCode): JsonResponse
    {
        $type = $this->resolveType($typeCode);
        $this->authorizeForPeriod($period);
        $this->packGenerator->assertCanGenerate($period, $type);

        $templates = TerminationLetterDocumentTemplate::query()
            ->forTypeCode((string) $type->code)
            ->withFile()
            ->ordered()
            ->get()
            ->filter(static function (TerminationLetterDocumentTemplate $template): bool {
                return Storage::disk('local')->exists((string) $template->template_path);
            })
            ->values()
            ->map(static fn (TerminationLetterDocumentTemplate $template): array => [
                'id' => $template->id,
                'label' => $template->label,
                'sort_order' => $template->sort_order,
            ]);

        return response()->json([
            'templates' => $templates,
        ]);
    }

    public function firmas(EmployeeFichaEmploymentPeriod $period, string $typeCode): JsonResponse
    {
        $type = $this->resolveType($typeCode);
        $this->authorizeForPeriod($period);
        $this->packGenerator->assertCanGenerate($period, $type);

        $firmas = PayrollCatalogItem::query()
            ->ofType('firmas')
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(static fn (PayrollCatalogItem $item): array => [
                'id' => $item->id,
                'code' => (string) $item->code,
                'name' => (string) $item->name,
            ]);

        return response()->json([
            'firmas' => $firmas,
        ]);
    }

    public function generate(
        GenerateFichaTypeLettersRequest $request,
        EmployeeFichaEmploymentPeriod $period,
        string $typeCode,
    ): BinaryFileResponse {
        $type = $this->resolveType($typeCode);
        $this->authorizeForPeriod($period);

        $period->load('fichaEntry.profile', 'fichaEntry.requisition');
        $entry = $period->fichaEntry;
        abort_unless($entry !== null, 404);

        $result = $this->packGenerator->generate(
            $period,
            $entry,
            $type,
            $request->templateIds(),
            $request->signatoryId(),
        );

        $this->auditLogService->logEvent(
            eventType: 'ficha_type_letter_pack',
            action: 'generate',
            metadata: [
                'period_id' => $period->id,
                'type_code' => $result['type_code'],
                'type_name' => $type->name,
                'template_ids' => $result['template_ids'],
                'output_type' => $result['output_type'],
                'document_count' => $result['document_count'],
                'download_name' => $result['download_name'],
                'document_number' => $entry->hired_document,
            ],
            model: $period,
            userId: (int) auth()->id(),
        );

        $absolutePath = Storage::disk('local')->path($result['storage_path']);

        return response()->download($absolutePath, $result['download_name']);
    }

    private function resolveType(string $typeCode): WordDocumentType
    {
        $type = WordDocumentType::query()->forCode($typeCode)->first();

        if ($type === null) {
            abort(404);
        }

        try {
            $this->packGenerator->assertNotReservedType($type);
        } catch (ValidationException) {
            abort(404);
        }

        return $type;
    }

    private function authorizeForPeriod(EmployeeFichaEmploymentPeriod $period): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        if ($period->status === EmployeeFichaEmploymentPeriod::STATUS_ACTIVO) {
            abort_unless($this->fichaEmpleadosAccess->canManage($user), 403);

            return;
        }

        if ($period->status === EmployeeFichaEmploymentPeriod::STATUS_CERRADO) {
            abort_unless($this->fichaEmpleadosAccess->canTerminate($user), 403);

            return;
        }

        abort(403);
    }
}
