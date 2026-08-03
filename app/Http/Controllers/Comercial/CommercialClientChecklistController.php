<?php

namespace App\Http\Controllers\Comercial;

use App\Exports\BaseExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Comercial\UpdateCommercialClientChecklistRequest;
use App\Models\CommercialClient;
use App\Models\CommercialClientDocumentItem;
use App\Support\CommercialDocumentCatalog;
use App\Support\DisplayDate;
use App\Traits\HasGestionClientesTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommercialClientChecklistController extends Controller
{
    use HasGestionClientesTabs;

    public function index(Request $request): View
    {
        $this->authorizeView();

        $filters = [
            'q' => trim($request->string('q')->toString()),
            'city' => trim($request->string('city')->toString()),
            'doc_vigencia' => $request->string('doc_vigencia')->toString(),
        ];

        $clients = $this->filteredClientsQuery($filters)->get();

        return view('areas.comercial.matriz-clientes.clients.checklist.index', [
            'clients' => $clients,
            'documentFields' => CommercialDocumentCatalog::documentFields(),
            'filters' => $filters,
            'documentStatuses' => CommercialDocumentCatalog::documentStatuses(),
            'canManage' => $this->canManage(),
            'subTabs' => $this->getGestionClientesSubTabs('clientes'),
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->authorizeView();

        $filters = [
            'q' => trim($request->string('q')->toString()),
            'city' => trim($request->string('city')->toString()),
            'doc_vigencia' => $request->string('doc_vigencia')->toString(),
        ];

        $clients = $this->filteredClientsQuery($filters)->get();
        $documentFields = CommercialDocumentCatalog::documentFields();

        $exportRows = $clients->map(function (CommercialClient $client) use ($documentFields) {
            $itemsByKey = $client->documentItems->keyBy('document_key');
            $row = (object) [
                'nit' => $client->nit,
                'name' => $client->name,
                'city' => $client->city,
                'documentation_expires_on' => DisplayDate::date($client->documentation_expires_on),
                'alert_days_before' => $client->alert_days_before ?? CommercialDocumentCatalog::DEFAULT_ALERT_DAYS,
            ];

            foreach ($documentFields as $documentKey => $label) {
                $row->{$documentKey} = CommercialDocumentCatalog::statusLabel($itemsByKey->get($documentKey)?->status);
            }

            return $row;
        });

        $columns = [
            ['key' => 'nit', 'label' => 'NIT'],
            ['key' => 'name', 'label' => 'Cliente'],
            ['key' => 'city', 'label' => 'Ciudad'],
        ];
        foreach ($documentFields as $documentKey => $label) {
            $columns[] = ['key' => $documentKey, 'label' => $label];
        }
        $columns[] = ['key' => 'documentation_expires_on', 'label' => 'Vencimiento'];
        $columns[] = ['key' => 'alert_days_before', 'label' => 'Dias anticipacion'];

        return (new BaseExport(
            $exportRows,
            $columns,
            'checklist_documental_'.now()->format('Y-m-d').'.xlsx',
            'Checklist documental - SJ Seguridad'
        ))->download();
    }

    public function update(UpdateCommercialClientChecklistRequest $request, CommercialClient $client): RedirectResponse
    {
        $this->authorizeManage();

        $validated = $request->validated();
        $expiresOn = $validated['documentation_expires_on'] ?? null;
        $alertDays = $validated['alert_days_before'] ?? null;

        if ($expiresOn !== null && $alertDays === null) {
            $alertDays = CommercialDocumentCatalog::DEFAULT_ALERT_DAYS;
        }

        $client->update([
            'documentation_expires_on' => $expiresOn,
            'alert_days_before' => $alertDays,
            'updated_by' => $request->user()->id,
        ]);

        $documents = $validated['documents'] ?? [];
        foreach (CommercialDocumentCatalog::documentKeys() as $documentKey) {
            if (! array_key_exists($documentKey, $documents)) {
                continue;
            }

            $status = $documents[$documentKey];
            if ($status === null || $status === '') {
                continue;
            }

            CommercialClientDocumentItem::query()
                ->where('commercial_client_id', $client->id)
                ->where('document_key', $documentKey)
                ->update(['status' => $status]);
        }

        return redirect()
            ->route('comercial.matriz.clients.checklist.index', $request->only(['q', 'city', 'doc_vigencia']))
            ->with('status', 'Checklist actualizado para '.$client->name.'.');
    }

    /**
     * @param  array{q: string, city: string, doc_vigencia: string}  $filters
     * @return Builder<CommercialClient>
     */
    private function filteredClientsQuery(array $filters): Builder
    {
        return CommercialClient::query()
            ->with(['documentItems'])
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $q = $filters['q'];
                $query->where(function (Builder $inner) use ($q): void {
                    $inner->where('nit', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%")
                        ->orWhere('legal_rep_name', 'like', "%{$q}%");
                });
            })
            ->when($filters['city'] !== '', fn (Builder $query) => $query->where('city', 'like', "%{$filters['city']}%"))
            ->when($filters['doc_vigencia'] === 'expired', fn (Builder $query) => $query->documentationExpired())
            ->when($filters['doc_vigencia'] === 'expiring', fn (Builder $query) => $query->documentationExpiring())
            ->orderBy('name');
    }

    private function authorizeView(): void
    {
        abort_unless(
            auth()->user()?->can('comercial.matriz.view')
            || auth()->user()?->can('comercial.matriz.manage')
            || auth()->user()?->can('view.board.comercial.matriz_clientes')
            || auth()->user()?->can('manage.users'),
            403
        );
    }

    private function authorizeManage(): void
    {
        abort_unless(
            auth()->user()?->can('comercial.matriz.manage')
            || auth()->user()?->can('manage.users'),
            403
        );
    }

    private function canManage(): bool
    {
        return (bool) (auth()->user()?->can('comercial.matriz.manage') || auth()->user()?->can('manage.users'));
    }
}
