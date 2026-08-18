<?php

namespace App\Http\Controllers\GestionHumana;

use App\Http\Controllers\Concerns\HandlesImportFailureReports;
use App\Http\Controllers\Controller;
use App\Http\Requests\GestionHumana\ImportEmployeeArchiveRequest;
use App\Http\Requests\GestionHumana\StoreEmployeeArchiveConsultationRequest;
use App\Http\Requests\GestionHumana\UpdateEmployeeArchiveConsultationItemRequest;
use App\Http\Requests\GestionHumana\UpdateEmployeeArchiveRequest;
use App\Models\EmployeeArchiveConsultation;
use App\Models\EmployeeArchiveConsultationItem;
use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;
use App\Services\Access\ArchivoAccessService;
use App\Services\GestionHumana\EmployeeArchiveConsultationParser;
use App\Services\GestionHumana\EmployeeArchiveImportService;
use App\Services\GestionHumana\EmployeeArchiveLaborHistoryDatatableService;
use App\Traits\HasArchivoTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArchivoController extends Controller
{
    use HandlesImportFailureReports;
    use HasArchivoTabs;

    public function __construct(
        private readonly ArchivoAccessService $archivoAccess,
        private readonly EmployeeArchiveImportService $importService,
        private readonly EmployeeArchiveConsultationParser $consultationParser,
        private readonly EmployeeArchiveLaborHistoryDatatableService $laborHistoryDatatable,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        $this->authorizeView();

        return redirect()->route('gestion-humana.archivo.labor-histories.index', $request->query());
    }

    public function laborHistories(Request $request): View
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());
        $consultationId = $request->integer('consultation') ?: null;
        $importResult = session('import_result');
        $importHasFailures = is_array($importResult) && (($importResult['failures_count'] ?? 0) > 0 || ! empty($importResult['report_token']));

        $activeConsultation = $this->resolveActiveConsultation($consultationId);

        return view('areas.gestion_humana.archivo.labor-histories', [
            'datatableUrl' => route('gestion-humana.archivo.labor-histories.datatable', $request->query()),
            'filters' => [
                'q' => $q,
                'consultation' => $consultationId,
            ],
            'subTabs' => $this->getArchivoSubTabs('historias-laborales'),
            'canManage' => $this->canManage(),
            'canExportArchive' => $this->canExportArchive(),
            'importResult' => $importResult,
            'importHasFailures' => $importHasFailures,
            'activeConsultation' => $activeConsultation,
            'consultationTypes' => config('employee_ficha.archive_consultation_types', []),
        ]);
    }

    public function laborHistoriesDatatable(Request $request): JsonResponse
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());
        $consultationId = $request->integer('consultation') ?: null;
        $activeConsultation = $this->resolveActiveConsultation($consultationId);
        $filters = [
            'q' => $q,
            'consultation' => $consultationId,
        ];

        return $this->laborHistoryDatatable->respond(
            $request,
            $this->laborHistoriesQuery($q, $activeConsultation),
            $filters,
            $this->canManage(),
        );
    }

    public function consultationHistory(Request $request): View
    {
        $this->authorizeView();

        $q = trim($request->string('q')->toString());
        $month = $request->integer('month') ?: null;
        $week = $request->integer('week') ?: null;

        $items = EmployeeArchiveConsultationItem::query()
            ->with(['consultation.user'])
            ->when($q !== '', function (Builder $query) use ($q): void {
                $query->where(function (Builder $inner) use ($q): void {
                    $inner->where('document_number', 'like', "%{$q}%")
                        ->orWhere('full_name', 'like', "%{$q}%")
                        ->orWhere('delivered_to', 'like', "%{$q}%")
                        ->orWhere('concept', 'like', "%{$q}%");
                });
            })
            ->when($month !== null, fn (Builder $query) => $query->where('month_number', $month))
            ->when($week !== null, fn (Builder $query) => $query->where('week_of_month', $week))
            ->latest('created_at')
            ->get();

        return view('areas.gestion_humana.archivo.consultation-history', [
            'items' => $items,
            'filters' => [
                'q' => $q,
                'month' => $month,
                'week' => $week,
            ],
            'subTabs' => $this->getArchivoSubTabs('historial-consultas'),
            'canManage' => $this->canManage(),
            'consultationTypes' => config('employee_ficha.archive_consultation_types', []),
        ]);
    }

    public function consult(StoreEmployeeArchiveConsultationRequest $request): RedirectResponse
    {
        $this->authorizeView();

        $documents = $this->consultationParser->parseDocuments($request->string('documents')->toString());

        if ($documents === []) {
            return back()
                ->withInput()
                ->withErrors(['documents' => 'Ingrese al menos una cedula valida para consultar.']);
        }

        $normalizedDocuments = array_map(
            fn (string $document): string => $this->consultationParser->normalizeDocument($document),
            $documents,
        );

        $matchedEntries = PersonalRequisitionFichaEntry::query()
            ->inFicha()
            ->with('profile')
            ->where(function (Builder $query) use ($normalizedDocuments): void {
                $query->whereIn('hired_document', $normalizedDocuments)
                    ->orWhereHas('profile', fn (Builder $profileQuery) => $profileQuery->whereIn('document_number', $normalizedDocuments));
            })
            ->get();

        $matchedDocuments = [];

        foreach ($matchedEntries as $entry) {
            $matchedDocuments[] = $this->consultationParser->normalizeDocument(
                (string) ($entry->profile?->document_number ?: $entry->hired_document),
            );
        }

        $matchedDocuments = array_values(array_unique($matchedDocuments));

        $documentsNotFound = array_values(array_filter(
            $documents,
            fn (string $document): bool => ! in_array(
                $this->consultationParser->normalizeDocument($document),
                $matchedDocuments,
                true,
            ),
        ));

        $deliveredTo = trim($request->string('delivered_to')->toString()) ?: null;
        $typeKeys = $request->validated('consultation_types');
        $consultation = DB::transaction(function () use ($request, $documents, $documentsNotFound, $deliveredTo, $typeKeys, $matchedEntries): EmployeeArchiveConsultation {
            $consultation = EmployeeArchiveConsultation::query()->create([
                'user_id' => $request->user()->id,
                'document_numbers' => $documents,
                'consultation_types' => $typeKeys,
                'documents_requested' => count($documents),
                'documents_matched' => count(array_unique(array_map(
                    fn (PersonalRequisitionFichaEntry $entry): string => $this->consultationParser->normalizeDocument(
                        (string) ($entry->profile?->document_number ?: $entry->hired_document),
                    ),
                    $matchedEntries->all(),
                ))),
                'documents_not_found' => $documentsNotFound !== [] ? $documentsNotFound : null,
                'delivered_to' => $deliveredTo,
            ]);

            $this->createConsultationItems($consultation, $documents, $matchedEntries, $deliveredTo);

            return $consultation;
        });

        $message = sprintf(
            'Consulta registrada: %d cedula(s) solicitada(s), %d encontrada(s) en ficha.',
            $consultation->documents_requested,
            $consultation->documents_matched,
        );

        if ($documentsNotFound !== []) {
            $message .= sprintf(' %d cedula(s) no encontrada(s).', count($documentsNotFound));
        }

        return redirect()
            ->route('gestion-humana.archivo.labor-histories.index', ['consultation' => $consultation->id])
            ->with('status', $message);
    }

    public function updateConsultationItem(
        UpdateEmployeeArchiveConsultationItemRequest $request,
        EmployeeArchiveConsultationItem $consultationItem,
    ): RedirectResponse {
        $this->authorizeView();

        $consultationItem->fill([
            'received' => $request->boolean('received'),
            'observation' => trim($request->string('observation')->toString()) ?: null,
        ]);
        $consultationItem->save();

        return redirect()
            ->route('gestion-humana.archivo.consultation-history.index', array_filter([
                'q' => $request->string('q')->toString() ?: null,
                'month' => $request->integer('month') ?: null,
                'week' => $request->integer('week') ?: null,
            ]))
            ->with('status', 'Registro de consulta actualizado.');
    }

    public function import(ImportEmployeeArchiveRequest $request): RedirectResponse
    {
        $path = $request->file('import_file')?->getRealPath();

        if ($path === false || $path === null) {
            return back()->withErrors(['import_file' => 'No se pudo leer el archivo subido.']);
        }

        try {
            $stats = $this->importService->import($path);
        } catch (\Throwable $e) {
            return back()->withErrors(['import_file' => $e->getMessage()]);
        }

        $message = sprintf(
            'Importacion de archivo finalizada: %d empleados actualizados.',
            $stats['updated'],
        );

        if ($stats['empty_rows'] > 0) {
            $message .= sprintf(' %d filas sin cedula ignoradas.', $stats['empty_rows']);
        }

        if ($stats['skipped'] > 0) {
            $message .= sprintf(' %d filas omitidas o con error (revise el detalle).', $stats['skipped']);
        }

        if ($stats['errors'] !== []) {
            Log::warning('Importacion archivo empleados con errores por fila.', [
                'user_id' => $request->user()->id,
                'errors_count' => count($stats['errors']),
                'errors' => array_slice($stats['errors'], 0, 200),
            ]);
        }

        $importResult = $this->buildImportResultPayload(
            $request->user(),
            $stats,
            'employee_archive',
            'Archivo empleados',
            [
                'Actualizados' => $stats['updated'],
                'Omitidos / error' => $stats['skipped'],
                'Filas vacias' => $stats['empty_rows'],
            ],
            array_keys(array_merge(
                config('employee_ficha.import_columns', []),
                config('employee_ficha.archive_export_extra_columns', []),
            )),
            'reporte_importacion_archivo',
        );

        return redirect()
            ->route('gestion-humana.archivo.labor-histories.index')
            ->with('status', $message)
            ->with('import_result', $importResult);
    }

    public function downloadImportReport(Request $request, string $token): StreamedResponse
    {
        abort_unless($this->canManage(), 403);

        return $this->downloadImportFailureReport($request->user(), $token, 'employee_archive');
    }

    public function update(UpdateEmployeeArchiveRequest $request, PersonalRequisitionFichaEntry $fichaEntry): RedirectResponse
    {
        abort_unless($this->canManage(), 403);

        if ($fichaEntry->moved_to_ficha_at === null) {
            abort(404);
        }

        DB::transaction(function () use ($request, $fichaEntry): void {
            $profile = $fichaEntry->profile;

            if ($profile === null) {
                $profile = new EmployeeFichaProfile([
                    'personal_requisition_ficha_entry_id' => $fichaEntry->id,
                    'document_number' => $fichaEntry->hired_document,
                    'full_name' => $fichaEntry->hired_full_name,
                    'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
                ]);
            }

            $profile->fill($request->validated());
            $profile->save();
        });

        return redirect()
            ->route('gestion-humana.archivo.labor-histories.index', array_filter([
                'q' => $request->string('q')->toString() ?: null,
                'consultation' => $request->integer('consultation') ?: null,
            ]))
            ->with('status', 'Ubicacion actualizada correctamente.');
    }

    /**
     * @param  list<string>  $documents
     * @param  Collection<int, PersonalRequisitionFichaEntry>  $matchedEntries
     */
    private function createConsultationItems(
        EmployeeArchiveConsultation $consultation,
        array $documents,
        Collection $matchedEntries,
        ?string $deliveredTo,
    ): void {
        $entriesByDocument = [];

        foreach ($matchedEntries as $entry) {
            $normalized = $this->consultationParser->normalizeDocument(
                (string) ($entry->profile?->document_number ?: $entry->hired_document),
            );
            $entriesByDocument[$normalized] = $entry;
        }

        $registeredAt = $consultation->created_at ?? now();
        $calendarMeta = EmployeeArchiveConsultationItem::calendarMeta($registeredAt);
        $concept = $consultation->conceptLabel();

        foreach ($documents as $document) {
            $normalized = $this->consultationParser->normalizeDocument($document);
            $entry = $entriesByDocument[$normalized] ?? null;

            EmployeeArchiveConsultationItem::query()->create([
                'employee_archive_consultation_id' => $consultation->id,
                'personal_requisition_ficha_entry_id' => $entry?->id,
                'document_number' => $document,
                'full_name' => $entry ? ($entry->profile?->full_name ?: $entry->hired_full_name) : null,
                'archive_shelf' => $entry?->profile?->archive_shelf,
                'archive_box' => $entry?->profile?->archive_box,
                'concept' => $concept,
                'delivered_to' => $deliveredTo,
                'received' => false,
                'observation' => null,
                ...$calendarMeta,
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function normalizedConsultationDocuments(EmployeeArchiveConsultation $consultation): array
    {
        return array_values(array_unique(array_map(
            fn (string $document): string => $this->consultationParser->normalizeDocument($document),
            $consultation->document_numbers ?? [],
        )));
    }

    private function resolveActiveConsultation(?int $consultationId): ?EmployeeArchiveConsultation
    {
        if ($consultationId === null) {
            return null;
        }

        return EmployeeArchiveConsultation::query()
            ->with('user')
            ->find($consultationId);
    }

    /**
     * @return Builder<PersonalRequisitionFichaEntry>
     */
    private function laborHistoriesQuery(string $q, ?EmployeeArchiveConsultation $activeConsultation): Builder
    {
        return PersonalRequisitionFichaEntry::query()
            ->inFicha()
            ->when($q !== '', function (Builder $query) use ($q): void {
                $query->where(function (Builder $inner) use ($q): void {
                    $inner->where('hired_document', 'like', "%{$q}%")
                        ->orWhere('hired_full_name', 'like', "%{$q}%")
                        ->orWhereHas('requisition', fn (Builder $r) => $r->where('code', 'like', "%{$q}%"));
                });
            })
            ->when($activeConsultation !== null, function (Builder $query) use ($activeConsultation): void {
                $normalizedDocuments = $this->normalizedConsultationDocuments($activeConsultation);

                if ($normalizedDocuments === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->where(function (Builder $inner) use ($normalizedDocuments): void {
                    $inner->whereIn('hired_document', $normalizedDocuments)
                        ->orWhereHas('profile', fn (Builder $profileQuery) => $profileQuery->whereIn('document_number', $normalizedDocuments));
                });
            });
    }

    private function authorizeView(): void
    {
        abort_unless($this->archivoAccess->canView(auth()->user()), 403);
    }

    private function canManage(): bool
    {
        return $this->archivoAccess->canManage(auth()->user());
    }

    private function canExportArchive(): bool
    {
        return $this->archivoAccess->canExportArchiveTemplate(auth()->user());
    }
}
