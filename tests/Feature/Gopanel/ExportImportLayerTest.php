<?php

namespace Tests\Feature\Gopanel;

use App\Exports\Gopanel\Activity\ActivityLogExport;
use App\Exports\Gopanel\ExportDefinition;
use App\Jobs\Export\SendExportMailJob;
use App\Services\Export\ExportContext;
use App\Services\Export\ExportMailService;
use App\Services\Export\Handlers\DefinitionExportHandler;
use App\Services\Mail\Templates\ExportMail;
use App\Support\Export\ExcelExporter;
use App\Support\Export\ExportBranding;
use App\Support\Export\ExportFile;
use App\Support\Export\ExportFilterLabels;
use App\Support\Export\ExportRegistry;
use App\Support\Export\ExportRenderer;
use App\Support\Export\ExportSection;
use App\Support\Export\PdfExporter;
use App\Support\Export\PdfUnavailableException;
use App\Support\Import\FailureBucket;
use App\Support\Import\ImportReport;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Tests\TestCase;

/**
 * Ortaq export/import qatı - bazaya toxunmadan (fixture tərifləri ilə).
 */
class ExportImportLayerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reyestr açarlarında nöqtə var - config() dot-notation onları iç-içə
        // massivə çevirməsin deyə bütün `definitions` bir dəfəyə yazılır.
        config([
            'custom.export.branding.title' => 'Test Brend',
            'custom.export.branding.phone' => '+994 12 000 00 00',
            'custom.export.definitions'    => array_merge((array) config('custom.export.definitions'), [
                'test.items' => [
                    'label'      => 'Test siyahısı',
                    'permission' => 'gopanel.test.items.export',
                    'export'     => ExportTestItemsExport::class,
                ],
                'test.no-mail' => [
                    'label'  => 'Mailsiz',
                    'export' => ExportTestItemsExport::class,
                    'mail'   => false,
                ],
            ]),
        ]);
    }

    /* ── Excel ─────────────────────────────────────────────────────────── */

    public function test_xlsx_is_written_to_disk_with_branding_filters_and_rows(): void
    {
        Storage::fake('exports');

        $rows = (new ExcelExporter())->store(
            new ExportTestItemsExport(['status' => '1', 'search' => 'alma', 'draw' => '3']),
            'exports',
            'reports/items.xlsx',
        );

        $this->assertSame(2, $rows);
        Storage::disk('exports')->assertExists('reports/items.xlsx');

        $sheet = $this->loadSheet(Storage::disk('exports')->path('reports/items.xlsx'));
        $text = $this->sheetText($sheet);

        $this->assertSame('Test Brend', $sheet->getCell('A1')->getValue());
        $this->assertStringContainsString('Tel: +994 12 000 00 00', $text);
        $this->assertStringContainsString('Test məhsulları', $text);
        $this->assertStringContainsString('Status: Aktiv', $text);
        $this->assertStringContainsString('Axtarış: alma', $text);
        $this->assertStringNotContainsString('draw', $text);
        $this->assertStringContainsString('Ad', $text);
        $this->assertStringContainsString('Alma', $text);
        $this->assertStringContainsString('Ümumi', $text);
        $this->assertSame('Test məhsulları', $sheet->getTitle());
    }

    public function test_renderer_returns_excel_file_and_respects_row_limit(): void
    {
        config(['custom.export.max_rows' => 1]);

        $file = (new ExportRenderer())->render(new ExportTestItemsExport(), 'excel');

        $this->assertInstanceOf(ExportFile::class, $file);
        $this->assertSame(ExcelExporter::MIME, $file->mime);
        $this->assertSame('xlsx', $file->extension());
        $this->assertStringStartsWith('test-mehsullari-', $file->name);
        $this->assertSame(1, $file->rowCount);
        $this->assertStringStartsWith('PK', $file->contents, 'xlsx zip arxivi olmalıdır');
    }

    public function test_empty_definition_writes_placeholder_row(): void
    {
        Storage::fake('exports');

        $rows = (new ExcelExporter())->store(new ExportTestEmptyExport(), 'exports', 'empty.xlsx');

        $this->assertSame(0, $rows);
        $text = $this->sheetText($this->loadSheet(Storage::disk('exports')->path('empty.xlsx')));
        $this->assertStringContainsString('Seçilmiş süzgəclərə uyğun məlumat yoxdur.', $text);
    }

    public function test_sheet_title_is_sanitized(): void
    {
        $this->assertSame('a-b-c', ExcelExporter::safeSheetTitle('a/b:c'));
        $this->assertSame(31, mb_strlen(ExcelExporter::safeSheetTitle(str_repeat('ə', 40))));
        $this->assertSame('Export', ExcelExporter::safeSheetTitle('   '));
    }

    /* ── Süzgəc etiketləri ─────────────────────────────────────────────── */

    public function test_filter_labels_skip_technical_keys_and_translate_known_ones(): void
    {
        $described = ExportFilterLabels::describe([
            'draw'      => '1',
            'start'     => '0',
            'format'    => 'pdf',
            'key'       => 'test.items',
            'search'    => 'telefon',
            'date_from' => '2026-01-01',
            'empty'     => '',
            'nothing'   => null,
            'tags'      => ['a', 'b', ['nested']],
            'custom'    => 'x',
        ]);

        $this->assertSame([
            'Axtarış'   => 'telefon',
            'Başlanğıc' => '2026-01-01',
            'tags'      => 'a, b',
            'custom'    => 'x',
        ], $described);
    }

    public function test_filter_labels_accept_config_call_labels_and_value_maps(): void
    {
        config(['custom.export.filter_labels' => ['warehouse_id' => 'Anbar', 'search' => 'Açar söz']]);

        $described = ExportFilterLabels::describe(
            ['warehouse_id' => '5', 'search' => 'x', 'is_active' => true, 'status' => '0'],
            ['search' => 'Sorğu'],
            ['is_active' => [1 => 'Bəli'], 'status' => ['0' => 'Passiv']],
        );

        $this->assertSame('5', $described['Anbar']);
        $this->assertSame('x', $described['Sorğu'], 'çağırışdakı etiket config-i üstələyir');
        $this->assertSame('Bəli', $described['Aktivlik']);
        $this->assertSame('Passiv', $described['Status']);
    }

    public function test_activity_example_describes_filters_without_database(): void
    {
        $export = new ActivityLogExport(['event' => 'deleted', 'date_to' => '2026-10-01', 'length' => '25']);

        $this->assertSame(['Əməliyyat' => 'Silindi', 'Bitmə' => '2026-10-01'], $export->appliedFilters());
        $this->assertCount(6, $export->headings());
        $this->assertSame('emeliyyat-loglari', $export->fileName());
    }

    /* ── PDF (istəyə bağlı) ────────────────────────────────────────────── */

    public function test_pdf_degrades_gracefully_when_dompdf_is_missing(): void
    {
        if (PdfExporter::isAvailable()) {
            $this->assertContains('pdf', ExportRenderer::availableFormats());
            $file = (new ExportRenderer())->render(new ExportTestItemsExport(), 'pdf');
            $this->assertSame(PdfExporter::MIME, $file->mime);

            return;
        }

        $this->assertSame(['excel'], ExportRenderer::availableFormats());

        try {
            (new ExportRenderer())->render(new ExportTestItemsExport(), 'pdf');
            $this->fail('Dompdf olmadan PDF istisna atmalıdır');
        } catch (PdfUnavailableException $e) {
            $this->assertStringContainsString('composer require barryvdh/laravel-dompdf', $e->getMessage());
        }
    }

    public function test_pdf_template_renders_from_view_data(): void
    {
        $data = (new PdfExporter())->viewData(new ExportTestItemsExport(['status' => '1']));
        $html = view('gopanel.exports.pdf', $data)->render();

        $this->assertStringContainsString('Test Brend', $html);
        $this->assertStringContainsString('Test məhsulları', $html);
        $this->assertStringContainsString('Status: Aktiv', $html);
        $this->assertStringContainsString('12 AZN', $html, 'manat işarəsi PDF-də AZN olur');
        $this->assertStringContainsString(ExportBranding::cssColor('accent'), $html);
    }

    public function test_orientation_switches_to_landscape_for_wide_tables(): void
    {
        $this->assertSame('portrait', (new ExportTestItemsExport())->orientation());

        config(['custom.export.pdf.landscape_after_columns' => 2]);
        $this->assertSame('landscape', (new ExportTestItemsExport())->orientation());
    }

    /* ── Reyestr, handler, mail ────────────────────────────────────────── */

    public function test_registry_builds_definitions_only_from_known_keys(): void
    {
        $this->assertInstanceOf(ExportTestItemsExport::class, ExportRegistry::make('test.items', ['status' => '1']));
        $this->assertInstanceOf(ActivityLogExport::class, ExportRegistry::make('activity.activity-logs'));
        $this->assertSame('gopanel.test.items.export', ExportRegistry::permission('test.items'));
        $this->assertNull(ExportRegistry::permission('test.no-mail'));
        $this->assertContains('test.items', ExportRegistry::mailableKeys());
        $this->assertNotContains('test.no-mail', ExportRegistry::mailableKeys());

        $this->expectException(InvalidArgumentException::class);
        ExportRegistry::make(ExportTestItemsExport::class);
    }

    public function test_definition_handler_bridges_export_context_to_result(): void
    {
        Storage::fake('exports');

        $handler = new class () extends DefinitionExportHandler {
            protected function definition(ExportContext $context): ExportDefinition
            {
                return new ExportTestItemsExport($context->params);
            }
        };

        $result = $handler->handle(new ExportContext('test.items', ['status' => '1'], 'exports', 'queued/items.xlsx', 'xlsx'));

        Storage::disk('exports')->assertExists('queued/items.xlsx');
        $this->assertSame('Test məhsulları', $result->title);
        $this->assertSame(2, $result->rowCount);
        $this->assertSame(['Ümumi' => '2'], $result->summary);
    }

    public function test_mail_service_checks_permission_and_queues_scalar_job(): void
    {
        Queue::fake();
        $service = new ExportMailService();

        try {
            $service->queue('test.items', [], 'excel', 'a@b.az', new ExportTestAdmin([]));
            $this->fail('İcazəsiz admin növbəyə iş qoya bilməməlidir');
        } catch (RuntimeException $e) {
            $this->assertSame('Bu sənəd üçün icazəniz yoxdur.', $e->getMessage());
        }

        try {
            $service->queue('test.no-mail', [], 'excel', 'a@b.az', new ExportTestAdmin([]));
            $this->fail('mail => false açarı göndərilməməlidir');
        } catch (RuntimeException $e) {
            $this->assertSame('Bu sənəd mail ilə göndərilə bilmir.', $e->getMessage());
        }

        $label = $service->queue('test.items', ['status' => '1'], 'excel', 'a@b.az', new ExportTestAdmin(['gopanel.test.items.export']), 'Qeyd');

        $this->assertSame('Test siyahısı', $label);
        Queue::assertPushed(SendExportMailJob::class, function (SendExportMailJob $job): bool {
            return $job->key === 'test.items'
                && $job->params === ['status' => '1']
                && $job->format === 'excel'
                && $job->adminId === 7;
        });
    }

    public function test_mail_job_sends_branded_attachment(): void
    {
        Mail::fake();

        (new SendExportMailJob('test.items', ['status' => '1'], 'excel', 'alici@test.az', 7, 'Salam'))
            ->handle(new ExportRenderer());

        Mail::assertSent(ExportMail::class, function (ExportMail $mail): bool {
            $html = $mail->render();

            return $mail->hasTo('alici@test.az')
                && $mail->title === 'Test məhsulları'
                && count($mail->attachments()) === 1
                && $mail->details['Status'] === 'Aktiv'
                && $mail->details['Sətir'] === '2'
                && str_contains($html, 'Salam')
                && str_contains($html, '.xlsx');
        });
    }

    public function test_routes_permission_and_views_are_registered(): void
    {
        $this->assertTrue(Route::has('gopanel.exports.download'));
        $this->assertTrue(Route::has('gopanel.exports.mail'));

        $names = collect(config('gopanel.permission_list.gopanel'))->flatten(1)->pluck('name')->all();
        $this->assertContains(ExportRegistry::permission('activity.activity-logs'), $names);

        $this->assertTrue(view()->exists('gopanel.exports.pdf'));
        $this->assertTrue(view()->exists('gopanel.exports.buttons'));
        $this->assertTrue(view()->exists('emails.export'));
    }

    public function test_buttons_partial_renders_links_for_available_formats_only(): void
    {
        $html = view('gopanel.exports.buttons', ['key' => 'test.items', 'filters' => '#filterPanel'])->render();

        $this->assertStringContainsString('key=test.items', $html);
        $this->assertStringContainsString('data-gp-export-filters="#filterPanel"', $html);
        $this->assertStringContainsString('data-gp-export-mail="test.items"', $html);
        $this->assertSame(PdfExporter::isAvailable(), str_contains($html, 'format=pdf'));

        $noMail = view('gopanel.exports.buttons', ['key' => 'test.no-mail'])->render();
        $this->assertStringNotContainsString('data-gp-export-mail=', $noMail);
    }

    /* ── Import ────────────────────────────────────────────────────────── */

    public function test_import_report_counts_and_serializes(): void
    {
        $report = new ImportReport();
        $report->increment('created');
        $report->increment('created', 4);
        $report->increment('skipped');
        $report->set('source', 'items.xlsx');

        $this->assertSame(5, $report->get('created'));
        $this->assertSame(0, $report->get('missing'));
        $this->assertSame(6, $report->total());
        $this->assertSame('items.xlsx', $report->meta('source'));
        $this->assertSame('x', $report->meta('none', 'x'));

        $array = $report->toArray();
        $this->assertSame(['created' => 5, 'skipped' => 1], $array['counters']);
        $this->assertSame($array['completed_at'], $array['finished_at']);
        $this->assertSame($report->startedAtIso(), $array['started_at']);
        $this->assertGreaterThanOrEqual(0, $array['duration_seconds']);
    }

    public function test_failure_bucket_round_trips_through_json(): void
    {
        $bucket = new FailureBucket();
        $this->assertTrue($bucket->isEmpty());

        $bucket->add(10, 'validate', 'Ad boşdur', ['row' => 3, 'stage' => 'saxta']);
        $bucket->add('SKU-1', 'save', 'Dublikat');
        $bucket->add(10, 'image', 'Şəkil tapılmadı');

        $restored = FailureBucket::fromJson($bucket->toJson());

        $this->assertSame(3, $restored->count());
        $this->assertSame($bucket->all(), $restored->all());
        $this->assertSame([10, 'SKU-1'], $restored->externalIds());
        $this->assertSame(['validate' => 1, 'save' => 1, 'image' => 1], $restored->countByStage());
        $this->assertSame('validate', $restored->all()[0]['stage'], 'kontekst əsas açarı üstələmir');
        $this->assertSame(3, $restored->all()[0]['row']);
        $this->assertStringContainsString('Şəkil tapılmadı', $bucket->toJson(), 'unicode qaçırılmır');

        $this->expectException(\JsonException::class);
        FailureBucket::fromJson('{pozuq');
    }

    /* ── Köməkçilər ────────────────────────────────────────────────────── */

    private function loadSheet(string $path): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        return IOFactory::load($path)->getActiveSheet();
    }

    private function sheetText(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): string
    {
        return collect($sheet->toArray(null, false, false))
            ->flatten()
            ->filter(fn ($value) => $value !== null)
            ->implode(' | ');
    }
}

/** Fixture: iki sətirli siyahı (bazasız). */
final class ExportTestItemsExport extends ExportDefinition
{
    public function __construct(private readonly array $filters = [])
    {
    }

    public function title(): string
    {
        return 'Test məhsulları';
    }

    public function headings(): array
    {
        return ['ID', 'Ad', 'Qiymət'];
    }

    public function numericColumns(): array
    {
        return [0, 2];
    }

    public function rows(): iterable
    {
        yield [1, 'Alma', '12 ₼'];
        yield [2, 'Armud', 0];
    }

    public function summary(): array
    {
        return ['Ümumi' => '2'];
    }

    public function appliedFilters(): array
    {
        return $this->describeFilters($this->filters, [], ['status' => ['1' => 'Aktiv']]);
    }
}

/** Fixture: boş nəticə. */
final class ExportTestEmptyExport extends ExportDefinition
{
    public function title(): string
    {
        return 'Boş';
    }

    public function headings(): array
    {
        return ['A'];
    }
}

/** Fixture: verilən icazələrə malik admin (Gate/baza olmadan). */
final class ExportTestAdmin implements Authenticatable
{
    public function __construct(private readonly array $abilities)
    {
    }

    public function can(string $ability): bool
    {
        return in_array($ability, $this->abilities, true);
    }

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return 7;
    }

    public function getAuthPassword()
    {
        return '';
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }
}
