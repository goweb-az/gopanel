<?php

namespace Tests\Unit;

use App\Models\Navigation\Category;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class CategoryModuleTest extends TestCase
{
    public function test_category_model_configuration_is_ready_for_gopanel(): void
    {
        $category = new Category();

        $this->assertSame(['name', 'description', 'slug'], $category->translatedAttributes);
        $this->assertSame('name', $category->slug_key);
        $this->assertSame('categories', $category->getTable());

        $this->assertContains('parent_id', $category->getFillable());
        $this->assertContains('icon', $category->getFillable());
        $this->assertContains('sort_order', $category->getFillable());
        $this->assertContains('show_in_menu', $category->getFillable());

        $this->assertInstanceOf(BelongsTo::class, $category->parent());
        $this->assertInstanceOf(HasMany::class, $category->children());
    }

    public function test_category_javascript_was_moved_out_of_blade(): void
    {
        $blade = file_get_contents(base_path('resources/views/gopanel/pages/categories/index.blade.php'));
        $script = file_get_contents(base_path('public/assets/gopanel/js/modules/categories.js'));

        $this->assertStringNotContainsString('$("#parent-sortable").sortable', $blade);
        $this->assertStringNotContainsString('$.ajax({', $blade);

        $this->assertStringContainsString("$('#parent-sortable').sortable", $script);
        $this->assertStringContainsString("data-move-url", $script);
    }

    public function test_category_controller_no_longer_uses_legacy_upload_helper(): void
    {
        $controller = file_get_contents(base_path('app/Http/Controllers/Gopanel/CategoryController.php'));

        $this->assertStringNotContainsString('file_name_genarte', $controller);
        $this->assertStringNotContainsString('gopanelHelper->upload', $controller);
        $this->assertStringContainsString("except(['_token', 'icon_file', 'icon_image', 'meta'])", $controller);
    }

    public function test_category_page_uses_toggle_buttons_for_status(): void
    {
        $blade = file_get_contents(base_path('resources/views/gopanel/pages/categories/index.blade.php'));

        $this->assertStringContainsString("app('gopanel')->toggle_btn", $blade);
        $this->assertStringNotContainsString("app('gopanel')->is_active_btn", $blade);
    }

    public function test_category_form_and_seeder_include_metadata(): void
    {
        $form = file_get_contents(base_path('resources/views/gopanel/pages/categories/partials/form.blade.php'));
        $seeder = file_get_contents(base_path('database/seeders/mock/CategorySeeder.php'));
        $mainJs = file_get_contents(base_path('public/assets/gopanel/js/main.js'));

        $this->assertStringContainsString('enctype="multipart/form-data"', $form);
        $this->assertStringContainsString('gopanel.component.meta', $form);
        $this->assertStringContainsString('$open ?? true', file_get_contents(base_path('resources/views/gopanel/component/meta.blade.php')));
        $this->assertStringContainsString('PageMetaData::updateOrCreate', $seeder);
        $this->assertStringContainsString("'source' => \$category->getTable()", $seeder);
        $this->assertStringContainsString('function initFormUiElements', $mainJs);
        $this->assertStringContainsString('MutationObserver', $mainJs);
    }

    public function test_category_permissions_and_sidebar_menu_are_registered(): void
    {
        $permissions = config('gopanel.permission_list.gopanel.Kateqoriyalar');
        $names = collect($permissions)->pluck('name')->all();

        $this->assertContains('gopanel.categories.index', $names);
        $this->assertContains('gopanel.categories.add', $names);
        $this->assertContains('gopanel.categories.edit', $names);
        $this->assertContains('gopanel.categories.delete', $names);
        $this->assertContains('gopanel.categories.sort', $names);
        $this->assertContains('gopanel.categories.move', $names);

        $sidebar = collect(config('gopanel.sidebar_menu_list'));
        $this->assertTrue($sidebar->contains(fn ($item) => ($item['route'] ?? null) === 'gopanel.categories.index'));
    }
}
