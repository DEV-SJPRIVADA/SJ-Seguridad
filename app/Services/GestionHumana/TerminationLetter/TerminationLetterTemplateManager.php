<?php

namespace App\Services\GestionHumana\TerminationLetter;

use App\Models\TerminationLetterDocumentTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TerminationLetterTemplateManager
{
    /**
     * @return list<TerminationLetterDocumentTemplate>
     */
    public function templatesForCause(string $causeCode): array
    {
        return TerminationLetterDocumentTemplate::query()
            ->forCause($causeCode)
            ->ordered()
            ->get()
            ->all();
    }

    public function findTemplate(string $causeCode, string $documentKey): ?TerminationLetterDocumentTemplate
    {
        return TerminationLetterDocumentTemplate::query()
            ->where('termination_cause_code', $causeCode)
            ->where('document_key', $documentKey)
            ->first();
    }

    public function storeUploadedTemplate(
        TerminationLetterDocumentTemplate $template,
        UploadedFile $file,
    ): TerminationLetterDocumentTemplate {
        $this->deleteStoredFile($template);

        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = Str::uuid()->toString().'.'.$extension;
        $relativePath = $file->storeAs(
            'ficha-empleados/letter-templates/'.$template->termination_cause_code,
            $storedName,
            'local',
        );

        $template->update([
            'template_path' => $relativePath,
        ]);

        return $template->fresh();
    }

    public function deleteTemplateFile(TerminationLetterDocumentTemplate $template): TerminationLetterDocumentTemplate
    {
        $this->deleteStoredFile($template);

        $template->update([
            'template_path' => null,
        ]);

        return $template->fresh();
    }

    public function absolutePath(?string $relativePath): ?string
    {
        if (! filled($relativePath)) {
            return null;
        }

        return Storage::disk('local')->path($relativePath);
    }

    private function deleteStoredFile(TerminationLetterDocumentTemplate $template): void
    {
        if ($template->template_path && Storage::disk('local')->exists($template->template_path)) {
            Storage::disk('local')->delete($template->template_path);
        }
    }
}
