<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSupplySiteRequest;
use App\Http\Requests\Admin\UpdateSupplySiteRequest;
use App\Models\SupplySite;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SupplySiteController extends Controller
{
    public function index(): JsonResponse
    {
        $sites = SupplySite::query()
            ->ordered()
            ->get()
            ->map(fn (SupplySite $site): array => $this->sitePayload($site));

        return response()->json(['sites' => $sites]);
    }

    public function store(StoreSupplySiteRequest $request): JsonResponse
    {
        $site = SupplySite::query()->create([
            'name' => $this->generateUniqueName(
                $request->string('city')->toString(),
                $request->string('utilization')->toString()
            ),
            'utilization' => $request->string('utilization')->toString(),
            'city' => $request->string('city')->toString(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'message' => 'Sede creada correctamente.',
            'site' => $this->sitePayload($site),
        ], 201);
    }

    public function update(UpdateSupplySiteRequest $request, SupplySite $supplySite): JsonResponse
    {
        $supplySite->update([
            'utilization' => $request->string('utilization')->toString(),
            'city' => $request->string('city')->toString(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return response()->json([
            'message' => 'Sede actualizada correctamente.',
            'site' => $this->sitePayload($supplySite->fresh()),
        ]);
    }

    public function destroy(SupplySite $supplySite): JsonResponse
    {
        $usersCount = $supplySite->users()->count();
        $requestsCount = $supplySite->supplyRequests()->count();

        if ($usersCount > 0 || $requestsCount > 0) {
            return response()->json([
                'message' => 'No se puede eliminar la sede porque tiene usuarios o solicitudes asociadas. Puede desactivarla en su lugar.',
            ], 422);
        }

        $supplySite->delete();

        return response()->json([
            'message' => 'Sede eliminada correctamente.',
        ]);
    }

    private function generateUniqueName(string $city, string $utilization): string
    {
        $base = Str::slug($city.'_'.$utilization, '_');
        if ($base === '') {
            $base = 'sede';
        }

        $name = $base;
        $suffix = 1;

        while (SupplySite::query()->where('name', $name)->exists()) {
            $name = $base.'_'.$suffix;
            $suffix++;
        }

        return $name;
    }

    /**
     * @return array<string, mixed>
     */
    private function sitePayload(SupplySite $site): array
    {
        return [
            'id' => $site->id,
            'name' => $site->name,
            'utilization' => $site->utilization,
            'city' => $site->city,
            'is_active' => $site->is_active,
            'users_count' => $site->users()->count(),
            'requests_count' => $site->supplyRequests()->count(),
        ];
    }
}
