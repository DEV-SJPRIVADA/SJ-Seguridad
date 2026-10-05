<?php

namespace App\Http\Controllers\GestionHumana;

use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\GenerateContratacionLettersRequest;
use App\Http\Requests\GestionHumana\GenerateQuickContratacionLetterRequest;
use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\TerminationLetterDocumentTemplate;
use App\Services\Access\FichaEmpleadosAccessService;
use App\Services\GestionHumana\ContratacionLetter\ContratacionLetterPackGeneratorService;
use App\Services\GestionHumana\ContratacionLetter\ContratacionQuickLetterService;
use App\Services\GestionHumana\EmployeeFichaAuditLogService;
use App\Services\GestionHumana\EmployeeFichaCatalogService;
use App\Services\GestionHumana\EmployeeFichaProfilePrefill;
use App\Traits\HasFichaEmpleadosTabs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ContratacionLetterController extends Controller
{
    use HasFichaEmpleadosTabs;

    public function __construct(
        private readonly FichaEmpleadosAccessService $fichaEmpleadosAccess,
        private readonly ContratacionLetterPackGeneratorService $packGenerator,
        private readonly ContratacionQuickLetterService $quickLetterService,
        private readonly EmployeeFichaAuditLogService $auditLogService,
        private readonly EmployeeFichaCatalogService $catalogService,
        private readonly EmployeeFichaProfilePrefill $profilePrefill,
    ) {}

    public function templates(EmployeeFichaEmploymentPeriod $period): JsonResponse
    {
        $this->authorizeManage();
        $this->packGenerator->assertCanGenerate($period);

        return response()->json([
            'templates' => $this->contratacionTemplatesPayload(),
        ]);
    }

    public function firmas(EmployeeFichaEmploymentPeriod $period): JsonResponse
    {
        $this->authorizeManage();
        $this->packGenerator->assertCanGenerate($period);

        return response()->json([
            'firmas' => $this->firmasPayload(),
        ]);
    }

    public function generate(
        GenerateContratacionLettersRequest $request,
        EmployeeFichaEmploymentPeriod $period,
    ): BinaryFileResponse|RedirectResponse {
        $period->load('fichaEntry.profile', 'fichaEntry.requisition');
        $entry = $period->fichaEntry;
        abort_unless($entry !== null, 404);

        try {
            $result = $this->packGenerator->generate($period, $entry, $request->templateIds(), $request->signatoryId());
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->letterGenerationFailed(
                $e,
                'contratacion_letter_pack',
                [
                    'period_id' => $period->id,
                    'ficha_entry_id' => $entry->id,
                    'template_ids' => $request->templateIds(),
                    'signatory_id' => $request->signatoryId(),
                ],
            );
        }

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

    /**
     * Formulario corto desde Pendientes (antes de En ficha).
     */
    public function quickForm(PersonalRequisitionFichaEntry $fichaEntry): View
    {
        $this->authorizeManage();

        $fichaEntry->load(['requisition.city', 'requisition.position', 'requisition.client', 'profile']);

        abort_unless($fichaEntry->moved_to_ficha_at === null, 404);

        $profile = $this->quickLetterService->profileForForm($fichaEntry);
        $templates = $this->contratacionTemplatesPayload();
        $firmas = $this->firmasPayload();

        return view('areas.gestion_humana.ficha-empleados.employees.carta-contratacion', [
            'fichaEntry' => $fichaEntry,
            'profile' => $profile,
            'requisitionReference' => $this->profilePrefill->requisitionReferenceForEntry($fichaEntry),
            'ciudadRequisicion' => (string) ($fichaEntry->requisition?->city?->name ?? ''),
            'catalogs' => $this->catalogService->optionsForForms(),
            'templates' => $templates,
            'firmas' => $firmas,
            'firmaOptions' => collect($firmas)
                ->map(static fn (array $firma): array => [
                    'value' => (string) $firma['id'],
                    'label' => $firma['name'].' — '.$firma['code'],
                ])
                ->all(),
            'subTabs' => $this->getFichaEmpleadosSubTabs('empleados'),
        ]);
    }

    public function generateQuick(
        GenerateQuickContratacionLetterRequest $request,
        PersonalRequisitionFichaEntry $fichaEntry,
    ): BinaryFileResponse|RedirectResponse {
        try {
            $result = $this->quickLetterService->saveAndGenerate(
                $fichaEntry,
                $request->payload(),
                (int) $request->user()->id,
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->letterGenerationFailed(
                $e,
                'contratacion_letter_quick',
                [
                    'ficha_entry_id' => $fichaEntry->id,
                    'document_number' => $fichaEntry->hired_document,
                    'is_rehire_pending' => $fichaEntry->fresh('profile')?->isRehirePending(),
                    'template_ids' => $request->input('template_ids'),
                    'signatory_id' => $request->input('signatory_id'),
                ],
            );
        }

        $this->auditLogService->logEvent(
            eventType: 'contratacion_letter_pack',
            action: 'generate_quick',
            metadata: [
                'period_id' => $result['period']->id,
                'ficha_entry_id' => $result['entry']->id,
                'template_ids' => $result['template_ids'],
                'output_type' => $result['output_type'],
                'document_count' => $result['document_count'],
                'download_name' => $result['download_name'],
                'document_number' => $result['entry']->hired_document,
                'still_pending' => $result['entry']->moved_to_ficha_at === null,
            ],
            model: $result['period'],
            userId: (int) $request->user()->id,
        );

        $absolutePath = Storage::disk('local')->path($result['storage_path']);

        return response()->download($absolutePath, $result['download_name']);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function letterGenerationFailed(Throwable $e, string $channel, array $context): RedirectResponse
    {
        report($e);

        Log::error('Fallo al generar carta Word ('.$channel.')', array_merge($context, [
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile().':'.$e->getLine(),
        ]));

        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['carta' => $this->userFacingLetterError($e)]);
    }

    private function userFacingLetterError(Throwable $e): string
    {
        $raw = trim($e->getMessage());

        if ($raw !== '' && (
            str_contains($raw, 'emp_ficha_profiles_doc_uq')
            || (str_contains($raw, 'Duplicate entry') && str_contains($raw, 'emp_ficha_profiles'))
        )) {
            return 'Esta cédula ya tiene un perfil de ficha en otro registro. Si es un reingreso, la entrada pendiente debe reutilizar ese perfil; revise que el empleado no siga activo en En ficha.';
        }

        if ($raw !== '' && str_contains($raw, 'Duplicate entry')) {
            return 'Conflicto de datos al guardar la ficha (registro duplicado). Detalle: '.Str::limit($raw, 240);
        }

        if ($raw !== '' && (
            str_contains($raw, 'Failed to open stream')
            || str_contains($raw, 'Permission denied')
            || str_contains($raw, 'mkdir():')
        )) {
            return 'No se pudo escribir archivos temporales de Word. Se intentará storage/framework/cache; en el servidor Linux revise dueño/permisos de storage (www-data). Detalle: '.Str::limit($raw, 200);
        }

        if ($e instanceof RuntimeException && $raw !== '') {
            return $raw;
        }

        if ($raw !== '') {
            return 'No se pudo generar la carta: '.Str::limit($raw, 300);
        }

        return 'No se pudo generar la carta de contratación. Verifique plantillas, firmante y almacenamiento del servidor.';
    }

    /**
     * @return list<array{id: int, label: string, sort_order: int|null}>
     */
    private function contratacionTemplatesPayload(): array
    {
        $typeCode = (string) config('employee_ficha.word_document_type_codes.contratacion');

        return TerminationLetterDocumentTemplate::query()
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
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, code: string, name: string}>
     */
    private function firmasPayload(): array
    {
        return PayrollCatalogItem::query()
            ->ofType('firmas')
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(static fn (PayrollCatalogItem $item): array => [
                'id' => $item->id,
                'code' => (string) $item->code,
                'name' => (string) $item->name,
            ])
            ->all();
    }

    private function authorizeManage(): void
    {
        abort_unless($this->fichaEmpleadosAccess->canManage(auth()->user()), 403);
    }
}
