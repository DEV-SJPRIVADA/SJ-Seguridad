<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ExportExcel extends Component
{
    public string $route;

    public string $label;

    public string $class;

    public function __construct(string $route, string $label = '', string $class = 'btn btn--secondary btn--sm')
    {
        // Callers often pass route="{{ $url }}"; Blade escapes & → &amp; before the prop
        // is set, and href="{{ $route }}" would escape again (&amp;amp;), breaking query strings.
        $this->route = html_entity_decode($route, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $this->label = $label;
        $this->class = $class;
    }

    public function render(): View|Closure|string
    {
        return view('components.export-excel');
    }
}
