<?php

namespace Tests\Feature\Gopanel;

use App\Services\Gopanel\Translations\TranslationExcelService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Feature\Gopanel\Concerns\BootsPanelSqlite;
use Tests\TestCase;

/**
 * UI tərcümələri: Excel ixrac ↔ idxal (eyni format) və `translations:seed-site`.
 *
 * Yaddaşda SQLite işlədilir. `Translation::regenerateFiles()` resources/lang
 * fayllarını yazdığı üçün həmin fayllar test əvvəli saxlanılıb sonda qaytarılır.
 */
class TranslationExcelTest extends TestCase
{
    use BootsPanelSqlite;

    /** @var array<string, string|null> */
    private array $langBackup = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootPanelSqlite();
        (require base_path('database/migrations/2025_03_25_172656_create_translations_table.php'))->up();
        DB::table('languages')->insert([
            ['code' => 'en', 'name' => 'English', 'default' => false, 'sort_order' => 2],
        ]);

        foreach (['az', 'en', 'ru'] as $locale) {
            foreach ([resource_path("lang/{$locale}/website.php"), resource_path("lang-json/{$locale}/website.json")] as $path) {
                $this->langBackup[$path] = is_file($path) ? file_get_contents($path) : null;
            }
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->langBackup as $path => $content) {
            if ($content === null) {
                @unlink($path);
            } else {
                file_put_contents($path, $content);
            }
        }

        parent::tearDown();
    }

    public function test_seed_site_command_adds_only_missing_rows_and_is_idempotent(): void
    {
        $this->artisan('translations:seed-site', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(0, DB::table('translations')->count(), 'dry-run bazaya yazmamalıdır');

        $this->artisan('translations:seed-site')->assertSuccessful();
        $count = DB::table('translations')->count();
        $this->assertGreaterThan(0, $count);

        // Admin mətni dəyişib - təkrar işlədəndə əzilməməlidir
        DB::table('translations')->where(['page' => 'general', 'key' => 'home', 'locale' => 'az'])->update(['value' => 'Baş səhifə']);

        $this->artisan('translations:seed-site')->assertSuccessful();
        $this->assertSame($count, DB::table('translations')->count(), 'dublikat yaranmamalıdır');
        $this->assertSame('Baş səhifə', DB::table('translations')->where(['key' => 'home', 'locale' => 'az'])->value('value'));

        $this->artisan('translations:seed-site', ['--force' => true])->assertSuccessful();
        $this->assertSame('Ana səhifə', DB::table('translations')->where(['key' => 'home', 'locale' => 'az'])->value('value'));
    }

    public function test_seed_site_rejects_unknown_page_and_dotted_key(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'tr') . '.json';
        file_put_contents($file, json_encode([
            ['page' => 'yoxdur', 'group' => 'title', 'key' => 'x', 'values' => ['az' => 'X']],
            ['page' => 'general', 'group' => 'title', 'key' => 'a.b', 'values' => ['az' => 'X']],
            ['page' => 'general', 'group' => 'title', 'key' => 'ok_key', 'values' => ['az' => 'Oldu']],
        ]));

        $this->artisan('translations:seed-site', ['--file' => $file])->assertFailed();

        // Düzgün sətir yenə də yazılır, səhvlər yox
        $this->assertSame(['ok_key'], DB::table('translations')->distinct()->pluck('key')->all());
        @unlink($file);
    }

    public function test_excel_export_has_shared_header_and_one_row_per_key(): void
    {
        $this->seedRow('general', 'title', 'home', ['az' => 'Ana səhifə', 'en' => 'Home']);

        $sheet = app(TranslationExcelService::class)->export('website')->getSheet(0)->toArray();

        $this->assertSame(['platform', 'page', 'group', 'key', 'az', 'en'], $sheet[0]);
        $this->assertSame(['website', 'general', 'title', 'home', 'Ana səhifə', 'Home'], $sheet[1]);
        $this->assertCount(2, $sheet);
    }

    public function test_exported_file_imports_back_unchanged_round_trip(): void
    {
        $this->seedRow('general', 'title', 'home', ['az' => 'Ana səhifə', 'en' => 'Home']);
        $this->seedRow('contact', 'content', 'form_intro', ['az' => 'Uzun mətn', 'en' => 'Long text']);

        $file = $this->exportToUpload();
        DB::table('translations')->delete();

        $result = app(TranslationExcelService::class)->import($file, false);

        $this->assertSame(2, $result['total']);
        $this->assertSame(4, $result['created']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame('Long text', DB::table('translations')->where(['key' => 'form_intro', 'locale' => 'en'])->value('value'));
    }

    public function test_import_modes_missing_vs_overwrite_and_blank_cells(): void
    {
        $this->seedRow('general', 'title', 'home', ['az' => 'Köhnə', 'en' => 'Old']);

        $file = $this->makeUpload([
            ['platform', 'page', 'group', 'key', 'az', 'en'],
            ['website', 'general', 'title', 'home', 'Yeni', ''],
        ]);

        $missing = app(TranslationExcelService::class)->import($file, false);
        $this->assertSame(1, $missing['skipped']);
        $this->assertSame('Köhnə', $this->value('home', 'az'));

        $overwrite = app(TranslationExcelService::class)->import($file, true);
        $this->assertSame(1, $overwrite['updated']);
        $this->assertSame('Yeni', $this->value('home', 'az'));
        $this->assertSame('Old', $this->value('home', 'en'), 'boş xana dili silməməlidir');
    }

    public function test_import_restores_soft_deleted_rows(): void
    {
        $this->seedRow('general', 'title', 'home', ['az' => 'Köhnə']);
        DB::table('translations')->update(['deleted_at' => now()]);

        $file = $this->makeUpload([
            ['platform', 'page', 'group', 'key', 'az'],
            ['website', 'general', 'title', 'home', 'Bərpa'],
        ]);

        $result = app(TranslationExcelService::class)->import($file, false);

        $this->assertSame(1, $result['updated']);
        $this->assertSame(1, DB::table('translations')->count(), 'unikal indeks - yeni sətir yaranmamalıdır');
        $this->assertNull(DB::table('translations')->value('deleted_at'));
    }

    public function test_import_reports_invalid_and_duplicate_rows_with_line_numbers(): void
    {
        $file = $this->makeUpload([
            ['key', 'page', 'platform', 'az'],               // sıra fərqli ola bilər - adlar vacibdir
            ['ok', 'general', 'website', 'Oldu'],
            ['ok', 'general', 'website', 'Təkrar'],
            ['x', 'yoxdur', 'website', 'X'],
            ['a.b', 'general', 'website', 'X'],
            ['bos', 'general', 'website', ''],
        ]);

        $result = app(TranslationExcelService::class)->import($file, false);

        $this->assertSame(1, $result['created']);
        $this->assertSame(4, $result['failed']);
        $this->assertStringStartsWith('Sətir 3:', $result['errors'][0]);
        $this->assertNull(DB::table('translations')->where('key', 'ok')->value('group'));
    }

    public function test_import_rejects_file_without_required_columns(): void
    {
        $this->expectException(\RuntimeException::class);

        app(TranslationExcelService::class)->import($this->makeUpload([['key', 'value'], ['a', 'b']]), false);
    }

    public function test_sample_file_uses_the_same_format(): void
    {
        $header = IOFactory::load(public_path('example-files/translations-example.xlsx'))->getSheet(0)->toArray()[0];

        $this->assertSame(array_merge(TranslationExcelService::BASE_COLUMNS, ['az', 'en', 'ru']), $header);
    }

    public function test_routes_require_permissions(): void
    {
        $admin = $this->makePanelAdmin('tr@test.az');

        $this->actingAs($admin, 'gopanel')
            ->get(route('gopanel.settings.translations.excel.export'))
            ->assertForbidden();

        $this->actingAs($admin, 'gopanel')
            ->post(route('gopanel.settings.translations.excel.import'), ['mode' => 'missing'])
            ->assertForbidden();
    }

    public function test_export_route_downloads_xlsx_for_permitted_admin(): void
    {
        $admin = $this->makePanelAdmin('tr2@test.az', ['gopanel.settings.translations.export']);
        $this->seedRow('general', 'title', 'home', ['az' => 'Ana səhifə']);

        $response = $this->actingAs($admin, 'gopanel')->get(route('gopanel.settings.translations.excel.export', ['platform' => 'website']));

        $response->assertOk();
        $this->assertStringContainsString('translations-website-', $response->headers->get('content-disposition'));
    }

    /* ----------------------------------------------------------------- */

    private function seedRow(string $page, ?string $group, string $key, array $values): void
    {
        foreach ($values as $locale => $value) {
            DB::table('translations')->insert([
                'platform' => 'website', 'filename' => 'website', 'page' => $page, 'group' => $group,
                'key' => $key, 'locale' => $locale, 'value' => $value, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function value(string $key, string $locale): ?string
    {
        return DB::table('translations')->where(['key' => $key, 'locale' => $locale])->value('value');
    }

    private function exportToUpload(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'tr') . '.xlsx';
        (new Xlsx(app(TranslationExcelService::class)->export()))->save($path);

        return new UploadedFile($path, 'translations.xlsx', null, null, true);
    }

    /** @param  array<int, array<int, string>>  $rows */
    private function makeUpload(array $rows): UploadedFile
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1', true);
        $path = tempnam(sys_get_temp_dir(), 'tr') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'translations.xlsx', null, null, true);
    }
}
