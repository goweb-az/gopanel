<?php

namespace Tests\Feature\Gopanel;

use App\Helpers\Gopanel\FileUploader;
use App\Helpers\Gopanel\GoPanelSidebar;
use App\Helpers\Gopanel\TranslationHelper;
use App\Helpers\Gopanel\TranslationPageRegistry;
use App\Models\BaseModel;
use App\Models\Geography\Language;
use App\Models\Site\Blog;
use App\Services\Activity\LogService;
use App\Support\Activity\ActivityEventLabel;
use App\Support\Activity\TranslationChanges;
use App\Support\Url\CdnUrl;
use App\Traits\Ui\FormatsDate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Törəmə layihələrdən (texnotech, aquastores, app.qrgate.loc) köçürülmüş
 * ümumi sinif təkmilləşdirmələri.
 */
class MergedSharedClassesTest extends TestCase
{
    use DatabaseTransactions;

    private string $publicDir;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://site.test', 'app.cdn_url' => null]);
        $this->publicDir = public_path('site/__merged_test');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicDir);
        GoPanelSidebar::getInstance()->clearItems();
        app()->setLocale('az');

        parent::tearDown();
    }

    /* ---------------- CdnUrl ---------------- */

    public function test_protocol_relative_url_is_absolute(): void
    {
        $this->assertTrue(CdnUrl::isAbsolute('//cdn.example.com/a.png'));
        $this->assertSame('//cdn.example.com/a.png', CdnUrl::url('//cdn.example.com/a.png'));
        $this->assertFalse(CdnUrl::isAbsolute('/storage/a.png'));
    }

    /* ---------------- FileUploader ---------------- */

    public function test_normalize_path_strips_known_prefixes_and_own_domains(): void
    {
        config(['app.cdn_url' => 'https://cdn.site.test']);

        $this->assertSame('admins/a.png', FileUploader::normalizePath('/storage/admins/a.png'));
        $this->assertSame('admins/a.png', FileUploader::normalizePath('public/storage/admins/a.png'));
        $this->assertSame('admins/a.png', FileUploader::normalizePath('storage/app/public/admins/a.png'));
        $this->assertSame('admins/a.png', FileUploader::normalizePath('http://site.test/storage/admins/a.png'));
        $this->assertSame('x/y.png', FileUploader::normalizePath('https://cdn.site.test/storage/x//y.png'));
        $this->assertNull(FileUploader::normalizePath(''));
        $this->assertNull(FileUploader::normalizePath('storage/'));
    }

    public function test_storage_and_public_url_do_not_touch_disk(): void
    {
        Storage::fake('public');

        $this->assertStringEndsWith('/storage/admins/a.png', (string) FileUploader::storageUrl('/storage/admins/a.png'));
        $this->assertSame('https://other.com/x.png', FileUploader::storageUrl('https://other.com/x.png'));
        $this->assertNull(FileUploader::storageUrl(null));

        $this->assertSame('http://site.test/site/blogs/a.png', FileUploader::publicUrl('/site/blogs/a.png'));
        $this->assertNull(FileUploader::publicUrl(''));
    }

    public function test_or_fallback_methods_use_given_placeholder(): void
    {
        $this->assertSame('http://site.test/assets/no.png', FileUploader::storageUrlOrFallback(null, 'assets/no.png'));
        $this->assertSame('http://site.test/assets/no.png', FileUploader::publicUrlOrFallback('', 'assets/no.png'));
        $this->assertSame('http://site.test/site/a.png', FileUploader::publicUrlOrFallback('site/a.png', 'assets/no.png'));
        $this->assertSame('https://x.com/n.png', FileUploader::storageUrlOrFallback(null, 'https://x.com/n.png'));
    }

    public function test_url_keeps_existence_check_and_supports_auto_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('admins/a.png', 'x');

        File::ensureDirectoryExists($this->publicDir);
        File::put($this->publicDir . '/p.png', 'x');

        // Mövcud davranış: fayl yoxdursa null
        $this->assertNull(FileUploader::url('site/__merged_test/missing.png'));
        $this->assertNull(FileUploader::url('missing.png', FileUploader::DISK_AUTO));

        $this->assertSame('http://site.test/site/__merged_test/p.png', FileUploader::url('site/__merged_test/p.png'));
        $this->assertStringEndsWith('/storage/admins/a.png', (string) FileUploader::url('admins/a.png', 'storage'));
        $this->assertStringEndsWith('/storage/admins/a.png', (string) FileUploader::url('admins/a.png', 'auto'));
        $this->assertSame('http://site.test/site/__merged_test/p.png', FileUploader::url('site/__merged_test/p.png', 'auto'));

        // Tam ünvan diskdə axtarılmır
        $this->assertSame('https://x.com/a.png', FileUploader::url('https://x.com/a.png'));
    }

    public function test_delete_file_auto_picks_the_right_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('admins/a.png', 'x');

        File::ensureDirectoryExists($this->publicDir);
        File::put($this->publicDir . '/p.png', 'x');

        $this->assertTrue(FileUploader::deleteFile('admins/a.png', 'auto'));
        Storage::disk('public')->assertMissing('admins/a.png');

        $this->assertTrue(FileUploader::deleteFile('site/__merged_test/p.png', 'auto'));
        $this->assertFileDoesNotExist($this->publicDir . '/p.png');
    }

    public function test_file_url_helper_falls_back(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('admins/a.png', 'x');

        $this->assertStringEndsWith('/storage/admins/a.png', (string) file_url('admins/a.png'));
        $this->assertSame('fallback.png', file_url('missing.png', 'fallback.png'));
        $this->assertNull(file_url(null));
    }

    /* ---------------- HasFiles ---------------- */

    private function fileModel(?string $image): BaseModel
    {
        $model = new class extends BaseModel {
            protected $table    = 'blogs';
            protected $fillable = ['image'];
            protected $files    = ['image'];
        };

        $model->setRawAttributes(['image' => $image]);

        return $model;
    }

    public function test_file_url_accessor_returns_full_urls(): void
    {
        Storage::fake('public');
        File::ensureDirectoryExists($this->publicDir);
        File::put($this->publicDir . '/p.png', 'x');

        $this->assertNull($this->fileModel(null)->image_url);
        $this->assertNull($this->fileModel('')->image_url);
        $this->assertSame('https://x.com/a.png', $this->fileModel('https://x.com/a.png')->image_url);
        $this->assertSame('http://site.test/site/__merged_test/p.png', $this->fileModel('site/__merged_test/p.png')->image_url);

        // Diskdə olmayan nisbi yol: əvvəl xam yol qaytarılırdı, indi tam storage ünvanı (yenə string)
        $missing = $this->fileModel('admins/missing.png')->image_url;
        $this->assertIsString($missing);
        $this->assertStringEndsWith('/storage/admins/missing.png', $missing);
    }

    public function test_field_view_shows_placeholder_for_missing_file(): void
    {
        Storage::fake('public');
        File::ensureDirectoryExists($this->publicDir);
        File::put($this->publicDir . '/p.png', 'x');

        $this->assertStringContainsString('Fayl yoxdur', (string) $this->fileModel('admins/missing.png')->image_view);
        $this->assertNull($this->fileModel('admins/missing.png')->getExistingFileUrl('image'));

        $html = (string) $this->fileModel('site/__merged_test/p.png')->image_view;
        $this->assertStringContainsString('<img src="http://site.test/site/__merged_test/p.png"', $html);
    }

    /* ---------------- FormatsDate ---------------- */

    public function test_formats_date_follows_app_locale_and_adds_date_time(): void
    {
        $model = new class {
            use FormatsDate;

            public $created_at = '2026-09-27 01:03:00';
        };

        app()->setLocale('en');
        $this->assertSame('27 September 2026', $model->getCreatedAtFormattedAttribute());
        $this->assertSame('27 September 2026, 01:03', $model->formatDateTime('created_at'));

        app()->setLocale('az');
        $this->assertSame('27 sentyabr 2026', $model->formatDate('created_at'));
        $this->assertNull($model->formatDateTime('missing'));
    }

    /* ---------------- Cacheable ---------------- */

    private function makeInactiveLanguage(): Language
    {
        return Language::create([
            'code'       => 'zq',
            'name'       => 'Test',
            'is_active'  => false,
            'sort_order' => 99,
            'country_id' => Language::query()->value('country_id'),
        ]);
    }

    public function test_saving_a_record_forgets_its_cached_first_key(): void
    {
        $language = $this->makeInactiveLanguage();

        $this->assertSame('Test', Language::getCachedFirst($language->id)->name);

        $language->update(['name' => 'Yeni']);

        $this->assertSame('Yeni', Language::getCachedFirst($language->id)->name);

        $language->delete();
        $this->assertNull(Language::getCachedFirst($language->id));
    }

    /* ---------------- LogService ---------------- */

    public function test_log_service_with_details_is_fluent(): void
    {
        $log = LogService::channel('testing', false);

        $this->assertFalse($log->log_detail, 'Default dəyişməməlidir');
        $this->assertSame($log, $log->withDetails());
        $this->assertTrue($log->log_detail);
        $this->assertFalse($log->withDetails(false)->log_detail);
    }

    /* ---------------- TranslationHelper + TranslationChanges ---------------- */

    public function test_translation_changes_are_returned_and_inactive_language_is_kept(): void
    {
        $active = Language::where('is_active', true)->value('code');
        $this->assertNotNull($active, 'Test bazasında aktiv dil olmalıdır');

        $inactive = $this->makeInactiveLanguage();

        $blog = Blog::create(['is_active' => 1, 'image' => 'assets/test.png', 'date_time' => now()->toDateTimeString()]);
        $blog->translations()->create(['locale' => 'zq', 'key' => 'title', 'value' => 'Saxla']);
        $blog->translations()->create(['locale' => $active, 'key' => 'title', 'value' => 'Köhnə']);

        $changes = TranslationHelper::fromInputWithChanges($blog, ['title' => [$active => 'Yeni']]);

        $this->assertSame(['old' => 'Köhnə', 'new' => 'Yeni'], $changes['title'][$active]);
        $this->assertArrayNotHasKey('zq', $changes['title']);
        $this->assertSame(
            'Saxla',
            $blog->translations()->where('locale', $inactive->code)->where('key', 'title')->value('value'),
            'Söndürülmüş dilin tərcüməsi silinməməlidir'
        );

        // Dəyişməyən dəyər massivə düşmür
        $again = TranslationHelper::fromInputWithChanges($blog, ['title' => [$active => 'Yeni'], 'slug' => [$active => 'yeni']]);
        $this->assertArrayNotHasKey('title', $again);
    }

    public function test_translation_changes_describe(): void
    {
        $text = TranslationChanges::describe([
            'title' => ['az' => ['old' => 'Köhnə', 'new' => '<p>Yeni</p>']],
            'description' => ['en' => ['old' => null, 'new' => str_repeat('a', 100)]],
        ]);

        $this->assertStringContainsString('(az): Köhnə → Yeni', $text);
        $this->assertStringContainsString('(en): - → ' . str_repeat('a', 80) . '…', $text);
        $this->assertSame('-', TranslationChanges::describe([]));
    }

    public function test_activity_event_label(): void
    {
        config(['activitylog.event_names' => ['created' => 'Yaradıldı']]);

        $this->assertSame('Yaradıldı', ActivityEventLabel::for('created'));
        $this->assertSame('Gallery Deleted', ActivityEventLabel::for('gallery_deleted'));
        $this->assertSame('-', ActivityEventLabel::for(null));
        $this->assertSame('success', ActivityEventLabel::color('created'));
        $this->assertSame('danger', ActivityEventLabel::color('deleted'));
        $this->assertSame('secondary', ActivityEventLabel::color('unknown_event'));

        config(['activitylog.event_colors' => ['unknown_event' => 'info']]);
        $this->assertSame('info', ActivityEventLabel::color('unknown_event'));
    }

    /* ---------------- GoPanelSidebar ---------------- */

    public function test_sidebar_skips_missing_routes_and_disabled_flags(): void
    {
        config(['custom.__merged_off' => false]);

        $sidebar = GoPanelSidebar::getInstance();
        $sidebar->clearItems();

        $sidebar->addItems([
            ['title' => 'Başlıq'],
            ['title' => 'Var', 'route' => 'gopanel.index'],
            ['title' => 'Yoxdur', 'route' => 'gopanel.__no_such_route.index'],
            ['title' => 'Söndürülüb', 'route' => 'gopanel.index', 'when' => 'custom.__merged_off'],
            ['title' => 'Qrup', 'inner' => [
                ['title' => 'Alt var', 'route' => 'gopanel.index'],
                ['title' => 'Alt yox', 'route' => 'gopanel.__no_such_route.index'],
            ]],
            ['title' => 'Boş qrup', 'inner' => [
                ['title' => 'Alt yox', 'route' => 'gopanel.__no_such_route.index'],
            ]],
        ]);

        $titles = $sidebar->getItems()->pluck('title')->all();

        $this->assertSame(['Başlıq', 'Var', 'Qrup'], $titles);
        $this->assertSame(['Alt var'], $sidebar->getItems()->last()->get('inner')->pluck('title')->all());
    }

    /* ---------------- TranslationPageRegistry ---------------- */

    public function test_translation_page_registry_helpers(): void
    {
        config(['gopanel.translation_pages' => ['website' => ['general' => 'Ümumi', 'home' => 'Ana səhifə']]]);

        $registry = new TranslationPageRegistry();

        $this->assertSame(['website'], $registry->platforms());
        $this->assertSame(['general', 'home'], $registry->keys('website'));
        $this->assertSame('Ana səhifə', $registry->label('website', 'home'));
        $this->assertSame('Ümumi', $registry->label('website', null));
        $this->assertSame('old_page', $registry->label('website', 'old_page'));

        config(['gopanel.translation_pages' => []]);
        $this->assertSame(TranslationPageRegistry::FALLBACK, $registry->forPlatform('mobile'));
    }

    /* ---------------- BaseModel ---------------- */

    public function test_base_model_has_not_found_message(): void
    {
        $this->assertSame('Blog tapılmadı.', Blog::notFoundMessage());
    }
}
