<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttachNotificationTypeEmailRequest;
use App\Models\NotificationEmail;
use App\Models\NotificationType;
use App\Services\Notifications\NotificationConfigService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class NotificationConfigController extends Controller
{
    public function __construct(
        private readonly NotificationConfigService $notificationConfig,
    ) {}

    public function index(): View
    {
        $moduleGroups = $this->notificationConfig->typesGroupedByModule(adminConfigurableOnly: true);

        return view('admin.notifications.index', [
            'moduleGroups' => $moduleGroups,
            'stats' => $this->notificationConfig->dashboardStats($moduleGroups),
            'fallbackRecipient' => $this->notificationConfig->fallbackRecipient(),
            'suggestedEmails' => $this->notificationConfig->knownRecipientEmails(),
            'initialModule' => request()->string('module')->toString() ?: null,
            'initialTypeId' => request()->has('type') ? request()->integer('type') : null,
        ]);
    }

    public function attachTypeEmail(AttachNotificationTypeEmailRequest $request, NotificationType $notificationType): RedirectResponse
    {
        abort_unless($this->notificationConfig->isAdminConfigurable($notificationType), 404);

        $this->notificationConfig->addEmailToType(
            $notificationType,
            $request->string('email')->toString(),
        );

        return $this->redirectToType($notificationType, 'Correo agregado a «'.$notificationType->label.'».');
    }

    public function detachTypeEmail(NotificationType $notificationType, NotificationEmail $notificationEmail): RedirectResponse
    {
        abort_unless($this->notificationConfig->isAdminConfigurable($notificationType), 404);

        $this->notificationConfig->removeEmailFromType($notificationType, $notificationEmail);

        return $this->redirectToType($notificationType, 'Correo quitado de «'.$notificationType->label.'».');
    }

    private function redirectToType(NotificationType $notificationType, string $status): RedirectResponse
    {
        return redirect()
            ->route('admin.notifications.index', [
                'module' => $notificationType->module,
                'type' => $notificationType->id,
            ])
            ->withFragment('notification-type-'.$notificationType->id)
            ->with('status', $status);
    }
}
