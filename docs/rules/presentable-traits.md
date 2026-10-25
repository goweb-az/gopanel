# Presentable trait-ləri (modelin göstəriş qatı)

> Mənbə: `app.qrgate.loc` layihəsində 28 trait ilə sınanmış naxış
> (`app/Traits/Presentable/**`). Buraya naxışın özü köçürülüb - o layihənin
> trait-ləri biznesə bağlı olduğu üçün kopyalanmayıb.

## Nə həll edir

Modeldə iki növ kod qarışır: **data** (fillable, cast, relation, scope) və
**göstəriş** (status adı, rəng, formatlanmış tarix/məbləğ). Göstəriş accessor-ları
yığıldıqca model 400 sətrə çatır və «bu sütun bazada var, yoxsa hesablanır?»
sualına cavab tapmaq çətinləşir.

Həll: göstəriş accessor-ları modelə **bir trait** kimi qoşulur, model faylında
yalnız data qalır.

## Harada

```text
app/Traits/Presentable/<Domen>/Has<Model>Presentation.php
```

```php
namespace App\Traits\Presentable\Site;

trait HasBlogPresentation
{
    public function getStatusLabelAttribute(): string
    {
        return BlogStatusEnum::labelOf($this->status);
    }

    public function getStatusClassAttribute(): string
    {
        return BlogStatusEnum::classOf($this->status);   // 'success' | 'secondary'
    }

    public function getPublishedAtFormattedAttribute(): ?string
    {
        return LocalizedDateFormatter::dateTime($this->published_at);
    }
}

class Blog extends BaseModel
{
    use HasBlogPresentation;
}
```

Bir model = bir trait. İki model eyni göstərişi paylaşırsa (məs. iki növ
kampaniya) eyni trait hər ikisinə qoşulur - kopyalanmır.

## Accessor adları (sabit lüğət)

| Sonluq | Nə qaytarır | Nümunə |
|---|---|---|
| `*_label` | Enum/kodun insan oxunaqlı adı | `status_label` → «Dərc edilib» |
| `*_class` | Bootstrap tonu (`success`, `danger`, `secondary`) | `status_class` |
| `*_color` | Hex rəng (qrafik/kart üçün) | `type_color` → `#0d6efd` |
| `*_formatted` | Formatlanmış tarix/məbləğ/ölçü | `created_at_formatted`, `total_formatted` |
| `*_url` | Tam URL (fayl, səhifə) | `image_url`, `single_url` |
| `full_*` | Birləşdirilmiş mətn | `full_name` |

Eyni məna HƏR modeldə eyni sonluqla adlandırılır - blade/datatable yazan adam
`status_label` + `status_class` cütünü hər modeldə tapacağını bilir.

## Qaydalar

1. **Yalnız oxu və formatlama.** Sorğu (`->where()`, `->count()`), yazma,
   event, xarici çağırış YOXDUR. Relation lazımdırsa əvvəlcədən yüklənmiş olanı
   işlədir (`$this->relationLoaded('author')`), özü yükləmir - əks halda
   siyahıda N+1 yaranır.
2. **Formatlama ortaq qatdan gəlir**, trait-də yenidən yazılmır:
   tarix → `App\Support\Date\LocalizedDateFormatter`, ölçü → `ByteSize`,
   müddət → `Duration`, fayl URL → `CdnUrl`, enum adı → enum-un öz `labelOf()`-u.
   `->locale('az')` kimi sabit dil yazılmır.
3. **HTML qaytarılmır** - `*_label` + `*_class` qaytarılır, nişanı blade/
   datatable (`RendersRichCells::badge()`) çəkir. İstisna: datatable hüceyrəsi
   üçün `*_badge` - onda da bütün dəyişən mətn `e()` ilə qaçırılır.
4. **İcazə yoxlaması yoxdur.** «Bu düymə görünsün?» Policy/datatable-ın işidir.
5. API-də model birbaşa qaytarılmır (04-api.md §2) - Resource lazım olan
   `*_label`/`*_formatted` sahələrini açıq şəkildə seçir. Ona görə trait-də
   `$appends` doldurulmur, yalnız həqiqətən hər yerdə lazım olan sahə əlavə olunur.

## Nə vaxt trait yox, başqa şey

| Hal | Yeri |
|---|---|
| Bir neçə modeli birləşdirən hesablama (dashboard kartı) | Query / `StatCard` |
| Təkrarlanan HTML bloku | Blade komponenti |
| Sabit dəyər dəstinin adı/rəngi | Enum metodu (`labelOf`, `classOf`) - trait onu çağırır |
| Bir dəfə lazım olan format | Controller/servis - trait-ə əlavə edilmir |

## Mövcud kod

Gopanel-in öz modellərindəki göstəriş accessor-ları trait-lərə köçürülüb.
Köhnə adlar və nəticələr **dəyişməyib** (view, datatable, JS və testlər
onlardan asılıdır); yanlarına lüğətə uyğun `*_label`/`*_class`/`*_formatted`
əlavə olunub. Qoruyucu test: `tests/Feature/Gopanel/PresentableTraitsTest.php`
(hər model öz trait-ini işlədir və accessor modeldə yenidən yazılmayıb).

| Trait (`App\Traits\Presentable\...`) | Model | Köhnə (köçürülən) | Yeni |
|---|---|---|---|
| `Activity\HasActivityPresentation` | `Activity\Activity` | `event_color`, `event_label`, `event_badge`, `log_name_title`, `log_name_badge`, `causer_name`, `description_short`, `created_at_formatted` | `event_class` |
| `Activity\HasFileLogPresentation` | `Activity\FileLog` | `user_name`, `admin_name`, `level_color`, `level_icon`, `level_badge`, `message_short`, `created_at_formatted` | `level_label`, `level_class` |
| `Gopanel\HasAdminPresentation` | `Gopanel\Admin` | `is_active_btn`, `is_super_btn`, `role_summary`, `permission_summary`, `granted_permissions_count`, `avatar_url` | - |
| `Gopanel\HasCustomRolePresentation` | `Gopanel\CustomRole` | `permissions_count`, `assigned_admins_count` | - |
| `Backup\HasBackupPresentation` | `Backup\Backup` | `readableSize()`, `duration()` (metod) | `size_formatted`, `duration_formatted`, `type_label`, `type_icon`, `status_label`, `status_class`, `mode_label`, `author_name`, `started_at_formatted`, `finished_at_formatted` |
| `Seo\HasSiteRedirectPresentation` | `Seo\SiteRedirect` | `match_type_name`, `is_active_button` | `match_type_label`, `match_type_class` |
| `Navigation\HasMenuPresentation` | `Navigation\Menu` | `menu_type`, `menu_position`, `route` | `type_label`, `type_class`, `position_label`, `position_class` |
| `Navigation\HasCategoryPresentation` | `Navigation\Category` | `icon_view`, `icon_value` | - |
| `Site\HasBlogPresentation` | `Site\Blog` | `short_description`, `short_description_site`, `image_view`, `is_active_btn`, `formatted_date_time`, `single_url` | `date_time_formatted` |
| `Site\HasProductPresentation` | `Site\Product` | `short_description_view`, `image_view`, `price_view`, `discount_view`, `final_price`, `price_with_discount_view`, `is_active_btn` | `discount_formatted`, `final_price_formatted` |
| `Site\HasServicePresentation` | `Site\Service` | `short_description_view`, `image_view`, `icon_view`, `icon_value` | - |
| `Contact\HasSocialPresentation` | `Contact\Social` | `icon_html`, `icon_value` | `icon_type_label` |
| `Contact\HasContactInfoPresentation` | `Contact\ContactInfo` | `map_embed_url` | - |
| `Geography\HasLanguagePresentation` | `Geography\Language` | `upper_code` | - |
| `Translations\HasTranslationPresentation` | `Translations\Translation` | `lang_check_exists`, `editable_value` | - |
| `User\HasUserPresentation` | `User\User` | - | `full_name` |
| `Analytics\HasAnalyticsClickPresentation` | `Analytics\AnalyticsClick` | `url_link`, `referer_link`, `device_link`, `operating_link`, `browser_link`, `country_link`, `city_link`, `language_link`, `ip_address_click` | - |
| `Analytics\HasAnalytics{AdPlatform,Browser,Country,Device,Language,OperatingSystem}Presentation` | eyniadlı lookup modelləri | `logo_img` / `icon_img` / `flag_img` / `icon_font` | - |

**Bilinən istisnalar (geriyə uyğunluq üçün saxlanılıb):**

- `Admin::role_summary`, `permission_summary`, `CustomRole::permissions_count`,
  `assigned_admins_count` əlaqə yüklənməyibsə özü sorğu atır;
  `Translation::lang_check_exists` hər dil üçün modelin `checkExists()`
  sorğusunu çağırır (N+1). Sorğu metodları modeldə qalıb; siyahıda `with()`
  verilməlidir.
- `*_badge`, `*_btn`, `*_view`, `*_link`, `*_img` köhnə HTML accessor-larıdır -
  dəyişən mətn `e()` ilə qaçırılır. SVG/font ikon (`Social::icon_html`,
  `Service::icon_view`) admin daxil etdiyi HTML olduğu üçün qəsdən xam qalır.
- `HasFiles::getAttribute()` (BaseModel) `<sütun>_view`-u accessor-dan əvvəl
  tutur: `$files` olan modeldə `$fillable` sütununun `_view`-u (məs.
  `Product::price_view`, `Blog::image_view`) heç vaxt trait-ə çatmır. Yeni
  kodda `*_formatted` işlədilir.
- `Menu::config` (config xəritəsindən oxu) data sayılıb modeldə qalıb.
- `DataUpdate::is_updated_status` köçürülməyib: `$appends`-də olan bool
  cast-dır (göstəriş deyil), model faylı da namespace/asılılıq baxımından
  natamamdır.
