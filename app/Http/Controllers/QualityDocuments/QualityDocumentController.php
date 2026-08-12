<?php

namespace App\Http\Controllers\QualityDocuments;

use App\Exports\BaseExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\QualityDocuments\StoreQualityDocumentRequest;
use App\Http\Requests\QualityDocuments\UpdateQualityDocumentRequest;
use App\Models\QualityDocument;
use App\Models\User;
use App\Services\QualityDocuments\QualityDocumentAuditLogService;
use App\Support\DisplayDate;
use App\Traits\HasQualityDocumentTabs;
use App\Traits\ValidatesModule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QualityDocumentController extends Controller
{
    use HasQualityDocumentTabs, ValidatesModule;

    public function __construct(
        private readonly QualityDocumentAuditLogService $auditLogService,
    ) {}

    public function myDocuments(string $module): View
    {
        $this->abortIfUnknownModule($module);
        $this->authorizePersonalDocumentsAccess($module);

        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $documents = QualityDocument::query()
            ->active()
            ->forUser($user->id)
            ->with('uploader')
            ->latest()
            ->get();

        return view('modules.quality-documents.mine.index', [
            'module' => $module,
            'documents' => $documents,
            'subTabs' => $this->getQualityDocumentSubTabs($module),
        ]);
    }

    public function libraryIndex(string $module): View
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeModuleAccess($module);

        $documents = QualityDocument::query()
            ->active()
            ->forArea($module)
            ->with('uploader')
            ->latest()
            ->get();

        return view('modules.quality-documents.library.index', [
            'module' => $module,
            'documents' => $documents,
            'subTabs' => $this->getQualityDocumentSubTabs($module),
        ]);
    }

    public function adminIndex(string $module): View
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManage($module);

        $documents = QualityDocument::query()
            ->with(['uploader', 'areas'])
            ->latest()
            ->get();

        return view('modules.quality-documents.admin.index', [
            'module' => $module,
            'documents' => $documents,
            'subTabs' => $this->getQualityDocumentSubTabs($module),
        ]);
    }

    public function adminExport(string $module): StreamedResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManage($module);

        $documents = QualityDocument::query()
            ->with(['uploader', 'areas', 'assignedUsers.user'])
            ->latest()
            ->get();

        $columns = [
            ['key' => 'code', 'label' => 'Código'],
            ['key' => 'title', 'label' => 'Nombre'],
            ['key' => fn ($d) => $d->processLabel(), 'label' => 'Proceso'],
            ['key' => fn ($d) => $d->documentTypeLabel(), 'label' => 'Tipo'],
            ['key' => fn ($d) => $d->originLabel(), 'label' => 'Origen'],
            ['key' => fn ($d) => $d->documentStatusLabel(), 'label' => 'Estado doc.'],
            ['key' => fn ($d) => $d->activityStatusLabel(), 'label' => 'Estado act.'],
            ['key' => fn ($d) => $d->storageTypeLabel(), 'label' => 'Almacenamiento'],
            ['key' => 'current_version', 'label' => 'Versión'],
            ['key' => fn ($d) => DisplayDate::date($d->last_updated_at), 'label' => 'Últ. actualización'],
            ['key' => fn ($d) => $d->is_active ? 'Activo' : 'Inactivo', 'label' => 'Activo'],
        ];

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'admin_excel',
            metadata: ['row_count' => $documents->count()],
        );

        return (new BaseExport($documents, $columns, 'documentos_calidad_'.now()->format('Y-m-d').'.xlsx', 'Documentos de Calidad'))->download();
    }

    public function libraryExport(string $module): StreamedResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeModuleAccess($module);

        $documents = QualityDocument::query()
            ->active()
            ->forArea($module)
            ->with('uploader')
            ->latest()
            ->get();

        $columns = [
            ['key' => 'code', 'label' => 'Código'],
            ['key' => 'title', 'label' => 'Título'],
            ['key' => fn ($d) => $d->processLabel(), 'label' => 'Proceso'],
            ['key' => fn ($d) => $d->documentTypeLabel(), 'label' => 'Tipo documento'],
            ['key' => fn ($d) => $d->isFile() ? 'Archivo' : 'Enlace', 'label' => 'Recurso'],
            ['key' => fn ($d) => DisplayDate::date($d->created_at), 'label' => 'Publicado'],
        ];

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'library_excel',
            metadata: [
                'row_count' => $documents->count(),
                'area' => $module,
            ],
        );

        return (new BaseExport($documents, $columns, 'biblioteca_calidad_'.now()->format('Y-m-d').'.xlsx', 'Biblioteca de Documentos'))->download();
    }

    public function mineExport(string $module): StreamedResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizePersonalDocumentsAccess($module);

        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $documents = QualityDocument::query()
            ->active()
            ->forUser($user->id)
            ->with('uploader')
            ->latest()
            ->get();

        $columns = [
            ['key' => 'code', 'label' => 'Código'],
            ['key' => 'title', 'label' => 'Título'],
            ['key' => fn ($d) => $d->processLabel(), 'label' => 'Proceso'],
            ['key' => fn ($d) => $d->documentTypeLabel(), 'label' => 'Tipo documento'],
            ['key' => fn ($d) => $d->isFile() ? 'Archivo' : 'Enlace', 'label' => 'Recurso'],
            ['key' => fn ($d) => DisplayDate::date($d->created_at), 'label' => 'Publicado'],
        ];

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'mine_excel',
            metadata: ['row_count' => $documents->count()],
        );

        return (new BaseExport($documents, $columns, 'mis_documentos_'.now()->format('Y-m-d').'.xlsx', 'Mis Documentos'))->download();
    }

    public function create(string $module): View
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManage($module);

        return view('modules.quality-documents.admin.create', [
            'module' => $module,
            'areas' => config('access.areas', []),
            'catalogs' => $this->documentCatalogs(),
            'users' => $this->activeUsersList(),
            'selectedUsers' => [],
            'subTabs' => $this->getQualityDocumentSubTabs($module),
        ]);
    }

    public function store(StoreQualityDocumentRequest $request, string $module): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManage($module);

        $document = null;

        DB::transaction(function () use ($request, &$document): void {
            $document = QualityDocument::create($this->metadataFromRequest($request) + [
                'type' => $request->string('type')->toString(),
                'external_url' => $request->input('type') === QualityDocument::TYPE_LINK
                    ? $request->string('external_url')->toString()
                    : null,
                'is_active' => $request->boolean('is_active', true),
                'uploaded_by' => $request->user()->id,
            ]);

            if ($request->input('type') === QualityDocument::TYPE_FILE && $request->hasFile('file')) {
                $this->storeUploadedFile($document, $request->file('file'));
            }

            $this->syncAreas($document, $request->input('areas', []));
            $this->syncUsers($document, $request->input('users', []));
        });

        $this->auditLogService->logModelChange(
            eventType: 'document',
            action: 'create',
            model: $document,
            before: null,
            after: [
                'code' => $document->code,
                'title' => $document->title,
                'type' => $document->type,
            ],
            metadata: [
                'areas_count' => $document->areas()->count(),
                'users_count' => $document->assignedUsers()->count(),
            ],
        );

        return redirect()
            ->route('quality-documents.admin.index', ['module' => $module])
            ->with('success', 'Documento publicado correctamente.');
    }

    public function edit(string $module, QualityDocument $qualityDocument): View
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManage($module);

        $qualityDocument->load(['areas', 'assignedUsers']);

        return view('modules.quality-documents.admin.edit', [
            'module' => $module,
            'document' => $qualityDocument,
            'areas' => config('access.areas', []),
            'catalogs' => $this->documentCatalogs(),
            'users' => $this->activeUsersList(),
            'selectedAreas' => $qualityDocument->assignedAreaKeys(),
            'selectedUsers' => $qualityDocument->assignedUserIds(),
            'subTabs' => $this->getQualityDocumentSubTabs($module),
        ]);
    }

    public function update(UpdateQualityDocumentRequest $request, string $module, QualityDocument $qualityDocument): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManage($module);

        $before = $this->documentAuditSnapshot($qualityDocument);

        DB::transaction(function () use ($request, $qualityDocument): void {
            $qualityDocument->update($this->metadataFromRequest($request) + [
                'type' => $request->string('type')->toString(),
                'external_url' => $request->input('type') === QualityDocument::TYPE_LINK
                    ? $request->string('external_url')->toString()
                    : null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            if ($request->input('type') === QualityDocument::TYPE_LINK) {
                $this->deleteStoredFile($qualityDocument);
                $qualityDocument->update([
                    'file_path' => null,
                    'original_name' => null,
                    'mime_type' => null,
                    'file_size' => null,
                ]);
            } elseif ($request->hasFile('file')) {
                $this->deleteStoredFile($qualityDocument);
                $this->storeUploadedFile($qualityDocument, $request->file('file'));
            }

            $this->syncAreas($qualityDocument, $request->input('areas', []));
            $this->syncUsers($qualityDocument, $request->input('users', []));
        });

        $qualityDocument->refresh();
        $after = $this->documentAuditSnapshot($qualityDocument);
        [$oldValues, $newValues] = $this->diffAuditFields($before, $after);

        if ($oldValues !== []) {
            $this->auditLogService->logModelChange(
                eventType: 'document',
                action: 'update',
                model: $qualityDocument,
                before: $oldValues,
                after: $newValues,
            );
        }

        return redirect()
            ->route('quality-documents.admin.index', ['module' => $module])
            ->with('success', 'Documento actualizado correctamente.');
    }

    public function toggleStatus(string $module, QualityDocument $qualityDocument): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManage($module);

        $previousIsActive = (bool) $qualityDocument->is_active;

        $qualityDocument->update([
            'is_active' => ! $qualityDocument->is_active,
        ]);

        $this->auditLogService->logEvent(
            eventType: 'document',
            action: $previousIsActive ? 'deactivate' : 'activate',
            model: $qualityDocument,
            metadata: ['code' => $qualityDocument->code],
        );

        return redirect()
            ->route('quality-documents.admin.index', ['module' => $module])
            ->with('success', 'Estado del documento actualizado.');
    }

    public function destroy(string $module, QualityDocument $qualityDocument): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManage($module);

        $code = $qualityDocument->code;
        $title = $qualityDocument->title;

        DB::transaction(function () use ($qualityDocument): void {
            $this->deleteStoredFile($qualityDocument);
            $qualityDocument->delete();
        });

        $this->auditLogService->logEvent(
            eventType: 'document',
            action: 'delete',
            model: $qualityDocument,
            metadata: [
                'code' => $code,
                'title' => $title,
            ],
        );

        return redirect()
            ->route('quality-documents.admin.index', ['module' => $module])
            ->with('success', 'Documento eliminado correctamente.');
    }

    public function download(string $module, QualityDocument $qualityDocument): StreamedResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeDocumentView($qualityDocument, $module);

        return $this->streamFileDownload($qualityDocument);
    }

    public function openLink(string $module, QualityDocument $qualityDocument): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeDocumentView($qualityDocument, $module);

        return $this->redirectToExternalLink($qualityDocument);
    }

    public function downloadMine(string $module, QualityDocument $qualityDocument): StreamedResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizePersonalDocumentView($qualityDocument, $module);

        return $this->streamFileDownload($qualityDocument);
    }

    public function openMine(string $module, QualityDocument $qualityDocument): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizePersonalDocumentView($qualityDocument, $module);

        return $this->redirectToExternalLink($qualityDocument);
    }

    private function authorizeManage(string $module): void
    {
        abort_unless($module === 'calidad', 404);
        abort_unless(auth()->user()?->can('manage.quality.documents'), 403);
    }

    private function authorizeModuleAccess(string $module): void
    {
        $user = auth()->user();

        abort_unless($user instanceof User && $user->canViewDocumentsBoardFor($module), 403);
    }

    private function authorizeDocumentView(QualityDocument $document, string $module): void
    {
        $this->authorizeModuleAccess($module);

        abort_unless($document->is_active, 404);
        abort_unless($document->isAssignedToArea($module), 403);
    }

    private function authorizePersonalDocumentsAccess(string $module): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        abort_unless(
            $user->canViewDocumentsBoardFor($module) && QualityDocument::hasActiveForUser($user->id),
            403
        );
    }

    private function authorizePersonalDocumentView(QualityDocument $document, string $module): void
    {
        $this->authorizePersonalDocumentsAccess($module);

        abort_unless($document->is_active, 404);

        $user = auth()->user();
        abort_unless($user instanceof User && $document->isAssignedToUser($user->id), 403);
    }

    private function syncAreas(QualityDocument $document, array $areaKeys): void
    {
        $document->areas()->delete();

        foreach (array_unique($areaKeys) as $areaKey) {
            $document->areas()->create(['area_key' => $areaKey]);
        }
    }

    /**
     * @param  array<int, int|string>  $userIds
     */
    private function syncUsers(QualityDocument $document, array $userIds): void
    {
        $document->assignedUsers()->delete();

        foreach (array_unique(array_map('intval', $userIds)) as $userId) {
            if ($userId > 0) {
                $document->assignedUsers()->create(['user_id' => $userId]);
            }
        }
    }

    private function streamFileDownload(QualityDocument $document): StreamedResponse
    {
        abort_unless($document->isFile() && $document->file_path, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_name ?? basename($document->file_path)
        );
    }

    private function redirectToExternalLink(QualityDocument $document): RedirectResponse
    {
        abort_unless($document->isLink() && $document->external_url, 404);

        return redirect()->away($document->external_url);
    }

    /**
     * @return Collection<int, User>
     */
    private function activeUsersList()
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'area_key']);
    }

    private function storeUploadedFile(QualityDocument $document, UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs('quality-documents', $storedName, 'local');

        $document->update([
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'external_url' => null,
        ]);
    }

    private function deleteStoredFile(QualityDocument $document): void
    {
        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function documentCatalogs(): array
    {
        return [
            'processes' => config('quality-documents.processes', []),
            'types' => config('quality-documents.types', []),
            'origins' => config('quality-documents.origins', []),
            'document_statuses' => config('quality-documents.document_statuses', []),
            'activity_statuses' => config('quality-documents.activity_statuses', []),
            'storage_types' => config('quality-documents.storage_types', []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function metadataFromRequest(StoreQualityDocumentRequest|UpdateQualityDocumentRequest $request): array
    {
        return [
            'title' => $request->string('title')->toString(),
            'code' => $request->string('code')->toString(),
            'process_key' => $request->string('process_key')->toString(),
            'document_type' => $request->string('document_type')->toString(),
            'origin' => $request->string('origin')->toString(),
            'document_status' => $request->string('document_status')->toString(),
            'activity_status' => $request->string('activity_status')->toString(),
            'storage_type' => $request->string('storage_type')->toString(),
            'current_version' => $request->input('current_version'),
            'last_updated_at' => $request->input('last_updated_at'),
            'retention_period' => $request->input('retention_period'),
            'final_disposition' => $request->input('final_disposition'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function documentAuditSnapshot(QualityDocument $document): array
    {
        return [
            'code' => $document->code,
            'title' => $document->title,
            'process_key' => $document->process_key,
            'document_type' => $document->document_type,
            'current_version' => $document->current_version,
        ];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function diffAuditFields(array $before, array $after): array
    {
        $oldValues = [];
        $newValues = [];

        foreach ($before as $field => $beforeValue) {
            $afterValue = $after[$field] ?? null;

            if ($beforeValue !== $afterValue) {
                $oldValues[$field] = $beforeValue;
                $newValues[$field] = $afterValue;
            }
        }

        return [$oldValues, $newValues];
    }
}
