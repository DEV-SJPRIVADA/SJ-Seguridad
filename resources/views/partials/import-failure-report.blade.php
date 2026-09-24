{{-- Compat: preferir <x-import-result-modal>. --}}
@props(['importResult' => null, 'downloadRoute' => null])

<x-import-result-modal
    :import-result="$importResult"
    :download-route="$downloadRoute"
/>
