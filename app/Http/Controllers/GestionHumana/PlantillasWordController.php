<?php

namespace App\Http\Controllers\GestionHumana;

use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\PlantillasWord\ReplaceWordDocumentTemplateRequest;
use App\Http\Requests\GestionHumana\PlantillasWord\StoreWordDocumentTemplateRequest;
use App\Http\Requests\GestionHumana\PlantillasWord\StoreWordDocumentTypeRequest;
use App\Http\Requests\GestionHumana\PlantillasWord\UpdateWordDocumentTypeRequest;
use App\Models\TerminationLetterDocumentTemplate;
use App\Models\WordDocumentType;
use App\Services\GestionHumana\EmployeeFichaAuditLogService;
use App\Services\GestionHumana\PlantillasWordAccessService;
use App\Services\GestionHumana\TerminationLetter\TerminationLetterTemplateManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlantillasWordController extends Controller
{
    public const TAB_TIPOS = 'tipos';

    public const TAB_PLANTILLAS = 'plantillas';

    public function __construct(
        private readonly PlantillasWordAccessService $plantillasWordAccess,
        private readonly TerminationLetterTemplateManager $templateManager,
        private readonly EmployeeFichaAuditLogService $auditLogService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($this->plantillasWordAccess->canView(auth()->user()), 403);

        $activeTab = $this->resolveTab($request->query('tab'));

        $types = WordDocumentType::query()
            ->withCount('templates')
            ->ordered()
            ->get();

        $templates = TerminationLetterDocumentTemplate::query()
            ->with('type')
            ->ordered()
            ->get();

        $activeTypes = $types->where('is_active', true)->values();

        return view('areas.gestion_humana.plantillas-word.index', [
            'canManage' => $this->plantillasWordAccess->canManage(auth()->user()),
            'types' => $types,
            'activeTypes' => $activeTypes,
            'templates' => $templates,
            'placeholders' => config('employee_ficha.letter_placeholders', []),
            'activeTab' => $activeTab,
            'subTabs' => $this->subTabs($activeTab),
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

        return $this->redirectToTab(self::TAB_TIPOS)
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

        return $this->redirectToTab(self::TAB_TIPOS)
            ->with('status', 'Tipo de documento actualizado.');
    }

    public function destroyType(WordDocumentType $type): RedirectResponse
    {
        abort_unless($this->plantillasWordAccess->canManage(auth()->user()), 403);

        if ($type->templates()->exists()) {
            return $this->redirectToTab(self::TAB_TIPOS)
                ->with('error', 'No se puede eliminar un tipo que tiene plantillas asociadas. Desactivelo o reasigne las plantillas.');
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

        return $this->redirectToTab(self::TAB_TIPOS)
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

        return $this->redirectToTab(self::TAB_PLANTILLAS)
            ->with('status', 'Plantilla Word agregada correctamente.');
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

        return $this->redirectToTab(self::TAB_PLANTILLAS)
            ->with('status', 'Archivo de plantilla reemplazado.');
    }

    public function destroyTemplate(TerminationLetterDocumentTemplate $template): RedirectResponse
    {
        abort_unless($this->plantillasWordAccess->canManage(auth()->user()), 403);

        $metadata = [
            'template_id' => $template->id,
            'type_id' => $template->word_document_type_id,
            'label' => $template->label,
        ];

        $this->templateManager->destroyTemplate($template);

        $this->auditLogService->logEvent(
            eventType: 'termination_letter_template',
            action: 'delete',
            metadata: $metadata,
            userId: (int) auth()->id(),
        );

        return $this->redirectToTab(self::TAB_PLANTILLAS)
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

    private function resolveTab(mixed $tab): string
    {
        $value = is_string($tab) ? $tab : self::TAB_TIPOS;

        return in_array($value, [self::TAB_TIPOS, self::TAB_PLANTILLAS], true)
            ? $value
            : self::TAB_TIPOS;
    }

    /**
     * @return list<array{key: string, label: string, url: string, active: bool}>
     */
    private function subTabs(string $activeTab): array
    {
        return [
            [
                'key' => self::TAB_TIPOS,
                'label' => 'Tipos de documento',
                'url' => route('gestion-humana.plantillas-word.index', ['tab' => self::TAB_TIPOS]),
                'active' => $activeTab === self::TAB_TIPOS,
            ],
            [
                'key' => self::TAB_PLANTILLAS,
                'label' => 'Plantillas',
                'url' => route('gestion-humana.plantillas-word.index', ['tab' => self::TAB_PLANTILLAS]),
                'active' => $activeTab === self::TAB_PLANTILLAS,
            ],
        ];
    }

    private function redirectToTab(string $tab): RedirectResponse
    {
        return redirect()->route('gestion-humana.plantillas-word.index', [
            'tab' => $this->resolveTab($tab),
        ]);
    }
}
