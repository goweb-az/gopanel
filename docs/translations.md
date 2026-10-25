# Tərcümələr

Layihədə iki ayrı tərcümə sistemi var - **qarışdırılmır**:

| | UI mətnləri (bu sənədin əsas hissəsi) | Model sahə tərcümələri |
|---|---|---|
| Nə tərcümə olunur | «Ətraflı», «Göndər», «Bizə yazın» | Bloq başlığı, məhsul adı, slug |
| Cədvəl | `translations` | `field_translations` |
| Model | `App\Models\Translations\Translation` | `App\Models\Translations\FieldTranslation` |
| Kimlik | `platform + page + group + key + locale` | `model_type + model_id + key + locale` |
| Blade | `@lang('website.home.title.latest_posts')` | `$blog->title` |

---

## 1. UI mətnlərinin strukturu

```
platform   website | mobile | admin | panel      hansı client
page       home                                  hansı ekran (kataloq: config/gopanel/translation_pages.php)
group      title | content                       qısa və ya uzun mətn
key        latest_posts                          açar
locale     az | en | ru                          dil
value      Son yazılar                           mətn
```

Blade-də çağırış **həmişə** belədir:

```blade
<h2>@lang('website.home.title.latest_posts')</h2>
<p>@lang('website.contact.content.form_intro')</p>
<button>@lang('website.general.title.send')</button>
```

Format: `website.<page>.<group>.<key>`. Blade-də sabit mətn yazılmır və
`__('Son yazılar')` kimi «mətnin özü açar» forması işlədilmir. Səbəb: mətn
dəyişəndə açar da dəyişir və bütün tərcümələr itir.

### `title` və `content` - hansını seçməli

| Qrup | Nə vaxt | Nümunə |
|---|---|---|
| `title` | Qısa mətn: başlıq, düymə, menyu, etiket, bir neçə söz | «Məhsullar bölməsi», «Səbətə at», «Göndər» |
| `content` | Uzun mətn: cümlə, paraqraf, izah | «Məhsullar bölməsindən istifadə edərək istədiyiniz məhsulu səbətə ata və ala bilərsiniz.» |

**Niyə ayrılır:** paneldə tərcüməçi qısa etiketləri bir siyahıda, uzun mətnləri
ayrıca görür. Uzun mətn redaktorda geniş sahə tələb edir. Dizayner də başlığın
yerinə paraqraf qoyulmayacağını bilir.

### Açar adı

- kiçik hərf, rəqəm, `_`, `-` (`latest_posts`, `form-intro`);
- **nöqtə olmaz** - `@lang` nöqtəni bölgü kimi oxuyur və açar tapılmır;
- mənaya görə adlandırılır, mətnə görə yox: `cta_button`, `buy_now_button` yox.

---

## 2. Səhifə məntiqi (`page`)

Kataloq: **`config/gopanel/translation_pages.php`**, tək giriş nöqtəsi
`App\Helpers\Gopanel\TranslationPageRegistry`. Panelin səhifə seçicisi,
`translations:seed-site` və Excel idxalının yoxlaması buradan oxuyur.
**Kataloqda olmayan səhifəyə yazılan sətir rədd edilir.**

Qaydalar (aquastores və texnotech layihələrində sınanıb):

1. **Bir səhifə = saytın bir ekranı** (dizayndakı bir freym). Açar snake_case:
   `home`, `blog`, `product_show`, `cart`, `checkout`.
2. **Eyni ekranın vəziyyətləri ayrıca səhifə deyil.** Modal, boş siyahı, uğur
   mesajı, ikinci addım həmin səhifənin açarlarıdır:
   `cart.title.empty_title`, `cart.content.empty_text`.
3. **Hər səhifədə təkrarlanan komponentlər `general`-dadır**: header, footer,
   menyu, ümumi düymələr («Ətraflı», «Göndər»), alert, loader, 404.
4. **Dizaynda olmayan səhifə uydurulmur.** Ekran gələndə kataloqa əlavə olunur.
5. Siyahı və detal eyni ekran ailəsidirsə bir səhifə ola bilər (`blog`); çoxlu
   fərqli mətni olan detal ekranı ayrıca səhifə olur (`product_show`).

Yeni səhifə: `translation_pages.php`-yə `'açar' => 'Panel adı'` sətri əlavə
olunur. Başqa heç yerə toxunulmur.

---

## 3. Frontend implement edilərkən iş axını

Şablondan gələn bütün statik sözlər **bir JSON faylına** yığılır:

```text
database/data/translations/website.json
```

```json
[
  {
    "page": "home",
    "group": "title",
    "key": "latest_posts",
    "values": { "az": "Son yazılar", "en": "Latest posts", "ru": "Последние записи" }
  }
]
```

Sonra:

```bash
php artisan translations:seed-site            # yalnız ÇATIŞMAYAN sətirləri yazır
php artisan translations:seed-site --dry-run  # bazaya yazmadan nə ediləcəyini göstərir
php artisan translations:seed-site --force    # paneldə dəyişdirilmiş mətnləri də JSON-dakı ilə əvəz edir
php artisan translations:seed-site --file=database/data/translations/mobile.json --platform=mobile
```

Command: `App\Console\Commands\Migrate\SeedSiteTranslationsCommand`.

- **İdempotentdir.** Təkrar işlədəndə dublikat yaranmır və adminin paneldə
  dəyişdirdiyi mətn əzilmir (`--force` istisnadır).
- **Production-da təhlükəsizdir.** Deploy-da yeni söz əlavə olunubsa sadəcə
  yenidən işlədilir. `db:seed` əvəzinə command seçilməsinin səbəbi də budur:
  seeder production-da işlədilmir.
- Dil üçün mətn yoxdursa standart dilin mətni yazılır, sayt boş açar göstərmir.
- Paneldə silinmiş sətir bərpa olunur.
- Səhv sətir (kataloqda olmayan səhifə, nöqtəli açar, `title/content`-dən fərqli
  qrup) nömrəsi ilə göstərilir və yazılmır. Düzgün sətirlər yenə də yazılır.
  Command bu halda xəta kodu ilə bitir ki, CI/deploy bunu görsün.
- Yazılış `App\Services\Gopanel\Translations\TranslationWriteService` iləndir.
  Lang faylları hər dil üçün sonda bir dəfə yenilənir, keş təmizlənir.

**Qayda:** yeni söz əvvəlcə JSON-a, sonra Blade-ə yazılır. Yalnız paneldən
əlavə edilən söz başqa mühitə (dev → prod) keçmir.

---

## 4. Panel - Excel ixrac / idxal

Yer: **Tənzimləmələr → Tərcümələr**. İcazələr: `gopanel.settings.translations.export`,
`gopanel.settings.translations.import`.

| Düymə | Nə edir |
|---|---|
| **Excel-ə çıxar** | Cari platforma/səhifə filtrinə görə `.xlsx` yükləyir |
| **Excel-dən yüklə** | Eyni formatlı faylı idxal edir (modal, nümunə fayl linki ilə) |
| **JSON-a çıxar** | `database/seeders/json-data/translations/` altına mock seeder faylları yazır |

**Format hər iki istiqamətdə eynidir.** Yüklənən fayl heç bir düzəliş etmədən
geri yüklənə bilər. Production-dakı sözləri lokala və ya dev-ə köçürmək üçün
nəzərdə tutulub.

```text
platform | page | group | key | az | en | ru     (dil sütunları = aktiv dillər)
website  | home | title | latest_posts | Son yazılar | Latest posts | ...
```

- Sütunların **adı** vacibdir, sırası yox. `group` sütunu istəyə bağlıdır.
- Boş dil xanası həmin dili **dəyişmir**.
- Rejim: «Yalnız çatışmayanları əlavə et» (default) və ya «Mövcud mətnləri də yenilə».
- Səhv sətirlər Excel sətir nömrəsi ilə göstərilir, qalanları yazılır.
- Faylın ikinci vərəqi «Bələdçi»dir: icazəli səhifələr və qruplar. İdxal yalnız
  birinci vərəqi oxuyur.
- Limit: 20 000 sətir, 10 MB.
- Nümunə fayl: `public/example-files/translations-example.xlsx`. O,
  `TranslationExcelService::build()` ilə yaradılıb, yəni ixracla eyni qəlibdir.
  Format dəyişsə nümunə fayl da yenidən yaradılır.

Siniflər: `TranslationExcelService` (format + idxal), `TranslationMatrixQuery`
(açar × dil matrisi), `App\Support\Translation\UiTranslationRow` (sətir yoxlaması -
command ilə ortaq).

> Köhnə «Toplu idxal» (JSON/`key|value` XLSX, dil/səhifə modalda seçilirdi) silinib.
> O, bir faylda yalnız bir dil və bir səhifə daşıyırdı və ixrac ilə eyni formatda
> deyildi.

---

## 5. Runtime və keş

`App\Providers\TranslationServiceProvider` `website` platformasının sətirlərini
translator-a `website.<page>.<group>.<key>` açarı ilə əlavə edir. Keş
`site_translations_{locale}`-dir. Yazılışdan sonra
`TranslationCacheService::flush()` həm bu keşi, həm də `CacheKey` `translation`
qrupunu təmizləyir. Lang faylları (`resources/lang/<dil>/website.php`,
`resources/lang-json/<dil>/website.json`) DB-dən yaradılır, əl ilə redaktə edilmir.

---

## 6. Model sahə tərcümələri

`field_translations` cədvəli. Model `Translation` trait-ini işlədir:

```php
public $translatedAttributes = ['title', 'description', 'slug'];
```

Yazma `App\Helpers\Gopanel\TranslationHelper` (`fromInput()`, `basic()`) və ya
`ContentSaveService` iləndir. Slug-lar `field_translations` üzərindən həll olunur,
dinamik route hədəf modeli buradan tapır.
