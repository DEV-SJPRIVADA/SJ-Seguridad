<?php

namespace App\Services\DevelopmentRequests;

use App\Mail\DevelopmentRequestMessageMail;
use App\Models\DevelopmentRequest;
use App\Models\DevelopmentRequestMessage;
use App\Models\User;
use App\Services\Notifications\NotificationConfigService;
use Illuminate\Support\Facades\Mail;

class DevelopmentRequestNotificationService
{
    public function __construct(
        private readonly NotificationConfigService $notificationConfig,
        private readonly DevelopmentRequestAuditLogService $auditLogService,
    ) {}

    public function notifyMessageParticipants(
        DevelopmentRequest $request,
        DevelopmentRequestMessage $message,
        User $author,
    ): void {
        $emails = $this->participantEmails($request, $author);

        foreach ($emails as $email) {
            Mail::to($email)->send(new DevelopmentRequestMessageMail($request, $message, $author));
        }

        $this->auditLogService->logEvent(
            eventType: 'development_request',
            action: 'message',
            metadata: [
                'development_request_id' => $request->id,
                'message_id' => $message->id,
                'body_length' => mb_strlen((string) $message->body),
                'recipients' => count($emails),
            ],
            model: $request,
            userId: $author->id,
        );
    }

    /**
     * @return list<string>
     */
    private function participantEmails(DevelopmentRequest $request, User $author): array
    {
        $request->loadMissing(['creator', 'leader', 'assignedProgrammer']);

        $emails = [];

        if (filled($request->requester_email)) {
            $emails[] = mb_strtolower(trim((string) $request->requester_email));
        }

        if ($request->creator?->email) {
            $emails[] = mb_strtolower(trim((string) $request->creator->email));
        }

        if ($request->leader?->email) {
            $emails[] = mb_strtolower(trim((string) $request->leader->email));
        }

        if ($request->assignedProgrammer?->email) {
            $emails[] = mb_strtolower(trim((string) $request->assignedProgrammer->email));
        }

        foreach ($this->notificationConfig->recipientEmails('development_requests', 'development_request_message') as $email) {
            $emails[] = mb_strtolower(trim($email));
        }

        $authorEmail = mb_strtolower(trim((string) $author->email));
        $emails = array_values(array_unique(array_filter($emails)));

        return array_values(array_filter(
            $emails,
            fn (string $email): bool => $email !== '' && $email !== $authorEmail,
        ));
    }
}
