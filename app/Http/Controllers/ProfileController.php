<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdateUserSignatureRequest;
use App\Services\Admin\AdminAuditLogService;
use App\Services\Users\UserSignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly UserSignatureService $signatures,
        private readonly AdminAuditLogService $audit,
    ) {}

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load(['roles', 'site']);

        return view('profile.edit', [
            'user' => $user,
            'signatureDataUri' => $this->signatures->dataUri($user),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updateSignature(UpdateUserSignatureRequest $request): RedirectResponse
    {
        $user = $request->user();
        $this->signatures->store($user, $request->file('signature'));
        $this->audit->logEvent('user_signature', 'uploaded', model: $user);

        return Redirect::route('profile.edit')->with('status', 'signature-updated');
    }

    public function destroySignature(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->signatures->delete($user);
        $this->audit->logEvent('user_signature', 'deleted', model: $user);

        return Redirect::route('profile.edit')->with('status', 'signature-deleted');
    }
}
