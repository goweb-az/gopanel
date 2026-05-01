<?php

namespace Tests\Unit;

use App\Models\Site\Slider;
use Tests\TestCase;

class SliderModuleTest extends TestCase
{
    public function test_slider_model_configuration_is_ready(): void
    {
        $slider = new Slider();

        $this->assertSame('sliders', $slider->getTable());
        $this->assertSame(['title', 'description', 'link_title'], $slider->translatedAttributes);

        $this->assertContains('link', $slider->getFillable());
        $this->assertContains('sort_order', $slider->getFillable());
        $this->assertContains('is_active', $slider->getFillable());
        $this->assertContains('image', $slider->getFillable());
    }

    public function test_slider_index_uses_sortable_table_not_datatable(): void
    {
        $index = file_get_contents(base_path('resources/views/gopanel/pages/slider/index.blade.php'));

        $this->assertStringNotContainsString('gopanel.component.datatable', $index);
        $this->assertStringContainsString('class="sortable"', $index);
        $this->assertStringContainsString('gopanel.general.sortable', $index);
        $this->assertStringContainsString('data-row="sort_order"', $index);
        $this->assertStringContainsString("app('gopanel')->toggle_btn", $index);
    }

    public function test_slider_controller_passes_sliders_collection_and_handles_save(): void
    {
        $controller = file_get_contents(base_path('app/Http/Controllers/Gopanel/SliderController.php'));

        $this->assertStringContainsString("Slider::orderBy('sort_order'", $controller);
        $this->assertStringContainsString('compact(', $controller);
        $this->assertStringContainsString('TranslationHelper::create', $controller);
        $this->assertStringContainsString('FileUploader::toPublic', $controller);
    }

    public function test_slider_permissions_include_sort_action(): void
    {
        $names = collect(config('gopanel.permission_list.gopanel.Slayder'))->pluck('name')->all();

        $this->assertContains('gopanel.slider.index', $names);
        $this->assertContains('gopanel.slider.add', $names);
        $this->assertContains('gopanel.slider.edit', $names);
        $this->assertContains('gopanel.slider.delete', $names);
        $this->assertContains('gopanel.slider.sort', $names);

        $sidebar = collect(config('gopanel.sidebar_menu_list'));
        $this->assertTrue($sidebar->contains(fn ($item) => ($item['route'] ?? null) === 'gopanel.slider.index'));
    }

    public function test_slider_mock_seeder_uses_placeholder_images(): void
    {
        $seeder = file_get_contents(base_path('database/seeders/mock/SliderSeeder.php'));

        $this->assertStringContainsString('CreatesPlaceholderImages', $seeder);
        $this->assertStringContainsString('placeholderImage', $seeder);
        $this->assertStringContainsString('TranslationHelper::basic', $seeder);
    }

    public function test_basemodel_uses_extracted_traits(): void
    {
        $baseModel = file_get_contents(base_path('app/Models/BaseModel.php'));

        $this->assertStringContainsString('use HasFiles;', $baseModel);
        $this->assertStringContainsString('use LogsAdminActivity;', $baseModel);
        $this->assertStringContainsString('use Cacheable;', $baseModel);
        $this->assertStringNotContainsString('public function getFileUrl', $baseModel);
        $this->assertStringNotContainsString('public function getFieldView', $baseModel);
        $this->assertStringNotContainsString('public static function getCachedAll', $baseModel);
        $this->assertStringNotContainsString("addGlobalScope('translations'", $baseModel);

        $translation = file_get_contents(base_path('app/Traits/Content/Translation.php'));
        $this->assertStringContainsString("addGlobalScope('translations'", $translation);

        $cacheable = file_get_contents(base_path('app/Traits/System/Cacheable.php'));
        $this->assertStringContainsString('public static function getCachedAll', $cacheable);
        $this->assertStringContainsString('public static function getCachedFirst', $cacheable);
        $this->assertStringContainsString('public static function getCachedForever', $cacheable);
    }
}
