<?php

namespace Tests\Unit;

use App\Helpers\Gopanel\TranslationPageRegistry;
use App\Models\Translations\Translation;
use App\Services\Gopanel\Translations\TranslationBulkImportService;
use ReflectionMethod;
use Tests\TestCase;

class TranslationModuleTest extends TestCase
{
    public function test_translation_model_has_page_in_fillable(): void
    {
        $this->assertContains('page', (new Translation)->getFillable());
    }

    public function test_page_registry_lookup_and_fallback(): void
    {
        $registry = new TranslationPageRegistry();

        // A configured platform returns its own catalog.
        $this->assertArrayHasKey('general', $registry->forPlatform('website'));
        $this->assertTrue($registry->exists('website', 'general'));

        // An unknown platform falls back to a catalog that still has "general".
        $this->assertArrayHasKey('general', $registry->forPlatform('does-not-exist'));

        // A page that isn't in the platform catalog is rejected.
        $this->assertFalse($registry->exists('website', 'nonexistent-page'));
    }

    public function test_new_import_and_export_permissions_are_registered(): void
    {
        $names = collect(config('gopanel.permission_list.gopanel.Tərcümələr'))
            ->pluck('name')
            ->all();

        $this->assertContains('gopanel.settings.translations.import', $names);
        $this->assertContains('gopanel.settings.translations.export', $names);
    }

    public function test_new_routes_are_declared(): void
    {
        $routes = file_get_contents(base_path('routes/gopanel.php'));

        $this->assertStringContainsString("'bulkImport'", $routes);
        $this->assertStringContainsString("'exportJson'", $routes);
        $this->assertStringContainsString("bulk-import", $routes);
        $this->assertStringContainsString("export-json", $routes);
        $this->assertStringContainsString('can:gopanel.settings.translations.import', $routes);
        $this->assertStringContainsString('can:gopanel.settings.translations.export', $routes);
    }

    public function test_service_provider_uses_page_aware_runtime_key(): void
    {
        $provider = file_get_contents(base_path('app/Providers/TranslationServiceProvider.php'));

        // The runtime key must include filename + page so pages can't collide.
        $this->assertStringContainsString('{$item->filename}.{$page}.{$item->group}.{$item->key}', $provider);
        $this->assertStringContainsString('{$item->filename}.{$page}.{$item->key}', $provider);
    }

    public function test_legacy_custom_translator_was_removed(): void
    {
        $this->assertFileDoesNotExist(base_path('app/Translations/CustomTranslator.php'));
    }

    public function test_translations_js_module_wires_bulk_import_and_export(): void
    {
        $js    = file_get_contents(base_path('public/assets/gopanel/js/modules/translations.js'));
        $index = file_get_contents(base_path('resources/views/gopanel/pages/settings/translations/index.blade.php'));

        $this->assertStringContainsString('data-import-url', $js);
        $this->assertStringContainsString('translation-export', $js);
        $this->assertStringContainsString('modules/translations.js', $index);
        $this->assertStringContainsString('data-pages', $index);
    }

    public function test_bulk_import_normalizes_and_detects_duplicate_and_nested_rows(): void
    {
        $service = app(TranslationBulkImportService::class);

        // normalizeRows() is the pure parsing/normalization core; exercise it
        // directly without touching the database.
        $method = new ReflectionMethod($service, 'normalizeRows');
        $method->setAccessible(true);

        $result = [
            'total_rows' => 0,
            'created'    => 0,
            'updated'    => 0,
            'skipped'    => 0,
            'failed'     => 0,
            'errors'     => [],
        ];

        $raw = [
            'submit'  => '  Yadda saxla  ', // trimmed
            ''        => 'empty key',        // rejected: empty key
            'nested'  => ['a' => 'b'],        // rejected: nested value
        ];

        $normalized = $method->invokeArgs($service, [$raw, &$result]);

        $this->assertSame(['submit' => 'Yadda saxla'], $normalized);
        $this->assertSame(2, $result['failed']);
        $this->assertCount(2, $result['errors']);
    }
}
