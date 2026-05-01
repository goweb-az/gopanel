<?php

namespace Tests\Unit;

use App\Models\Site\Product;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Tests\TestCase;

class ProductModuleTest extends TestCase
{
    public function test_product_model_configuration_is_ready(): void
    {
        $product = new Product();

        $this->assertSame('products', $product->getTable());
        $this->assertSame(['title', 'short_description', 'description', 'slug'], $product->translatedAttributes);
        $this->assertSame('title', $product->slug_key);

        $this->assertContains('uid', $product->getFillable());
        $this->assertContains('price', $product->getFillable());
        $this->assertContains('discount', $product->getFillable());
        $this->assertContains('image', $product->getFillable());
        $this->assertContains('is_active', $product->getFillable());

        $this->assertInstanceOf(MorphOne::class, $product->meta());
        $this->assertInstanceOf(MorphMany::class, $product->metaAll());
    }

    public function test_product_price_with_discount_view_renders_correctly(): void
    {
        $without = new Product(['price' => 100, 'discount' => null]);
        $this->assertStringContainsString('100.00 ₼', $without->price_with_discount_view);
        $this->assertStringNotContainsString('text-decoration-line-through', $without->price_with_discount_view);

        $with = new Product(['price' => 100, 'discount' => 25]);
        $this->assertStringContainsString('text-decoration-line-through', $with->price_with_discount_view);
        $this->assertStringContainsString('75.00 ₼', $with->price_with_discount_view);
    }

    public function test_product_final_price_subtracts_discount(): void
    {
        $a = new Product(['price' => 200, 'discount' => 50]);
        $this->assertSame(150.0, $a->final_price);

        $b = new Product(['price' => 100, 'discount' => null]);
        $this->assertSame(100.0, $b->final_price);

        $c = new Product(['price' => 50, 'discount' => 100]);
        $this->assertSame(0.0, $c->final_price);
    }

    public function test_product_index_uses_datatable_and_form_uses_static_pattern(): void
    {
        $index = file_get_contents(base_path('resources/views/gopanel/pages/products/index.blade.php'));
        $form  = file_get_contents(base_path('resources/views/gopanel/pages/products/partials/form.blade.php'));

        $this->assertStringContainsString("'__datatableName' => 'gopanel.product'", $index);
        $this->assertStringContainsString('id="static-form"', $form);
        $this->assertStringContainsString('class="form-control ckeditor"', $form);
        $this->assertStringContainsString('gopanel.component.meta', $form);
        $this->assertStringContainsString("'open' => true", $form);
    }

    public function test_product_controller_handles_redirect_and_translation(): void
    {
        $controller = file_get_contents(base_path('app/Http/Controllers/Gopanel/ProductController.php'));

        $this->assertStringContainsString('TranslationHelper::create', $controller);
        $this->assertStringContainsString('PageMetaDataHelper::save', $controller);
        $this->assertStringContainsString("route('gopanel.products.index')", $controller);
    }

    public function test_product_datatable_columns_match_expected_layout(): void
    {
        $datatable = file_get_contents(base_path('app/Datatable/Gopanel/ProductDatatable.php'));

        $this->assertStringContainsString("'image_view'", $datatable);
        $this->assertStringContainsString("'title'", $datatable);
        $this->assertStringContainsString("'short_description'", $datatable);
        $this->assertStringContainsString("'price_with_discount_view'", $datatable);
        $this->assertStringContainsString("'is_active_btn'", $datatable);
    }

    public function test_product_permissions_and_sidebar_are_registered(): void
    {
        $names = collect(config('gopanel.permission_list.gopanel.Məhsullar'))->pluck('name')->all();

        $this->assertContains('gopanel.products.index', $names);
        $this->assertContains('gopanel.products.add', $names);
        $this->assertContains('gopanel.products.edit', $names);
        $this->assertContains('gopanel.products.delete', $names);

        $sidebar = collect(config('gopanel.sidebar_menu_list'));
        $this->assertTrue($sidebar->contains(fn ($item) => ($item['route'] ?? null) === 'gopanel.products.index'));
    }

    public function test_product_mock_seeder_uses_placeholder_images(): void
    {
        $seeder = file_get_contents(base_path('database/seeders/mock/ProductSeeder.php'));

        $this->assertStringContainsString('CreatesPlaceholderImages', $seeder);
        $this->assertStringContainsString('placeholderImage', $seeder);
        $this->assertStringContainsString('TranslationHelper::basic', $seeder);
        $this->assertStringContainsString('PageMetaData::updateOrCreate', $seeder);
    }
}
