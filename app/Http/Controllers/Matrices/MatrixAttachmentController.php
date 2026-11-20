<?php

namespace App\Http\Controllers\Matrices;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Matrices\Concerns\ResolvesMatrixContext;
use App\Models\Matrices\Matrix;
use App\Models\Matrices\MatrixSubmission;
use App\Models\Matrices\MatrixSubmissionAttachment;
use App\Services\Matrices\MatrixAttachmentService;
use App\Services\Matrices\MatrixAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MatrixAttachmentController extends Controller
{
    use ResolvesMatrixContext;

    public function __construct(
        private readonly MatrixAttachmentService $attachments,
        private readonly MatrixAuditService $audit,
    ) {}

    public function download(string $module, Matrix $matrix, MatrixSubmissionAttachment $attachment): StreamedResponse
    {
        $this->authorizeBoard($module);
        $submission = $this->authorizeAttachment($matrix, $attachment);

        $this->audit->submission('download', 'matrix_attachment_downloaded', $submission, [
            'attachment_id' => $attachment->id,
            'file' => $attachment->original_name,
        ]);

        return Storage::disk(MatrixAttachmentService::DISK)
            ->download($attachment->stored_path, $attachment->original_name);
    }

    public function preview(string $module, Matrix $matrix, MatrixSubmissionAttachment $attachment): StreamedResponse
    {
        $this->authorizeBoard($module);
        $this->authorizeAttachment($matrix, $attachment);

        abort_unless($attachment->isPreviewable(), 404);

        return Storage::disk(MatrixAttachmentService::DISK)
            ->response($attachment->stored_path, $attachment->original_name, [
                'Content-Type' => (string) $attachment->mime_type,
            ]);
    }

    public function destroy(string $module, Matrix $matrix, MatrixSubmissionAttachment $attachment): RedirectResponse
    {
        $this->authorizeBoard($module);

        $submission = $attachment->submission;

        abort_unless($submission !== null && (int) $submission->matrix_id === (int) $matrix->id, 404);

        $this->authorizeMatrix('updateSubmission', $matrix, $submission);

        $file = $attachment->original_name;
        $this->attachments->deleteAttachment($attachment);
        $this->audit->submission('delete', 'matrix_attachment_deleted', $submission, [
            'attachment_id' => $attachment->id,
            'file' => $file,
        ]);

        return back()->with('status', 'Adjunto eliminado.');
    }

    private function authorizeAttachment(Matrix $matrix, MatrixSubmissionAttachment $attachment): MatrixSubmission
    {
        $submission = $attachment->submission;

        abort_unless($submission !== null && (int) $submission->matrix_id === (int) $matrix->id, 404);
        abort_unless($this->attachments->fileExists($attachment), 404);

        $this->authorizeMatrix('viewSubmission', $matrix, $submission);

        return $submission;
    }
}
