<?php

namespace App\Http\Controllers\QualityDocuments;

use App\Http\Controllers\Controller;
use App\Http\Requests\QualityDocuments\StoreQualityDocumentRequest;
use App\Http\Requests\QualityDocuments\UpdateQualityDocumentRequest;
use App\Models\QualityDocument;
use App\Models\User;
use App\Traits\HasQualityDocumentTabs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QualityDocumentController extends Controller
{
    use HasQualityDocumentTabs;

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
        $this->authorizeManage();

        $documents = QualityDocument::query()
            ->with(['uploader', 'areas', 'assignedUsers.user'])
            ->latest()
            ->get();

        return view('modules.quality-documents.admin.index', [
            'module' => $module,
            'documents' => $documents,
            'subTabs' => $this->getQualityDocumentSubTabs($module),
        ]);
    }

    public function create(string $module): View
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManage();

        return view('modules.quality-documents.admin.create', [
            'module' => $module,
            'areas' => config('access.areas', []),
            'documentTypes' => config('access.quality_document_types', []),
            'users' => $this->activeUsersList(),
            'selectedUsers' => [],
            'subTabs' => $this->getQualityDocumentSubTabs($module),
        ]);
    }

    public function store(StoreQualityDocumentRequest $request, string $module): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManage();

        DB::transaction(function () use ($request): void {
            $document = QualityDocument::create([
                'title' => $request->string('title')->toString(),
                'code' => $request->string('code')->toString(),
                'root_process' => $request->string('root_process')->toString(),
                'document_type' => $request->string('document_type')->toString(),
                'description' => $request->input('description'),
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

        return redirect()
            ->route('quality-documents.admin.index', ['module' => $module])
            ->with('success', 'Documento publicado correctamente.');
    }

    public function edit(string $module, QualityDocument $qualityDocument): View
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManage();

        $qualityDocument->load(['areas', 'assignedUsers']);

        return view('modules.quality-documents.admin.edit', [
            'module' => $module,
            'document' => $qualityDocument,
            'areas' => config('access.areas', []),
            'documentTypes' => config('access.quality_document_types', []),
            'users' => $this->activeUsersList(),
            'selectedAreas' => $qualityDocument->assignedAreaKeys(),
            'selectedUsers' => $qualityDocument->assignedUserIds(),
            'subTabs' => $this->getQualityDocumentSubTabs($module),
        ]);
    }

    public function update(UpdateQualityDocumentRequest $request, string $module, QualityDocument $qualityDocument): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManage();

        DB::transaction(function () use ($request, $qualityDocument): void {
            $qualityDocument->update([
                'title' => $request->string('title')->toString(),
                'code' => $request->string('code')->toString(),
                'root_process' => $request->string('root_process')->toString(),
                'document_type' => $request->string('document_type')->toString(),
                'description' => $request->input('description'),
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

        return redirect()
            ->route('quality-documents.admin.index', ['module' => $module])
            ->with('success', 'Documento actualizado correctamente.');
    }

    public function toggleStatus(string $module, QualityDocument $qualityDocument): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManage();

        $qualityDocument->update([
            'is_active' => ! $qualityDocument->is_active,
        ]);

        return redirect()
            ->route('quality-documents.admin.index', ['module' => $module])
            ->with('success', 'Estado del documento actualizado.');
    }

    public function destroy(string $module, QualityDocument $qualityDocument): RedirectResponse
    {
        $this->abortIfUnknownModule($module);
        $this->authorizeManage();

        DB::transaction(function () use ($qualityDocument): void {
            $this->deleteStoredFile($qualityDocument);
            $qualityDocument->delete();
        });

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

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('manage.quality.documents'), 403);
    }

    private function authorizeModuleAccess(string $module): void
    {
        $user = auth()->user();

        abort_unless(
            $user?->can("view.area.{$module}") || $user?->can("manage.area.{$module}") || $user?->can('manage.quality.documents'),
            403
        );
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

    private function userCanView(QualityDocument $document, User $user, ?string $module = null): bool
    {
        if (! $document->is_active) {
            return false;
        }

        if ($user->can('manage.quality.documents')) {
            return true;
        }

        if ($document->isAssignedToUser($user->id)) {
            return true;
        }

        if ($module && $document->isAssignedToArea($module)) {
            return $user->can("view.area.{$module}") || $user->can("manage.area.{$module}");
        }

        return false;
    }

    private function abortIfUnknownModule(string $module): void
    {
        abort_unless(array_key_exists($module, config('access.areas', [])), 404);
    }

    /**
     * @param  array<int, string>  $areaKeys
     */
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
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function activeUsersList()
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'area_key']);
    }

    private function storeUploadedFile(QualityDocument $document, \Illuminate\Http\UploadedFile $file): void
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
}
