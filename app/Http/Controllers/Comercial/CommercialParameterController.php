<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comercial\StoreCommercialParameterRequest;
use App\Models\CommercialClientType;
use App\Models\CommercialPortfolio;
use App\Models\CommercialSector;
use App\Models\CommercialServiceType;
use App\Traits\HasGestionClientesTabs;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class CommercialParameterController extends Controller
{
    use HasGestionClientesTabs;

    /** @var array<string, array{label: string, model: class-string}> */
    private const PARAMETER_TYPES = [
        'sectors' => ['label' => 'Sectores', 'model' => CommercialSector::class],
        'client-types' => ['label' => 'Tipos de cliente', 'model' => CommercialClientType::class],
        'service-types' => ['label' => 'Tipos de servicio', 'model' => CommercialServiceType::class],
        'portfolios' => ['label' => 'Portafolios', 'model' => CommercialPortfolio::class],
    ];

    public function index(): View
    {
        $this->authorizeParameters();

        $catalogs = collect(self::PARAMETER_TYPES)
            ->map(function (array $definition, string $type): array {
                $modelClass = $definition['model'];

                return [
                    'key' => $type,
                    'label' => $definition['label'],
                    'items' => $modelClass::query()->orderBy('sort_order')->orderBy('name')->get(),
                    'hasSlug' => $type === 'portfolios',
                ];
            })
            ->values();

        return view('areas.comercial.matriz-clientes.parameters', [
            'catalogs' => $catalogs,
            'subTabs' => $this->getGestionClientesSubTabs('parametros'),
        ]);
    }

    public function store(StoreCommercialParameterRequest $request, string $type): RedirectResponse
    {
        $this->authorizeParameters();

        $definition = self::PARAMETER_TYPES[$type] ?? null;
        abort_unless($definition !== null, 404);

        $modelClass = $definition['model'];
        $name = Str::of($request->string('name')->toString())->trim()->squish()->toString();

        if ($type === 'portfolios') {
            $modelClass::query()->create([
                'slug' => Str::of($request->string('slug')->toString())->trim()->lower()->toString(),
                'name' => $name,
                'is_active' => $request->boolean('is_active', true),
                'sort_order' => (int) ($request->input('sort_order') ?? 0),
            ]);
        } else {
            $modelClass::query()->firstOrCreate(
                ['name' => $name],
                [
                    'is_active' => $request->boolean('is_active', true),
                    'sort_order' => (int) ($request->input('sort_order') ?? 0),
                ]
            );
        }

        return redirect()
            ->route('comercial.parameters.index')
            ->with('status', 'commercial-parameter-created');
    }

    public function update(StoreCommercialParameterRequest $request, string $type, int $parameterId): RedirectResponse
    {
        $this->authorizeParameters();

        $definition = self::PARAMETER_TYPES[$type] ?? null;
        abort_unless($definition !== null, 404);

        $record = $definition['model']::query()->findOrFail($parameterId);

        $record->update([
            'name' => Str::of($request->string('name')->toString())->trim()->squish()->toString(),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($request->input('sort_order') ?? $record->sort_order ?? 0),
        ]);

        return redirect()
            ->route('comercial.parameters.index')
            ->with('status', 'commercial-parameter-updated');
    }

    public function destroy(string $type, int $parameterId): RedirectResponse
    {
        $this->authorizeParameters();

        $definition = self::PARAMETER_TYPES[$type] ?? null;
        abort_unless($definition !== null, 404);

        $definition['model']::query()->findOrFail($parameterId)->delete();

        return redirect()
            ->route('comercial.parameters.index')
            ->with('status', 'commercial-parameter-deleted');
    }

    private function authorizeParameters(): void
    {
        $user = auth()->user();

        abort_unless(
            $user?->can('manage.commercial.parameters')
            || $user?->can('comercial.matriz.manage')
            || $user?->can('manage.users'),
            403
        );
    }
}
