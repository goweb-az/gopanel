<?php

namespace Tests\Feature\Gopanel;

use App\Enums\Gopanel\BackupStatus;
use App\Enums\Gopanel\BackupType;
use App\Models\Activity\Activity;
use App\Models\Activity\FileLog;
use App\Models\Analytics\AnalyticsAdPlatform;
use App\Models\Analytics\AnalyticsBrowser;
use App\Models\Analytics\AnalyticsClick;
use App\Models\Analytics\AnalyticsCountry;
use App\Models\Analytics\AnalyticsDevice;
use App\Models\Analytics\AnalyticsLanguage;
use App\Models\Analytics\AnalyticsOperatingSystem;
use App\Models\Backup\Backup;
use App\Models\Contact\ContactInfo;
use App\Models\Contact\Social;
use App\Models\Geography\Language;
use App\Models\Gopanel\Admin;
use App\Models\Gopanel\CustomRole;
use App\Models\Navigation\Category;
use App\Models\Navigation\Menu;
use App\Models\Seo\SiteRedirect;
use App\Models\Site\Blog;
use App\Models\Site\Product;
use App\Models\Site\Service;
use App\Models\Translations\Translation;
use App\Models\User\User;
use App\Traits\Presentable;
use Tests\TestCase;

/**
 * Modellərin göstəriş accessor-ları Presentable trait-lərinə köçürülüb
 * (docs/rules/presentable-traits.md). Bu test iki şeyi qoruyur:
 *   1. hər model öz trait-ini işlədir (kimsə accessor-u modelə qaytarmasın);
 *   2. köhnə accessor adları eyni nəticəni verir (view/datatable/JS asılıdır).
 *
 * Yalnız yadda saxlanmamış nüsxələr - bazaya heç nə yazılmır.
 */
class PresentableTraitsTest extends TestCase
{
    public function test_each_model_uses_its_presentation_trait(): void
    {
        $map = [
            Activity::class                 => Presentable\Activity\HasActivityPresentation::class,
            FileLog::class                  => Presentable\Activity\HasFileLogPresentation::class,
            Admin::class                    => Presentable\Gopanel\HasAdminPresentation::class,
            CustomRole::class               => Presentable\Gopanel\HasCustomRolePresentation::class,
            Backup::class                   => Presentable\Backup\HasBackupPresentation::class,
            SiteRedirect::class             => Presentable\Seo\HasSiteRedirectPresentation::class,
            Menu::class                     => Presentable\Navigation\HasMenuPresentation::class,
            Category::class                 => Presentable\Navigation\HasCategoryPresentation::class,
            Blog::class                     => Presentable\Site\HasBlogPresentation::class,
            Product::class                  => Presentable\Site\HasProductPresentation::class,
            Service::class                  => Presentable\Site\HasServicePresentation::class,
            Social::class                   => Presentable\Contact\HasSocialPresentation::class,
            ContactInfo::class              => Presentable\Contact\HasContactInfoPresentation::class,
            Language::class                 => Presentable\Geography\HasLanguagePresentation::class,
            Translation::class              => Presentable\Translations\HasTranslationPresentation::class,
            User::class                     => Presentable\User\HasUserPresentation::class,
            AnalyticsClick::class           => Presentable\Analytics\HasAnalyticsClickPresentation::class,
            AnalyticsAdPlatform::class      => Presentable\Analytics\HasAnalyticsAdPlatformPresentation::class,
            AnalyticsBrowser::class         => Presentable\Analytics\HasAnalyticsBrowserPresentation::class,
            AnalyticsCountry::class         => Presentable\Analytics\HasAnalyticsCountryPresentation::class,
            AnalyticsDevice::class          => Presentable\Analytics\HasAnalyticsDevicePresentation::class,
            AnalyticsLanguage::class        => Presentable\Analytics\HasAnalyticsLanguagePresentation::class,
            AnalyticsOperatingSystem::class => Presentable\Analytics\HasAnalyticsOperatingSystemPresentation::class,
        ];

        foreach ($map as $model => $trait) {
            $this->assertContains($trait, class_uses_recursive($model), "{$model} {$trait} trait-ini işlətmir");

            // Accessor modelin özündə yox, trait-dədir.
            foreach ((new \ReflectionClass($trait))->getMethods() as $method) {
                $declaredIn = (new \ReflectionMethod($model, $method->getName()))->getFileName();
                $this->assertSame(
                    (new \ReflectionClass($trait))->getFileName(),
                    $declaredIn,
                    "{$model}::{$method->getName()} modeldə yenidən yazılıb - trait-dəki kölgədə qalır"
                );
            }
        }
    }

    public function test_activity_accessors(): void
    {
        $activity = new Activity();
        $activity->forceFill([
            'event'       => 'created',
            'log_name'    => 'Unknown<b>',
            'description' => str_repeat('a', 70),
            'created_at'  => '2026-10-25 10:20:30',
        ]);

        $this->assertSame('success', $activity->event_color);
        $this->assertSame('success', $activity->event_class);
        $this->assertSame('Yaradıldı', $activity->event_label);
        $this->assertSame(
            '<span class="badge bg-success" style="font-size:13px;padding:5px 10px;">Yaradıldı</span>',
            $activity->event_badge
        );
        $this->assertSame('Unknown<b>', $activity->log_name_title);
        $this->assertStringContainsString('Unknown&lt;b&gt;', $activity->log_name_badge);
        $this->assertSame('-', $activity->causer_name);
        $this->assertStringContainsString(str_repeat('a', 60) . '...', $activity->description_short);
        $this->assertSame('2026-10-25 10:20:30', $activity->created_at_formatted);

        $activity->event = '<script>';
        $this->assertStringNotContainsString('<script>', $activity->event_badge);
    }

    public function test_file_log_accessors(): void
    {
        $log = new FileLog(['level' => 'error', 'message' => 'Xəta baş verdi']);

        $this->assertSame('danger', $log->level_color);
        $this->assertSame('danger', $log->level_class);
        $this->assertSame('ERROR', $log->level_label);
        $this->assertSame('fas fa-exclamation-circle', $log->level_icon);
        $this->assertSame('<span class="badge bg-danger">ERROR</span>', $log->level_badge);
        $this->assertSame('<span title="Xəta baş verdi">Xəta baş verdi</span>', $log->message_short);
        $this->assertSame('-', $log->created_at_formatted);
        $this->assertNull($log->admin_name);
    }

    public function test_admin_and_role_accessors_use_loaded_relations(): void
    {
        $admin = new Admin(['full_name' => 'Test Admin']);
        $admin->setRelation('roles', collect());
        $this->assertSame('<span class="text-muted">Vəzifə verilməyib</span>', $admin->role_summary);

        $admin->setRelation('roles', collect([(object) ['name' => 'Redaktor'], (object) ['name' => 'Moderator']]));
        $this->assertStringContainsString('Redaktor', $admin->role_summary);
        $this->assertStringContainsString('(+1)', $admin->role_summary);

        $this->assertStringStartsWith('https://ui-avatars.com/api/?name=Test+Admin', $admin->avatar_url);

        $role = new CustomRole(['name' => 'Redaktor', 'guard_name' => 'gopanel']);
        $role->setRelation('permissions', collect());
        $role->setRelation('users', collect([new Admin()]));
        $this->assertSame('<span class="text-muted">İcazə verilməyib</span>', $role->permissions_count);
        $this->assertSame('<span class="fw-semibold">1 nəfərə verilib</span>', $role->assigned_admins_count);
    }

    public function test_backup_accessors(): void
    {
        $backup = new Backup([
            'type'        => BackupType::Files->value,
            'status'      => BackupStatus::Completed->value,
            'mode'        => 'incremental',
            'size'        => 0,
            'started_at'  => '2026-10-25 10:00:00',
            'finished_at' => '2026-10-25 10:02:05',
        ]);

        $this->assertSame('2 dəq. 5 san.', $backup->duration());
        $this->assertSame('2 dəq. 5 san.', $backup->duration_formatted);
        $this->assertSame($backup->readableSize(), $backup->size_formatted);
        $this->assertSame('Fayllar', $backup->type_label);
        $this->assertSame('fas fa-images', $backup->type_icon);
        $this->assertSame('Hazır', $backup->status_label);
        $this->assertSame('success', $backup->status_class);
        $this->assertSame('Yalnız yeni fayllar', $backup->mode_label);
        $this->assertSame('Sistem', $backup->author_name);
        $this->assertStringContainsString('2026', (string) $backup->started_at_formatted);

        $this->assertNull((new Backup(['started_at' => '2026-10-25 10:00:00']))->duration());
    }

    public function test_site_redirect_and_menu_accessors(): void
    {
        $redirect = new SiteRedirect(['match_type' => 'prefix']);
        $this->assertSame('Başlanğıcla uyğunluq', $redirect->match_type_name);
        $this->assertSame('Başlanğıcla uyğunluq', $redirect->match_type_label);
        $this->assertSame('info', $redirect->match_type_class);

        $unknown = new SiteRedirect(['match_type' => 'weird']);
        $this->assertSame('weird', $unknown->match_type_label);
        $this->assertSame('secondary', $unknown->match_type_class);

        $menu = new Menu(['type' => 'static', 'position' => 'header']);
        $this->assertNotEmpty($menu->menu_type);
        $this->assertSame($menu->menu_type, $menu->type_label);
        $this->assertSame('secondary', $menu->type_class);
        $this->assertSame($menu->menu_position, $menu->position_label);
        $this->assertSame('success', $menu->position_class);
    }

    public function test_product_blog_and_contact_accessors(): void
    {
        $product = new Product(['price' => 100, 'discount' => 25]);
        // `price_view`/`discount_view` HasFiles::getAttribute() tərəfindən tutulur
        // (`price` $fillable-dadır) - ona görə düz mətn `*_formatted` yoxlanılır.
        $this->assertStringContainsString('75.00 ₼', $product->price_with_discount_view);
        $this->assertSame(75.0, $product->final_price);
        $this->assertSame('75.00 ₼', $product->final_price_formatted);
        $this->assertSame('25.00 ₼', $product->discount_formatted);
        $this->assertNull((new Product(['price' => 10]))->discount_formatted);

        app()->setLocale('az');
        $blog = new Blog(['date_time' => '2026-10-05 12:00:00']);
        $this->assertStringStartsWith('5 ', $blog->formatted_date_time);
        $this->assertStringContainsString('2026', $blog->date_time_formatted);

        $contact = new ContactInfo(['map' => 'https://www.google.com/maps/place/X/@40.4093,49.8671,17z']);
        $this->assertSame('https://www.google.com/maps?q=40.4093,49.8671&hl=az&z=16&output=embed', $contact->map_embed_url);
        $this->assertNull((new ContactInfo())->map_embed_url);

        $category = new Category();
        $this->assertSame('<i class="fas fa-tag text-muted"></i>', $category->icon_view);

        $social = new Social(['icon' => 'fab fa-facebook', 'icon_type' => 'font', 'name' => 'Facebook']);
        $this->assertSame('fab fa-facebook', $social->icon_html);
        $this->assertSame('fab fa-facebook', $social->icon_value);
        $this->assertNotEmpty($social->icon_type_label);
    }

    public function test_language_user_and_analytics_accessors(): void
    {
        $this->assertSame('AZ', (new Language(['code' => 'az']))->upper_code);
        $this->assertSame('Orxan', (new User(['name' => 'Orxan']))->full_name);

        $this->assertSame('<img src="/f.png" width="25" alt="">', (new AnalyticsCountry(['flag' => '/f.png']))->flag_img);
        $this->assertNull((new AnalyticsBrowser())->icon_img);
        $this->assertSame('<i class="fas fa-mobile"></i>', (new AnalyticsDevice(['icon' => 'fa-solid fa-mobile']))->icon_font);

        $click = new AnalyticsClick(['url' => 'https://example.com/' . str_repeat('x', 40), 'ip_address' => '1.2.3.4']);
        $this->assertStringContainsString('...</a>', $click->url_link);
        $this->assertNull($click->referer_link);
        $this->assertSame('<span class="toSearch">1.2.3.4</span>', $click->ip_address_click);
    }
}
