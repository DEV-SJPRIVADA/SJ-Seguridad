<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comercial\StoreCommercialServiceRequest;
use App\Http\Requests\Comercial\UpdateCommercialServiceRequest;
use App\Models\CommercialClient;
use App\Models\CommercialClientType;
use App\Models\CommercialSector;
use App\Models\CommercialService;
use App\Models\CommercialServiceType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CommercialServiceController extends Controller
{
    public function create(CommercialClient $client): View
    {
        $this->authorizeManage();

        return view('areas.comercial.matriz-clientes.services.create', [
            'client' => $client,
            'service' => new CommercialService(['portfolio' => CommercialService::PORTFOLIO_SEG_FISICA]),
            ...$this->formOptions(),
        ]);
    }

    public function store(StoreCommercialServiceRequest $request, CommercialClient $client): RedirectResponse
    {
        $this->authorizeManage();

        $client->services()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('comercial.matriz.clients.show', $client)
            ->with('status', 'Servicio agregado.');
    }

    public function edit(CommercialClient $client, CommercialService $service): View
    {
        $this->authorizeManage();
        $this->ensureServiceBelongsToClient($client, $service);

        return view('areas.comercial.matriz-clientes.services.edit', [
            'client' => $client,
            'service' => $service,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateCommercialServiceRequest $request, CommercialClient $client, CommercialService $service): RedirectResponse
    {
        $this->authorizeManage();
        $this->ensureServiceBelongsToClient($client, $service);

        $service->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('comercial.matriz.clients.show', $client)
            ->with('status', 'Servicio actualizado.');
    }

    public function inactivate(CommercialClient $client, CommercialService $service): RedirectResponse
    {
        $this->authorizeManage();
        $this->ensureServiceBelongsToClient($client, $service);

        $service->update([
            'portfolio' => CommercialService::PORTFOLIO_INACTIVOS,
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('comercial.matriz.clients.show', $client)
            ->with('status', 'Servicio movido a Inactivos.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'portfolios' => CommercialService::portfolios(),
            'documentStatuses' => CommercialService::documentStatuses(),
            'documentFields' => CommercialService::documentFields(),
            'sectors' => CommercialSector::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'clientTypes' => CommercialClientType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'serviceTypes' => CommercialServiceType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ];
    }

    private function ensureServiceBelongsToClient(CommercialClient $client, CommercialService $service): void
    {
        abort_unless($service->commercial_client_id === $client->id, 404);
    }

    private function authorizeManage(): void
    {
        abort_unless(
            auth()->user()?->can('comercial.matriz.manage')
            || auth()->user()?->can('manage.users'),
            403
        );
    }
}
