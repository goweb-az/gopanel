<?php

namespace Tests\Unit;

use App\Models\Site\AboutUs;
use App\Models\Site\Service;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Tests\TestCase;

class SiteContentModulesTest extends TestCase
{
    public function test_about_us_model_is_translation_and_metadata_ready(): void
    {
        $aboutUs = new AboutUs();

        $this->assertSame('about_us', $aboutUs->getTable());
        $this->assertSame(['title', 'description'], $aboutUs->translatedAttributes);
        $this->assertContains('image', $aboutUs->getFillable());
        $this->assertInstanceOf(MorphOne::class, $aboutUs->meta());
        $this->assertInstanceOf(MorphMany::class, $aboutUs->metaAll());
    }

    public function test_service_model_is_crud_ready(): void
    {
        $service = new Service();

        $this->assertSame('services', $service->getTable());
        $this->assertSame(['title', 'short_description', 'description'], $service->translatedAttributes);
        $this->assertContains('sort_order', $service->getFillable());
        $this->assertContains('icon_type', $service->getFillable());
        $this->assertContains('icon', $service->getFillable());
        $this->assertContains('image', $service->getFillable());
        $this->assertInstanceOf(MorphOne::class, $service->meta());
    }

    public function test_permissions_and_sidebar_items_are_registered(): void
    {
        $permissions = config('gopanel.permission_list.gopanel');

        $this->assertArrayHasKey('Haqqımızda', $permissions);
        $this->assertArrayHasKey('Xidmətlər', $permissions);

        $aboutNames = collect($permissions['Haqqımızda'])->pluck('name')->all();
        $serviceNames = collect($permissions['Xidmətlər'])->pluck('name')->all();

        $this->assertContains('gopanel.about-us.index', $aboutNames);
        $this->assertContains('gopanel.about-us.edit', $aboutNames);
        $this->assertContains('gopanel.services.index', $serviceNames);
        $this->assertContains('gopanel.services.add', $serviceNames);
        $this->assertContains('gopanel.services.edit', $serviceNames);
        $this->assertContains('gopanel.services.delete', $serviceNames);
        $this->assertContains('gopanel.services.sort', $serviceNames);

        $sidebar = collect(config('gopanel.sidebar_menu_list'));
        $this->assertTrue($sidebar->contains(fn ($item) => ($item['route'] ?? null) === 'gopanel.about-us.index'));
        $this->assertTrue($sidebar->contains(fn ($item) => ($item['route'] ?? null) === 'gopanel.services.index'));
    }

    public function test_views_use_sortable_table_metadata_and_icon_picker(): void
    {
        $aboutView = file_get_contents(base_path('resources/views/gopanel/pages/about_us/index.blade.php'));
        $serviceView = file_get_contents(base_path('resources/views/gopanel/pages/services/index.blade.php'));
        $serviceForm = file_get_contents(base_path('resources/views/gopanel/pages/services/partials/form.blade.php'));

        $this->assertStringContainsString('gopanel.component.meta', $aboutView);
        $this->assertStringContainsString('class="sortable"', $serviceView);
        $this->assertStringContainsString('gopanel.general.sortable', $serviceView);
        $this->assertStringNotContainsString('<th style="width:80px;">Sıra</th>', $serviceView);
        $this->assertStringContainsString('short_description', $serviceForm);
        $this->assertStringContainsString('class="form-control ckeditor"', $serviceForm);
        $this->assertStringContainsString('gopanel.component.meta', $serviceForm);
        $this->assertStringContainsString('data-icon-picker-target', $serviceForm);
    }

    public function test_mock_seeders_use_local_placeholder_images(): void
    {
        $helper = file_get_contents(base_path('database/seeders/mock/Concerns/CreatesPlaceholderImages.php'));
        $blogSeeder = file_get_contents(base_path('database/seeders/mock/BlogSeeder.php'));
        $serviceSeeder = file_get_contents(base_path('database/seeders/mock/ServiceSeeder.php'));
        $aboutSeeder = file_get_contents(base_path('database/seeders/mock/AboutUsSeeder.php'));
        $categorySeeder = file_get_contents(base_path('database/seeders/mock/CategorySeeder.php'));

        $this->assertStringContainsString('https://placehold.co/', $helper);
        $this->assertStringContainsString('placeholderImage', $blogSeeder);
        $this->assertStringContainsString('placeholderImage', $serviceSeeder);
        $this->assertStringContainsString('placeholderImage', $aboutSeeder);
        $this->assertStringContainsString('placeholderImage', $categorySeeder);
        $this->assertStringNotContainsString("'image'     => ''", $blogSeeder);
        $this->assertStringNotContainsString("'image' => ''", $serviceSeeder);
        $this->assertStringNotContainsString("'image' => ''", $aboutSeeder);
    }
}
