# GoPanel qaydaları (admin panel)

Route prefiksi `gopanel.*` · Controller `app/Http/Controllers/Gopanel/`
· View `resources/views/gopanel/` · Guard `gopanel`

## 1. Yeni CRUD modulu - ardıcıllıq

Aşağıdakı 8 addımın **hamısı** edilməlidir. Biri unudulsa modul ya görünmür,
ya da icazəsiz açılır.

1. **Migration** → `database/migrations/`. Sütun silinmir, əlavə edilir.
2. **Model** → `app/Models/<Domen>/<Ad>.php`
   - `BaseModel`-dən törəyir;
   - lazım olan trait-lər: `AddUuid`, `Translation`, `HasFiles`, `HasArchive`,
     `LogsAdminActivity`, `Cacheable`;
   - çoxdilli sahələr `public $translatedAttributes = [...]`;
   - fayl sahələri `protected $files = [...]`.
3. **Controller** → `app/Http/Controllers/Gopanel/<Domen>/<Ad>Controller.php`.
   Nazik: request → servis → cavab. Fayl yükləmə, tərcümə, meta burada YAZILMIR.
4. **FormRequest** → `app/Http/Requests/Gopanel/<Domen>/`, `GopanelFormRequest`-dən
   törəyir. Validasiya burada, controller-də inline `validate()` yoxdur.
   Sinifdə `$module`, `$translatedFields`, `$fileInputs` və `fileFields()` elan
   olunur - icazə yoxlaması və `payload()` avtomatik gəlir.
5. **Service**: adi məzmun modulunda ayrıca servis yazılmır -
   `App\Services\Gopanel\Content\ContentSaveService` işlədilir. Əlavə addım
   varsa (keş invalidasiyası, ağac yoxlaması) modul servisi
   `app/Services/Gopanel/<Domen>/` altına düşür.
   Böyük SELECT lazımdırsa **Query** → `app/Queries/Gopanel/<Domen>/`,
   yazma isə `App\Repositories\BaseRepository` üzərindən.
6. **Datatable** → `app/Datatable/Gopanel/<Domen>/<Ad>Datatable.php`,
   `BaseDatatable`-dan törəyir.
7. **Route** → `routes/gopanel.php`. Ad `gopanel.<qrup>.<modul>.<əməliyyat>`.
8. **İcazə + sidebar**:
   - `config/gopanel/permission_list.php` → `gopanel` guard-ı altında qrup + sətirlər;
   - `config/gopanel/sidebar_menu_list.php` → menyu sətri (`route` + `can`);
   - `php artisan db:seed --class=PermissionSeeder`.

**View**: `resources/views/gopanel/pages/<modul>/` - `index`, `form`, `view`
blade-ləri. Layout `gopanel/layouts/`, təkrarlanan hissələr `gopanel/component/`.

## 2. Datatable

- Server-side işləyir - bütün filtr/sıralama/səhifələmə **serverdə** olur.
- Sıralama sütunları **ağ siyahıdadır** - kənardan gələn sütun adı birbaşa
  `orderBy()`-a verilmir (SQL injection + indeksdən düşmə).
- Sətirdəki düymələr icazəyə görə gizlədilir, amma server tərəf də yoxlayır.
- Sütunda hesablama lazımdırsa - onu Query/Service hazırlayır, datatable yalnız çap edir.

## 3. JS və CSS

- JS: `public/assets/gopanel/js/` - ortaq davranış `crud.js`, `main.js`,
  `initDatatable.js`; modula xas kod `js/modules/<qrup>/` altında.
- CSS: `public/assets/gopanel/css/`.
- Blade-də `<script>` / `<style>` **yazılmır**. Dəyər `data-*` atributu ilə ötürülür.
- Yeni plugin əlavə etməzdən əvvəl şablonda hazır olanlara baxılır (Skote şablonu):
  DataTables, Select2, Flatpickr, SweetAlert2, Toastr, Boxicons, Font Awesome 5.

## 4. İkonlar

- Sidebar və menyular **Boxicons** (`<i class="bx bx-home-circle"></i>`) işlədir -
  `config/gopanel/sidebar_menu_list.php`-də tam HTML kimi yazılır.
- İkon seçimi modalı Font Awesome 5 siyahısından gəlir
  (`config/gopanel/font_awesome_icons.php` + `IconPickerHelper`).
- Kənar şablondan (Phosphor/Tabler) gələn ikon adı varsa
  `App\Support\Gopanel\PanelIconMap::fontAwesome()` ilə çevrilir - əks halda
  panel boş kvadrat göstərir.

## 5. Info kartları və statistika

Dashboard/siyahı başındakı kartlar əl ilə yığılmır:

```php
use App\Support\Gopanel\PeriodRange;
use App\Support\Gopanel\StatCard;

$period = PeriodRange::fromFilters($request->from, $request->to);
$prev   = $period->previous();

$cards = StatCard::collection([
    [
        'label'    => 'Yeni istifadəçilər',
        'value'    => $current,
        'current'  => $current,
        'previous' => $previous,       // trend faizi avtomatik hesablanır
        'series'   => $daily,          // sparkline
        'icon'     => 'fas fa-users',
        'hint'     => $period->label(),
        'url'      => route('gopanel.users.index'),
    ],
]);
```

Faiz, ox istiqaməti və rəng **blade-də hesablanmır** - `StatCard` verir.

## 6. Toplu (bulk) əməliyyatlar

Checkbox ilə seçilən sətirlər üçün `App\Services\Bulk\BulkActionService`
törəməsi yazılır. Qaydalar:

- Toplu rejim **yeni məntiq yazmır** - tək sətir üçün işləyən eyni servisi çağırır.
  Əks halda iki rejim iki fərqli nəticə verir.
- Bir sətrin xətası qalanları **dayandırmır**; nəticədə uğurlu/uğursuz/ötürülən
  sayı qaytarılır.
- Hər əməliyyat öz icazəsini soruşur (`abilityFor()`), server tərəfdə də yoxlanılır.

## 7. Silmə

- Modeldə `SoftDeletes` varsa silmə **arxivləmədir** - `HasArchive` trait-i ilə.
- Birdəfəlik (force) silmə toplu rejimdə **yoxdur** - yalnız tək-tək və
  ayrıca icazə ilə.
- Silinmiş sətirlərə aid fayl dərhal diskdən silinmir (bərpa mümkün olsun).

## 8. Bildirişlər bölməsi

Admin bildiriş bölməsi (header badge + dropdown + siyahı + arxiv + toplu
əməliyyat) **sıfırdan icad edilmir** — addım-addım qayda hazırdır:
[docs/rules/gopanel-notifications-module.md](../../docs/rules/gopanel-notifications-module.md).

Ən çox unudulan üç məqam:

- domen servisi **`dispatch()`** çağırır, sinxron `send()` yox;
- queue-da `$admin->can()` **işləmir** (`Gate::before` login tələb edir);
- `delete-all` route-u `{notification}`-dan **əvvəl** yazılır.

## 9. Backup bölməsi

Panelin `Backup` bölməsi (baza + artımlı fayl arxivi) hazırdır - yenidən
yazılmır: [docs/backup.md](../../docs/backup.md).

Ən çox unudulan üç məqam:

- `gopanel.backup.index` icazəsi **arxiv endirmək** deməkdir, arxivdə isə bütün
  baza var - bu icazə hər vəzifəyə paylanmır;
- job model obyekti yox, **`id`** daşıyır və `$tries = 1`-dir (uğursuz backup
  təkrarlanmır - səbəb adətən sistemdədir);
- `mysqldump` binary-si serverdəki baza ilə **eyni ailədən** olmalıdır
  (MariaDB serverə MySQL 8-in dump-ı `column_statistics` xətası verir).

## 10. Sistem vəziyyəti bölməsi

Serverin canlı monitoru (CPU/RAM/disk, növbə, cron, baza) hazırdır:
[docs/system-status.md](../../docs/system-status.md).

İki qayda unudulmur:

- səhifə bir neçə saniyədən bir yenilənir - oraya **ağır əməliyyat əlavə
  edilmir** (rekursiv `public/site` gəzişi, limitsiz `get()`);
- yenilənmədə **hazır HTML** göndərilir, JS heç nə formatlamır - əks halda eyni
  dəyər panelin iki yerində iki cür görünür.

## 11. Fəaliyyət jurnalı

Yeni modelin dəyişiklikləri panelin «Fəaliyyət» bölməsində görünsün istəyirsənsə:

1. modelə `App\Traits\Activity\LogsAdminActivity` trait-i əlavə et;
2. `config/custom/activity_messages.php`-də model adı (namespace-siz) ilə blok yaz.

**İkinci addım olmadan heç nə jurnallanmır** - trait config-də adı olmayan modeli
sükutla ötürür. Mesajlarda yalnız HƏMİŞƏ dolu olan sütunlar placeholder kimi
işlədilir (`:name`, `:title`), nullable sahələr mesajda boş yer buraxır.
