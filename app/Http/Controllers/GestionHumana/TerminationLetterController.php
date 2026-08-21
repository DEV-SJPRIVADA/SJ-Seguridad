<?php

namespace App\Http\Controllers\GestionHumana;

use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\UploadTerminationLetterTemplateRequest;
use App\Models\EmployeeFichaEmploymentPeriod;
use App\Services\Access\FichaEmpleadosAccessService;
use App\Services\GestionHumana\EmployeeFichaAuditLogService;
use App\Services\GestionHumana\TerminationLetter\TerminationLetterPackGeneratorService;
use App\Services\GestionHumana\TerminationLetter\TerminationLetterTemplateManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TerminationLetterController extends Controller
{
    public function __construct(
        private readonly FichaEmpleadosAccessService $fichaEmpleadosAccess,
        private readonly TerminationLetterPackGeneratorService $packGenerator,
        private readonly TerminationLetterTemplateManager $templateManager,
        private readonly EmployeeFichaAuditLogService $auditLogService,
    ) {}

    public function generate(EmployeeFichaEmploymentPeriod $period): BinaryFileResponse|RedirectResponse
    {
        $this->authorizeTerminate();

        $period->load('fichaEntry.profile');
        $entry = $period->fichaEntry;
        abort_unless($entry !== null, 404);

        $result = $this->packGenerator->generate($period, $entry);

        $this->auditLogService->logEvent(
            eventType: 'termination_letter_pack',
            action: 'generate',
            metadata: [
                'period_id' => $period->id,
                'cause_code' => $period->termination_cause_code,
                'document_count' => $result['document_count'],
                'zip_filename' => $result['download_name'],
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

        $this->auditLogService->logEvent(
            eventType: 'termination_letter_pack',
            action: 'download',
            metadata: [
                'period_id' => $period->id,
                'cause_code' => $period->termination_cause_code,
                'zip_filename' => basename((string) $period->termination_letter_path),
            ],
            model: $period,
            userId: (int) auth()->id(),
        );

        return Storage::disk('local')->download(
            $period->termination_letter_path,
            basename((string) $period->termination_letter_path),
        );
    }

    public function uploadTemplate(
        UploadTerminationLetterTemplateRequest $request,
        string $causeCode,
        string $documentKey,
    ): RedirectResponse {
        $this->authorizeManage();

        $template = $this->templateManager->findTemplate($causeCode, $documentKey);
        abort_unless($template !== null, 404);

        $this->templateManager->storeUploadedTemplate($template, $request->file('template'));

        $this->auditLogService->logEvent(
            eventType: 'termination_letter_template',
            action: 'upload',
            metadata: [
                'cause_code' => $causeCode,
                'document_key' => $documentKey,
                'template_id' => $template->id,
            ],
            model: $template,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.ficha-empleados.catalogs.index', ['catalog' => 'termination_cause'])
            ->with('status', 'Plantilla Word actualizada correctamente.');
    }

    public function downloadMasterTemplate(string $causeCode, string $documentKey): StreamedResponse
    {
        $this->authorizeManage();

        $template = $this->templateManager->findTemplate($causeCode, $documentKey);
        abort_unless($template !== null && $template->hasTemplateFile(), 404);

        return Storage::disk('local')->download(
            (string) $template->template_path,
            Str::slug($template->label, '_').'.docx',
        );
    }

    public function deleteTemplate(string $causeCode, string $documentKey): RedirectResponse
    {
        $this->authorizeManage();

        $template = $this->templateManager->findTemplate($causeCode, $documentKey);
        abort_unless($template !== null, 404);

        $this->templateManager->deleteTemplateFile($template);

        $this->auditLogService->logEvent(
            eventType: 'termination_letter_template',
            action: 'delete',
            metadata: [
                'cause_code' => $causeCode,
                'document_key' => $documentKey,
                'template_id' => $template->id,
            ],
            model: $template,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route('gestion-humana.ficha-empleados.catalogs.index', ['catalog' => 'termination_cause'])
            ->with('status', 'Plantilla Word eliminada.');
    }

    private function authorizeTerminate(): void
    {
        abort_unless($this->fichaEmpleadosAccess->canTerminate(auth()->user()), 403);
    }

    private function authorizeManage(): void
    {
        abort_unless($this->fichaEmpleadosAccess->canManage(auth()->user()), 403);
    }
}
