<?php

namespace App\Http\Controllers\Matrices;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Matrices\Concerns\BuildsMatrixTabs;
use App\Http\Controllers\Matrices\Concerns\ResolvesMatrixContext;
use App\Http\Requests\Matrices\ShareMatrixRequest;
use App\Models\Matrices\Matrix;
use App\Models\Matrices\MatrixAreaAccess;
use App\Models\Matrices\MatrixUserAccess;
use App\Services\Matrices\MatrixAuditService;
use App\Services\Matrices\MatrixListingService;
use App\Services\Matrices\MatrixShareService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MatrixShareController extends Controller
{
    use BuildsMatrixTabs, ResolvesMatrixContext;

    public function __construct(
        private readonly MatrixListingService $listing,
        private readonly MatrixShareService $share,
        private readonly MatrixAuditService $audit,
    ) {}

    public function index(string $module, Matrix $matrix): View
    {
        $this->authorizeBoard($module);
        $this->authorizeMatrix('share', $matrix);

        $matrix->load('owner:id,name');

        return view('modules.matrices.share', [
            'module' => $module,
            'matrix' => $matrix,
            'userAccess' => $matrix->userAccess()
                ->with(['user:id,name,area_key', 'grantedBy:id,name'])
                ->orderByDesc('id')
                ->get(),
            'areaAccess' => $matrix->areaAccess()
                ->with('grantedBy:id,name')
                ->orderByDesc('id')
                ->get(),
            'areaOptions' => $this->listing->areaOptions(),
            'userOptions' => $this->listing->shareableUsers($matrix)
                ->mapWithKeys(fn ($user): array => [$user->id => $user->name.' — '.(config("access.areas.{$user->area_key}") ?? 'Sin área')])
                ->all(),
            'tabs' => $this->matrixTabs($matrix, $module, 'share'),
        ]);
    }

    public function store(string $module, Matrix $matrix, ShareMatrixRequest $request): RedirectResponse
    {
        $this->authorizeBoard($module);
        $this->authorizeMatrix('share', $matrix);

        $user = auth()->user();
        abort_unless($user !== null, 403);

        $data = $request->validated();

        if ($data['kind'] === 'area') {
            $grant = $this->share->upsertArea($matrix, $data, $user);
            $this->audit->matrix('share', 'matrix_shared_area', $matrix, [
                'area' => $grant->area_key,
                'can_register' => $grant->can_register,
                'can_view_others' => $grant->can_view_others,
                'can_edit_others' => $grant->can_edit_others,
            ]);

            return back()->with('status', 'Acceso por área guardado.');
        }

        $grant = $this->share->upsertUser($matrix, $data, $user);
        $this->audit->matrix('share', 'matrix_shared_user', $matrix, [
            'user_id' => $grant->user_id,
            'can_register' => $grant->can_register,
            'can_view_others' => $grant->can_view_others,
            'can_edit_others' => $grant->can_edit_others,
        ]);

        return back()->with('status', 'Acceso del usuario guardado.');
    }

    public function destroyUser(string $module, Matrix $matrix, MatrixUserAccess $userAccess): RedirectResponse
    {
        $this->authorizeBoard($module);
        $this->authorizeMatrix('share', $matrix);

        abort_unless((int) $userAccess->matrix_id === (int) $matrix->id, 404);

        $userId = $userAccess->user_id;
        $this->share->removeUser($userAccess);
        $this->audit->matrix('share', 'matrix_share_user_revoked', $matrix, ['user_id' => $userId]);

        return back()->with('status', 'Acceso del usuario retirado.');
    }

    public function destroyArea(string $module, Matrix $matrix, MatrixAreaAccess $areaAccess): RedirectResponse
    {
        $this->authorizeBoard($module);
        $this->authorizeMatrix('share', $matrix);

        abort_unless((int) $areaAccess->matrix_id === (int) $matrix->id, 404);

        $areaKey = $areaAccess->area_key;
        $this->share->removeArea($areaAccess);
        $this->audit->matrix('share', 'matrix_share_area_revoked', $matrix, ['area' => $areaKey]);

        return back()->with('status', 'Acceso del área retirado.');
    }
}
