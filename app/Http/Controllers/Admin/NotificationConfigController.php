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
        return view('admin.notifications.index', [
            'moduleGroups' => $this->notificationConfig->typesGroupedByModule(adminConfigurableOnly: true),
        ]);
    }

    public function attachTypeEmail(AttachNotificationTypeEmailRequest $request, NotificationType $notificationType): RedirectResponse
    {
        abort_unless($this->notificationConfig->isAdminConfigurable($notificationType), 404);

        $this->notificationConfig->addEmailToType(
            $notificationType,
            $request->string('email')->toString(),
        );

        return redirect()
            ->route('admin.notifications.index')
            ->with('status', 'Correo agregado a «'.$notificationType->label.'».');
    }

    public function detachTypeEmail(NotificationType $notificationType, NotificationEmail $notificationEmail): RedirectResponse
    {
        abort_unless($this->notificationConfig->isAdminConfigurable($notificationType), 404);

        $this->notificationConfig->removeEmailFromType($notificationType, $notificationEmail);

        return redirect()
            ->route('admin.notifications.index')
            ->with('status', 'Correo quitado de «'.$notificationType->label.'».');
    }
}
