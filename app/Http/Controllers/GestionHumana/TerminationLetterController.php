<?php

namespace App\Http\Controllers\GestionHumana;

use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\GenerateTerminationLettersRequest;
use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\PayrollCatalogItem;
use App\Models\TerminationLetterDocumentTemplate;
use App\Services\Access\FichaEmpleadosAccessService;
use App\Services\GestionHumana\EmployeeFichaAuditLogService;
use App\Services\GestionHumana\TerminationLetter\TerminationLetterPackGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TerminationLetterController extends Controller
{
    public function __construct(
        private readonly FichaEmpleadosAccessService $fichaEmpleadosAccess,
        private readonly TerminationLetterPackGeneratorService $packGenerator,
        private readonly EmployeeFichaAuditLogService $auditLogService,
    ) {}

    public function templates(EmployeeFichaEmploymentPeriod $period): JsonResponse
    {
        $this->authorizeTerminate();
        $this->packGenerator->assertCanGenerate($period);

        $typeCode = (string) config('employee_ficha.word_document_type_codes.desvinculacion');

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
        $this->authorizeTerminate();
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
        GenerateTerminationLettersRequest $request,
        EmployeeFichaEmploymentPeriod $period,
    ): BinaryFileResponse {
        $period->load('fichaEntry.profile');
        $entry = $period->fichaEntry;
        abort_unless($entry !== null, 404);

        $result = $this->packGenerator->generate($period, $entry, $request->templateIds(), $request->signatoryId());

        $this->auditLogService->logEvent(
            eventType: 'termination_letter_pack',
            action: 'generate',
            metadata: [
                'period_id' => $period->id,
                'cause_code' => $period->termination_cause_code,
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

    public function download(EmployeeFichaEmploymentPeriod $period): StreamedResponse
    {
        $this->authorizeTerminate();

        abort_unless(filled($period->termination_letter_path), 404);
        abort_unless(Storage::disk('local')->exists($period->termination_letter_path), 404);

        $downloadName = basename((string) $period->termination_letter_path);

        $this->auditLogService->logEvent(
            eventType: 'termination_letter_pack',
            action: 'download',
            metadata: [
                'period_id' => $period->id,
                'cause_code' => $period->termination_cause_code,
                'output_type' => $period->termination_letter_type,
                'download_name' => $downloadName,
            ],
            model: $period,
            userId: (int) auth()->id(),
        );

        return Storage::disk('local')->download(
            $period->termination_letter_path,
            $downloadName,
        );
    }

    private function authorizeTerminate(): void
    {
        abort_unless($this->fichaEmpleadosAccess->canTerminate(auth()->user()), 403);
    }
}
