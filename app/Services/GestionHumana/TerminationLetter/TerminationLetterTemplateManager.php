<?php

namespace App\Services\GestionHumana\TerminationLetter;

use App\Models\TerminationLetterDocumentTemplate;
use App\Models\WordDocumentType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TerminationLetterTemplateManager
{
    /**
     * @return list<TerminationLetterDocumentTemplate>
     */
    public function templatesForTypeCode(string $typeCode): array
    {
        return TerminationLetterDocumentTemplate::query()
            ->forTypeCode($typeCode)
            ->ordered()
            ->get()
            ->all();
    }

    /**
     * @return list<TerminationLetterDocumentTemplate>
     */
    public function templatesForType(WordDocumentType|int $type): array
    {
        $typeId = $type instanceof WordDocumentType ? $type->id : $type;

        return TerminationLetterDocumentTemplate::query()
            ->where('word_document_type_id', $typeId)
            ->ordered()
            ->get()
            ->all();
    }

    public function createTemplate(
        WordDocumentType $type,
        string $label,
        UploadedFile $file,
        int $sortOrder = 0,
    ): TerminationLetterDocumentTemplate {
        $template = TerminationLetterDocumentTemplate::query()->create([
            'word_document_type_id' => $type->id,
            'label' => $label,
            'sort_order' => $sortOrder,
            'template_path' => null,
        ]);

        return $this->storeUploadedTemplate($template, $file);
    }

    public function storeUploadedTemplate(
        TerminationLetterDocumentTemplate $template,
        UploadedFile $file,
    ): TerminationLetterDocumentTemplate {
        $this->deleteStoredFile($template);

        $typeId = (int) $template->word_document_type_id;
        $extension = strtolower($file->getClientOriginalExtension() ?: 'docx');
        $storedName = Str::uuid()->toString().'.'.$extension;
        $relativePath = $file->storeAs(
            'ficha-empleados/letter-templates/'.$typeId,
            $storedName,
            'local',
        );

        $template->update([
            'template_path' => $relativePath,
        ]);

        return $template->fresh(['type']) ?? $template;
    }

    public function destroyTemplate(TerminationLetterDocumentTemplate $template): void
    {
        $this->deleteStoredFile($template);
        $template->delete();
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
