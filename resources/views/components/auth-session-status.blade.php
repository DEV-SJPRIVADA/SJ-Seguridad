@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'auth-message']) }}>
        {{ $status }}
    </div>
@endif
