<?php

namespace App\Http\Controllers\GestionHumana;

use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\GenerateContratacionLettersRequest;
use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\PayrollCatalogItem;
use App\Models\TerminationLetterDocumentTemplate;
use App\Services\Access\FichaEmpleadosAccessService;
use App\Services\GestionHumana\ContratacionLetter\ContratacionLetterPackGeneratorService;
use App\Services\GestionHumana\EmployeeFichaAuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContratacionLetterController extends Controller
{
    public function __construct(
        private readonly FichaEmpleadosAccessService $fichaEmpleadosAccess,
        private readonly ContratacionLetterPackGeneratorService $packGenerator,
        private readonly EmployeeFichaAuditLogService $auditLogService,
    ) {}

    public function templates(EmployeeFichaEmploymentPeriod $period): JsonResponse
    {
        $this->authorizeManage();
        $this->packGenerator->assertCanGenerate($period);

        $typeCode = (string) config('employee_ficha.word_document_type_codes.contratacion');

        $templates = TerminationLetterDocumentTemplate::query()
            ->forTypeCode($typeCode)
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

    public function firmas(EmployeeFichaEmploymentPeriod $period): JsonResponse
    {
        $this->authorizeManage();
        $this->packGenerator->assertCanGenerate($period);

        $firmas = PayrollCatalogItem::query()
            ->ofType('firmas')
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return response()->json([
            'firmas' => $firmas,
        ]);
    }

    public function generate(
        GenerateContratacionLettersRequest $request,
        EmployeeFichaEmploymentPeriod $period,
    ): BinaryFileResponse {
        $period->load('fichaEntry.profile.requisition');
        $entry = $period->fichaEntry;
        abort_unless($entry !== null, 404);

        $result = $this->packGenerator->generate($period, $entry, $request->templateIds(), $request->signatoryId());

        $this->auditLogService->logEvent(
            eventType: 'contratacion_letter_pack',
            action: 'generate',
            metadata: [
                'period_id' => $period->id,
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

    private function authorizeManage(): void
    {
        abort_unless($this->fichaEmpleadosAccess->canManage(auth()->user()), 403);
    }
}
