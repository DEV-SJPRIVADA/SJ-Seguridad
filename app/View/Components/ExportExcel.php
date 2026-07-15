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

    public function __construct(string $route, string $label = 'Exportar a Excel', string $class = 'btn btn--secondary btn--sm')
    {
        $this->route = $route;
        $this->label = $label;
        $this->class = $class;
    }

    public function render(): View|Closure|string
    {
        return view('components.export-excel');
    }
}
