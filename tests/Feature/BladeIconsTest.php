<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class BladeIconsTest extends TestCase
{
    public function test_lucide_icons_render_as_svg(): void
    {
        $html = Blade::render('<x-lucide-search width="16" height="16" aria-hidden="true" />');

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('width="16"', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    public function test_lucide_filter_x_and_close_icons_render(): void
    {
        $this->assertStringContainsString('<svg', Blade::render('<x-lucide-filter-x />'));
        $this->assertStringContainsString('<svg', Blade::render('<x-lucide-x />'));
    }

    public function test_local_remix_icons_render_through_blade_icons(): void
    {
        $pending = Blade::render('<x-ri-pass-pending-fill width="24" height="24" />');
        $reopen = Blade::render('<x-ri-issues-reopen-fill width="18" height="18" />');

        $this->assertStringContainsString('<svg', $pending);
        $this->assertStringContainsString('<svg', $reopen);
    }

    public function test_selfhst_excel_icon_renders(): void
    {
        $html = Blade::render('<x-selfhst-microsoft-excel-2013 width="16" height="16" />');

        $this->assertStringContainsString('<svg', $html);
    }
}
