<?php

namespace App\Traits;

use Illuminate\Support\Collection;

trait HasQualityDocumentTabs
{
    protected function getQualityDocumentSubTabs(string $module): Collection
    {
        $user = auth()->user();
        $tabs = $user->qualityDocumentBoardTabsFor($module);
        $routeName = request()->route()?->getName();

        return $tabs->map(function (string $tab) use ($module, $routeName) {
            $targetRoute = match ($tab) {
                'biblioteca' => 'quality-documents.library.index',
                'mis_documentos' => 'quality-documents.mine.index',
                'administrar' => 'quality-documents.admin.index',
                default => 'quality-documents.library.index',
            };

            $active = match ($tab) {
                'biblioteca' => str_starts_with((string) $routeName, 'quality-documents.library.'),
                'mis_documentos' => str_starts_with((string) $routeName, 'quality-documents.mine.'),
                'administrar' => str_starts_with((string) $routeName, 'quality-documents.admin.'),
                default => false,
            };

            return [
                'label' => config("access.quality_document_tabs.{$tab}", ucfirst($tab)),
                'url' => route($targetRoute, ['module' => $module]),
                'active' => $active,
            ];
        });
    }
}
