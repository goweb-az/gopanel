<?php

namespace Tests\Unit;

use App\Models\Navigation\Menu;
use App\Services\Gopanel\Menus\MenuTreeService;
use Tests\TestCase;

class LanguageMenuModuleTest extends TestCase
{
    // --- Languages ----------------------------------------------------------

    public function test_language_sort_permission_registered(): void
    {
        $names = collect(config('gopanel.permission_list.gopanel.Dillər'))->pluck('name')->all();
        $this->assertContains('gopanel.settings.languages.sort', $names);
    }

    public function test_language_index_uses_dedicated_sort_endpoint_not_generic(): void
    {
        $blade = file_get_contents(base_path('resources/views/gopanel/pages/settings/languages/index.blade.php'));

        $this->assertStringContainsString("route('gopanel.settings.languages.sort')", $blade);
        $this->assertStringContainsString('language-sortable', $blade);
        // The generic model/column sortable endpoint must no longer drive this table.
        $this->assertStringNotContainsString("data-url=\"{{ route('gopanel.general.sortable') }}\"", $blade);
    }

    public function test_language_sort_route_and_js_exist(): void
    {
        $routes = file_get_contents(base_path('routes/gopanel.php'));
        $this->assertStringContainsString('can:gopanel.settings.languages.sort', $routes);

        $js = file_get_contents(base_path('public/assets/gopanel/js/modules/languages.js'));
        $this->assertStringContainsString('data-sort-url', $js);
        $this->assertStringContainsString('sortable', $js);
    }

    // --- Menus --------------------------------------------------------------

    public function test_menu_move_and_sort_permissions_registered(): void
    {
        $names = collect(config('gopanel.permission_list.gopanel.Menyu'))->pluck('name')->all();
        $this->assertContains('gopanel.settings.menu.move', $names);
        $this->assertContains('gopanel.settings.menu.sort', $names);
    }

    public function test_menu_model_has_admin_children_relation(): void
    {
        $this->assertTrue(method_exists(new Menu, 'childrenAdmin'));
    }

    public function test_menu_tree_service_reports_configured_max_depth(): void
    {
        $service = app(MenuTreeService::class);
        $this->assertSame((int) config('gopanel.menu.max_depth', 4), $service->maxDepth());
    }

    public function test_menu_index_uses_dedicated_sort_endpoint_not_generic(): void
    {
        $blade = file_get_contents(base_path('resources/views/gopanel/pages/settings/menu/index.blade.php'));

        $this->assertStringContainsString("route('gopanel.settings.menu.sort')", $blade);
        $this->assertStringContainsString('menu-sortable', $blade);
        // Drill-down into child menus is part of this design.
        $this->assertStringContainsString('Alt Menyular [', $blade);
        // The generic model/column sortable endpoint must not drive the table.
        $this->assertStringNotContainsString('gopanel.general.sortable', $blade);
    }

    public function test_menu_sort_route_and_js_exist(): void
    {
        $routes = file_get_contents(base_path('routes/gopanel.php'));
        $this->assertStringContainsString('can:gopanel.settings.menu.sort', $routes);
        // The transactional move endpoint is still wired for programmatic use.
        $this->assertStringContainsString('can:gopanel.settings.menu.move', $routes);

        $js = file_get_contents(base_path('public/assets/gopanel/js/modules/menus.js'));
        $this->assertStringContainsString('data-sort-url', $js);
        $this->assertStringContainsString('sortable', $js);
    }
}
