import './bootstrap';

// Livewire (via @livewireScripts al final del layout) ya inicia Alpine con sus plugins.
// No llamar Alpine.start() aqui: rompe @entangle, x-teleport y wire:click en componentes Livewire.
