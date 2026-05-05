@props(['value'])

<label {{ $attributes->merge(['class' => 'form-label']) }}>
    @if(isset($value))
        {!! str_replace('*', '<span class="text-danger" style="color: red;">*</span>', e($value)) !!}
    @else
        {{ $slot }}
    @endif
</label>
