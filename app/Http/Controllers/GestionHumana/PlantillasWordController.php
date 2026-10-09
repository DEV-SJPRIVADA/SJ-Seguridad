<?php

namespace App\Http\Controllers\GestionHumana;

use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\PlantillasWord\ReplaceWordDocumentTemplateRequest;
use App\Http\Requests\GestionHumana\PlantillasWord\StoreWordDocumentTemplateRequest;
use App\Http\Requests\GestionHumana\PlantillasWord\StoreWordDocumentTypeRequest;
use App\Http\Requests\GestionHumana\PlantillasWord\UpdateWordDocumentTemplateRequest;
use App\Http\Requests\GestionHumana\PlantillasWord\UpdateWordDocumentTypeRequest;
use App\Models\TerminationLetterDocumentTemplate;
use App\Models\WordDocumentType;
use App\Services\GestionHumana\EmployeeFichaAuditLogService;
use App\Services\GestionHumana\PlantillasWordAccessService;
use App\Services\GestionHumana\TerminationLetter\TerminationLetterTemplateManager;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlantillasWordController extends Controller
{
    public function __construct(
        private readonly PlantillasWordAccessService $plantillasWordAccess,
        private readonly TerminationLetterTemplateManager $templateManager,
        private readonly EmployeeFichaAuditLogService $auditLogService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($this->plantillasWordAccess->canView(auth()->user()), 403);

        $types = WordDocumentType::query()
            ->withCount('templates')
            ->ordered()
            ->get();

        $selectedTypeId = $request->integer('type');
        $selectedType = $selectedTypeId > 0
            ? $types->firstWhere('id', $selectedTypeId)
            : null;

        $filters = $this->resolveTemplateFilters($request);
        $templates = collect();

        if ($selectedType !== null) {
            $templatesQuery = TerminationLetterDocumentTemplate::query()
                ->with('type')
                ->where('word_document_type_id', $selectedType->id)
                ->ordered();

            $this->applyTemplateFilters($templatesQuery, $filters);
            $templates = $templatesQuery->get();
        }

        return view('areas.gestion_humana.plantillas-word.index', [
            'canManage' => $this->plantillasWordAccess->canManage(auth()->user()),
            'types' => $types,
            'selectedType' => $selectedType,
            'templates' => $templates,
            'placeholders' => config('employee_ficha.letter_placeholders', []),
            'filters' => $filters,
            'typeSelectOptions' => $types
                ->map(static fn (WordDocumentType $type): array => [
                    'value' => (string) $type->id,
                    'label' => $type->name.($type->is_active ? '' : ' (inactivo)'),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function storeType(StoreWordDocumentTypeRequest $request): RedirectResponse
    {
        $type = WordDocumentType::query()->create([
            'code' => $request->string('code')->toString(),
            'name' => $request->string('name')->toString(),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($request->input('sort_order') ?? 0),
        ]);

        $this->auditLogService->logEvent(
            eventType: 'word_document_type',
            action: 'store',
            metadata: [
                'type_id' => $type->id,
                'code' => $type->code,
                'name' => $type->name,
            ],
            model: $type,
            userId: (int) auth()->id(),
        );

        return $this->redirectToBoard()
            ->with('status', 'Tipo de documento creado correctamente.');
    }

    public function updateType(UpdateWordDocumentTypeRequest $request, WordDocumentType $type): RedirectResponse
    {
        $before = $type->only(['code', 'name', 'is_active', 'sort_order']);

        $type->update([
            'code' => $request->string('code')->toString(),
            'name' => $request->string('name')->toString(),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($request->input('sort_order') ?? $type->sort_order ?? 0),
        ]);

        $this->auditLogService->logModelChange(
            eventType: 'word_document_type',
            action: 'update',
            model: $type,
            before: $before,
            after: $type->fresh()?->only(['code', 'name', 'is_active', 'sort_order']),
            metadata: ['type_id' => $type->id],
            userId: (int) auth()->id(),
        );

        return $this->redirectToBoard((int) $type->id)
            ->with('status', 'Tipo de documento actualizado.');
    }

    public function destroyType(WordDocumentType $type): RedirectResponse
    {
        abort_unless($this->plantillasWordAccess->canManage(auth()->user()), 403);

        if ($type->templates()->exists()) {
            return $this->redirectToBoard()
                ->with('error', 'No se puede eliminar un tipo que tiene plantillas asociadas. Desactívelo o reasigne las plantillas.');
        }

        $metadata = [
            'type_id' => $type->id,
            'code' => $type->code,
            'name' => $type->name,
        ];

        $type->delete();

        $this->auditLogService->logEvent(
            eventType: 'word_document_type',
            action: 'delete',
            metadata: $metadata,
            userId: (int) auth()->id(),
        );

        return $this->redirectToBoard()
            ->with('status', 'Tipo de documento eliminado.');
    }

    public function storeTemplate(StoreWordDocumentTemplateRequest $request): RedirectResponse
    {
        $type = WordDocumentType::query()->findOrFail((int) $request->input('word_document_type_id'));

        $template = $this->templateManager->createTemplate(
            $type,
            $request->string('label')->toString(),
            $request->file('template'),
            (int) ($request->input('sort_order') ?? 0),
        );

        $this->auditLogService->logEvent(
            eventType: 'termination_letter_template',
            action: 'store',
            metadata: [
                'template_id' => $template->id,
                'type_id' => $type->id,
                'type_code' => $type->code,
                'label' => $template->label,
            ],
            model: $template,
            userId: (int) auth()->id(),
        );

        return $this->redirectToBoard((int) $type->id)
            ->with('status', 'Plantilla Word agregada correctamente.');
    }

    public function updateTemplate(
        UpdateWordDocumentTemplateRequest $request,
        TerminationLetterDocumentTemplate $template,
    ): RedirectResponse {
        $type = WordDocumentType::query()->findOrFail((int) $request->input('word_document_type_id'));
        $before = $template->only(['label', 'word_document_type_id', 'sort_order']);

        $template = $this->templateManager->updateTemplateMetadata(
            $template,
            $request->string('label')->toString(),
            $type,
            (int) ($request->input('sort_order') ?? 0),
        );

        $this->auditLogService->logModelChange(
            eventType: 'termination_letter_template',
            action: 'update',
            model: $template,
            before: $before,
            after: $template->only(['label', 'word_document_type_id', 'sort_order']),
            metadata: [
                'template_id' => $template->id,
                'type_id' => $template->word_document_type_id,
                'type_code' => $type->code,
            ],
            userId: (int) auth()->id(),
        );

        return $this->redirectToBoard((int) $template->word_document_type_id)
            ->with('status', 'Plantilla Word actualizada.');
    }

    public function replaceTemplate(
        ReplaceWordDocumentTemplateRequest $request,
        TerminationLetterDocumentTemplate $template,
    ): RedirectResponse {
        $this->templateManager->storeUploadedTemplate($template, $request->file('template'));

        $this->auditLogService->logEvent(
            eventType: 'termination_letter_template',
            action: 'replace',
            metadata: [
                'template_id' => $template->id,
                'type_id' => $template->word_document_type_id,
                'label' => $template->label,
            ],
            model: $template,
            userId: (int) auth()->id(),
        );

        return $this->redirectToBoard((int) $template->word_document_type_id)
            ->with('status', 'Archivo de plantilla reemplazado.');
    }

    public function destroyTemplate(TerminationLetterDocumentTemplate $template): RedirectResponse
    {
        abort_unless($this->plantillasWordAccess->canManage(auth()->user()), 403);

        $typeId = (int) $template->word_document_type_id;
        $metadata = [
            'template_id' => $template->id,
            'type_id' => $typeId,
            'label' => $template->label,
        ];

        $this->templateManager->destroyTemplate($template);

        $this->auditLogService->logEvent(
            eventType: 'termination_letter_template',
            action: 'delete',
            metadata: $metadata,
            userId: (int) auth()->id(),
        );

        return $this->redirectToBoard($typeId)
            ->with('status', 'Plantilla Word eliminada.');
    }

    public function downloadTemplate(TerminationLetterDocumentTemplate $template): StreamedResponse
    {
        abort_unless($this->plantillasWordAccess->canView(auth()->user()), 403);
        abort_unless($template->hasTemplateFile(), 404);
        abort_unless(Storage::disk('local')->exists((string) $template->template_path), 404);

        return Storage::disk('local')->download(
            (string) $template->template_path,
            Str::slug($template->label, '_').'.docx',
        );
    }

    /**
     * @return array{q: string, file: string}
     */
    private function resolveTemplateFilters(Request $request): array
    {
        $file = (string) $request->query('file', '');
        if (! in_array($file, ['cargada', 'pendiente'], true)) {
            $file = '';
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'file' => $file,
        ];
    }

    /**
     * @param  Builder<TerminationLetterDocumentTemplate>  $query
     * @param  array{q: string, file: string}  $filters
     */
    private function applyTemplateFilters(Builder $query, array $filters): void
    {
        if ($filters['q'] !== '') {
            $query->where('label', 'like', '%'.$filters['q'].'%');
        }

        if ($filters['file'] === 'cargada') {
            $query->withFile();
        }

        if ($filters['file'] === 'pendiente') {
            $query->where(static function ($pendingQuery): void {
                $pendingQuery
                    ->whereNull('template_path')
                    ->orWhere('template_path', '');
            });
        }
    }

    private function redirectToBoard(?int $typeId = null): RedirectResponse
    {
        $params = [];
        if ($typeId !== null && $typeId > 0) {
            $params['type'] = $typeId;
        }

        return redirect()->route('gestion-humana.plantillas-word.index', $params);
    }
}
