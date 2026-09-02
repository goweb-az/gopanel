<?php

namespace Tests\Feature\Gopanel;

use App\Models\Site\Blog;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Tests\TestCase;

class BlogModuleTest extends TestCase
{
    public function test_blog_model_configuration_is_ready(): void
    {
        $blog = new Blog();

        $this->assertSame('blogs', $blog->getTable());
        $this->assertSame(['title', 'description', 'slug'], $blog->translatedAttributes);
        $this->assertSame('title', $blog->slug_key);

        $this->assertContains('image', $blog->getFillable());
        $this->assertContains('date_time', $blog->getFillable());
        $this->assertContains('is_active', $blog->getFillable());
        $this->assertContains('views', $blog->getFillable());

        $this->assertInstanceOf(MorphOne::class, $blog->meta());
        $this->assertInstanceOf(MorphMany::class, $blog->metaAll());
    }

    public function test_blog_uses_datatable_and_form_includes_metadata(): void
    {
        $index = file_get_contents(base_path('resources/views/gopanel/pages/blog/index.blade.php'));
        $form  = file_get_contents(base_path('resources/views/gopanel/pages/blog/partials/form.blade.php'));

        $this->assertStringContainsString("'__datatableName' => 'gopanel.blog'", $index);
        $this->assertStringContainsString('id="static-form"', $form);
        $this->assertStringContainsString('class="form-control ckeditor"', $form);
        $this->assertStringContainsString('gopanel.component.meta', $form);
    }

    public function test_blog_controller_delegates_saving_to_content_service(): void
    {
        $controller = file_get_contents(base_path('app/Http/Controllers/Gopanel/BlogController.php'));
        $service    = file_get_contents(base_path('app/Services/Gopanel/Content/ContentSaveService.php'));

        // Controller nazikdir: yazma məntiqi burada olmamalıdır
        $this->assertStringContainsString('ContentSaveService', $controller);
        $this->assertStringContainsString('$this->content->save(', $controller);
        $this->assertStringNotContainsString('FileUploader::', $controller);
        $this->assertStringContainsString("redirect'] = isset(\$item->id) ? route(\"gopanel.blog.index\")", $controller);

        // Tərcümə və SEO meta yazılışı ortaq servisdədir
        $this->assertStringContainsString('TranslationHelper::fromInput', $service);
        $this->assertStringContainsString('PageMetaDataHelper::save', $service);
    }

    public function test_blog_save_request_validates_and_declares_image_field(): void
    {
        $request = new \App\Http\Requests\Gopanel\Site\BlogSaveRequest();

        $this->assertArrayHasKey('title', $request->rules());
        $this->assertArrayHasKey('image', $request->rules());

        $fields = $request->fileFields();
        $this->assertCount(1, $fields);
        $this->assertSame('image', $fields[0]->input);
        $this->assertSame('image', $fields[0]->column);
    }

    public function test_blog_permissions_and_sidebar_are_registered(): void
    {
        $names = collect(config('gopanel.permission_list.gopanel.Bloqlar'))->pluck('name')->all();

        $this->assertContains('gopanel.blog.index', $names);
        $this->assertContains('gopanel.blog.add', $names);
        $this->assertContains('gopanel.blog.edit', $names);
        $this->assertContains('gopanel.blog.delete', $names);

        $sidebar = collect(config('gopanel.sidebar_menu_list'));
        $this->assertTrue($sidebar->contains(fn ($item) => ($item['route'] ?? null) === 'gopanel.blog.index'));
    }

    public function test_blog_mock_seeder_uses_placeholder_images(): void
    {
        $seeder = file_get_contents(base_path('database/seeders/mock/BlogSeeder.php'));

        $this->assertStringContainsString('CreatesPlaceholderImages', $seeder);
        $this->assertStringContainsString('placeholderImage', $seeder);
        $this->assertStringContainsString('TranslationHelper::basic', $seeder);
        $this->assertStringContainsString('PageMetaData::updateOrCreate', $seeder);
    }
}
