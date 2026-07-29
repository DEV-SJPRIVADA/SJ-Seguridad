@props([
    'value' => null,
    'datetime' => false,
    'empty' => '—',
])

{{ $datetime
    ? \App\Support\DisplayDate::dateTime($value, $empty)
    : \App\Support\DisplayDate::date($value, $empty) }}
