{{--
    Guía lateral reutilizable de formularios y detalles.

    Reemplaza las tarjetas "Antes de enviar", "Qué pasa después", "Resumen", "Al editar", etc.
    que estaban copiadas en cada vista con cuatro familias CSS distintas
    (pur-req-form-*, req-form-*, dev-req-form-*, comercial-form-*).

    Uso con lista simple:
        <x-form-aside-guide
            title="Antes de enviar"
            subtitle="Reduce devoluciones con estos puntos."
            :items="['Punto uno.', 'Punto dos.']"
        />

    Uso con pasos numerados:
        <x-form-aside-guide title="Qué pasa después" numbered :items="['Primero.', 'Segundo.']" />

    Uso con contenido propio (enlaces, negritas, condicionales):
        <x-form-aside-guide title="Resumen">
            <li class="form-aside-guide__item">Estado: {{ $etapa->label() }}</li>
        </x-form-aside-guide>

    El icono del encabezado es opcional: icon="clipboard-check" | "route" | "info" | "list-checks".
--}}
@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'items' => [],
    'numbered' => false,
    'tone' => null,
])

<div {{ $attributes->class(['panel', 'form-aside-guide', $tone ? 'form-aside-guide--' . $tone : null]) }}>
    <div class="panel__header form-aside-guide__head">
        <div class="form-aside-guide__heading">
            @if ($icon)
                {{-- Icono del bloque: identifica de un vistazo si es checklist, flujo o informativo. --}}
                <span class="form-aside-guide__icon" aria-hidden="true">
                    <x-dynamic-component :component="'lucide-' . $icon" width="16" height="16" />
                </span>
            @endif
            <div class="form-aside-guide__titles">
                <h3 class="panel-title form-aside-guide__title">{{ $title }}</h3>
                @if ($subtitle)
                    <p class="panel-text form-aside-guide__subtitle">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="panel__body form-aside-guide__body">
        @if ($numbered)
            <ol class="form-aside-guide__flow">
                @forelse ($items as $item)
                    <li class="form-aside-guide__flow-item">
                        <span class="form-aside-guide__step" aria-hidden="true">{{ $loop->iteration }}</span>
                        <span class="form-aside-guide__text">{{ $item }}</span>
                    </li>
                @empty
                    {{-- Pasos escritos por la vista (con <strong>, enlaces o condicionales). --}}
                    {{ $slot ?? '' }}
                @endforelse
            </ol>
        @elseif (count($items) > 0)
            <ul class="form-aside-guide__list">
                @foreach ($items as $item)
                    <li class="form-aside-guide__item">{{ $item }}</li>
                @endforeach
            </ul>
        @else
            {{-- Contenido libre: la vista pasa sus propios <li> o bloques. --}}
            <ul class="form-aside-guide__list">
                {{ $slot ?? '' }}
            </ul>
        @endif
    </div>
</div>
