@if ($errors->any())
    <div class="panel indicadores-alert indicadores-alert--error">
        <div class="panel__body text-small">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    </div>
@endif

@if (session('status'))
    <div class="panel indicadores-alert indicadores-alert--success">
        <div class="panel__body text-small">{{ session('status') }}</div>
    </div>
@endif
