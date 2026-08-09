# Ümumi qaydalar (bütün layerlər)

## 0. Bu layihə bir STARTER-dir

Gopanel törəmə layihələrin skeletidir. Buradakı hər sinif başqa layihələrdə
görünəcək.

- Layihəyə xas iş məntiqi (davamiyyət, satış, elan, sifariş) **bura yazılmır** -
  o, törəmə layihədə qalır.
- Bura yalnız **ümumi, təkrar istifadə oluna bilən** qat düşür.
- "Bu sinif başqa layihədə də lazım olacaqmı?" sualının cavabı **xeyr**-dirsə,
  onun yeri bura deyil.
- Mövcud sinifin metod imzası **dəyişmir** - yenisi əlavə olunur. Törəmə
  layihələr köhnə imzadan asılıdır.

## 1. Layerlər - controller-də logika yazılmır

Controller **nazik** olmalıdır: request al → servisə ötür → cavab qaytar.

| Layer | Qovluq | Nə saxlayır |
|---|---|---|
| Controller | `app/Http/Controllers/...` | Yalnız orkestrasiya. `if`-lərlə dolu iş məntiqi YOX. |
| FormRequest | `app/Http/Requests/...` | Bütün validasiya. Controller-də inline `$request->validate()` **QADAĞAN**. |
| Service | `app/Services/...` | İş məntiqi, tranzaksiyalar, əməliyyat ardıcıllığı, xarici API. |
| Repository | `app/Repositories/...` | **Yalnız** insert / update / delete. SELECT yazılmır. |
| Query class | `app/Queries/{Gopanel,Site,Api}/...` | Bütün böyük/mürəkkəb SELECT-lər, hesabat, aggregate. |
| Support | `app/Support/...` | Saf, vəziyyətsiz primitivlər (formatlama, açar qurma, URL). |
| Helper | `app/Helpers/...` | Kiçik köməkçilər, konfiqurasiya/təqdimat xəritələri. |
| DTO | `app/DTOs/...` | Layerlər arası typed data (filtr, komanda, nəticə). |
| Enum | `app/Enums/...` | Sabit dəyər dəstləri. Sərbəst `int`/`string` sabitləri əvəzinə. |
| Resource | `app/Http/Resources/...` | API cavab formatı. |
| Datatable | `app/Datatable/...` | Server-side cədvəl sinifləri. |
| Policy | `app/Policies/...` | "Bu istifadəçi bunu edə bilər?" |
| Job | `app/Jobs/...` | Növbəyə düşən iş - **skalyar id daşıyır**, model obyekti yox. |

### Qərar ardıcıllığı - yeni sinifin yeri

1. Sabit, bitmiş dəyər dəstidir? → **Enum**
2. Layerlər arası typed data? → **DTO**
3. Yalnız oxuyur/aggregate edir? → **Query**
4. Yalnız insert/update/delete? → **Repository**
5. İş axını, vəziyyət dəyişikliyi, tranzaksiya? → **Service**
6. Uyğun siniflərə qarışdırılan davranış? → **Trait** (ehtiyatla)
7. Kiçik, vəziyyətsiz çevirmə/hesablama? → **Support**

İki və daha çox cavab uyğun gəlirsə - sinif **bölünməlidir**.

**Query class stili:** `declare(strict_types=1)`, konstruktorda readonly
promotion (`from` / `to` / `filters`), bir metod = bir sorğu. Metodlar
**instance**-dır, `static` deyil. Query içində `request()` OXUNMUR - filtr
konstruktorla ötürülür.

**Eyni məntiq iki yerdə lazımdırsa** - kopyalanmır, ortaq servisə/support-a çıxarılır.

## 2. Qovluq strukturu - hər şey öz yerində

Controller / servis / query / view / datatable **domenə görə** alt qovluqda olur:

```text
app/Http/Controllers/Gopanel/Site/BlogController.php
app/Services/Site/BlogService.php
app/Queries/Gopanel/Site/BlogQuery.php
app/Datatable/Gopanel/Site/BlogDatatable.php
resources/views/gopanel/pages/blogs/...
```

Kök qovluğa "səpələnmiş" fayl yazılmır. Yeni modul yazılırsa - əvvəlcə oxşar
mövcud modula baxılır və eyni yerləşdirmə təkrarlanır.

### 2.1 Bir mövzuda 2+ fayl → alt qovluq

Eyni mövzuya aid **iki və daha çox** sinif yığılan kimi onlar alt qovluğa
çıxarılır; tək qalanlar valideyn qovluqda saxlanılır.

Köçürəndə: `namespace` yenilənir **və bütün istinadlar** (`use ...`) axtarılıb
düzəldilir. Diqqət: eyni namespace-dəki siniflər bir-birini `use` olmadan
görürdü - ayrılanda həmin yerlərə `use` əlavə edilməlidir.

### 2.2 Boş qovluqlar

`app/Queries`, `app/DTOs`, `app/Contracts`, `app/Policies`, `app/Rules` və s.
boş olsa da `.gitkeep` ilə repoda saxlanılır - struktur ilk gündən görünsün,
hər layihədə yenidən "haraya yazım?" sualı yaranmasın.

## 3. Blade-də məntiq yoxdur

- `@php ... @endphp` blokları **yazılmır**. Hesablama, çeşidləmə, qruplaşdırma,
  şərtli mətn - hamısı controller/servisdə hazırlanıb blade-ə **hazır** ötürülür.
- Blade-də ancaq: çap (`{{ }}`), sadə `@if/@foreach`, `@include`, komponent çağırışı.
- Blade-də `<script>` və `<style>` bloku yazılmır - JS `public/assets/.../js/`,
  CSS `public/assets/.../css/` altındadır.
- Blade-dən JS-ə dəyər ötürmək üçün **`data-*` atributu** işlədilir
  (route, id, uid, config). JS həmin dəyəri `$el.data('...')` ilə oxuyur.
- **İSTİSNA:** email şablonları (`resources/views/emails/`). Email klientləri
  xarici CSS və flexbox dəstəkləmir - orada table layout + inline stil işlədilir.

## 4. Modallar

Modallar layihənin **mövcud modal məntiqi** ilə işləməlidir - yeni modal sistemi
qurulmur: `public/assets/gopanel/js/crud.js` + `main.js`
(`.view` / `.edit` / `#save-form-btn` / `#create-form`, `#view-modal`).

Forma modalın içi AJAX ilə serverdən gəlir, blade-də tam hazır HTML kimi yazılmır.
Xəta cavabı JSON `{status: 'error', ...}` formatındadır.

## 5. Permission adlandırması

**Bir əməliyyat = bir icazə.** Əlavə (`add`) ilə redaktə (`edit`) **birləşdirilmir**:

```text
✅ gopanel.settings.languages.index / .add / .edit / .delete / .sort
❌ gopanel.settings.languages.create   (həm əlavə, həm redaktə üçün)
```

Əlavə əməliyyatlar da öz icazəsini alır: `view`, `export`, `import`, `sort`, `restore`.
İcazə adı route adı ilə **eyni** olur (`gopanel.` prefiksi daxil).
İcazələr `config/gopanel/permission_list.php` faylında qrupla birlikdə qeydiyyatdan
keçir və `php artisan db:seed --class=PermissionSeeder` ilə bazaya yazılır.

Düymənin gizlədilməsi tək müdafiə deyil - server tərəfdə (FormRequest/Policy)
**eyni icazə** yoxlanılır.

## 6. Konfiqurasiya və sirlər

- Açar/token/parol **hardcode edilmir** → `config/custom/*.php` + `.env`.
- `env()` yalnız config fayllarının içində çağırılır. Servis/controller içində
  `config()` işlədilir - əks halda `php artisan config:cache` sonrası dəyər boş qalır.
- Yeni config faylı `config/custom/` altına düşür və `.env.example`-a
  müvafiq açarlar əlavə olunur.

## 7. Jurnal (log)

- Fayl jurnalı: `new LogService('kanal')` → `info()` / `error()`.
  Kanal `config/logging.php`-də qeydiyyatdan keçir (`manual => true` olanlar
  panel log-viewer siyahısında görünür).
- Fəaliyyət jurnalı (kim nə etdi): model `LogsAdminActivity` trait-ini işlədir
  **və** `config/custom/activity_messages.php`-də bir blok alır.
  **Config-də adı olmayan model ümumiyyətlə jurnallanmır** - trait tək kifayət deyil.
- Jurnal xətası əsas əməliyyatı **dayandırmamalıdır** (`try/catch` + `report()`).

## 8. Test

- Yazılan hər funksionallıq test edilir: `php artisan test`.
- Yeni Feature testi öz layer qovluğuna düşür: `tests/Feature/{Gopanel,Site,Api}/`.
- JS dəyişəndə ən azı `node --check`, PHP dəyişəndə `php -l`.
- Testi "keçsin deyə" məntiq dəyişdirilmir - test real davranışı yoxlayır.

## 9. Console command-lər

- **Birdəfəlik data köçürmə / backfill** command-ləri
  `app/Console/Commands/Migrate/` altına yazılır. Kök `app/Console/Commands/`
  yalnız daimi (təkrarlanan, scheduler-lik) command-lər üçündür.
- Belə command-lər **idempotent** yazılır (təkrar işlədəndə dublikat yaratmır)
  və `--dry-run` seçimi olur.
- Kütləvi əməliyyatda çıxış yalnız yekun rəqəm yox, **gedişat** göstərməlidir.

## 10. Geriyə uyğunluq (ən vacib qayda)

- **Mövcud funksionallıq pozulmur.** İstifadəçi "bunu dəyiş" demədikcə köhnə
  davranış olduğu kimi qalır.
- Ortaq servis/helper dəyişəndə onu çağıran **bütün yerlər** yoxlanılır.
- Miqrasiyada sütun silinmir/adı dəyişdirilmir - əlavə edilir.
- Silmə əməliyyatları (fayl, cədvəl, branch) **istifadəçi açıq deməsə** aparılmır.

## 11. Digər

- İstifadəçiyə görünən bütün mətn **Azərbaycan dilində**.
- CSS yazarkən **mobil mütləq nəzərə alınır** (media query, `overflow-x: auto`).
  Masaüstündə işləyib mobildə sınan qəbul edilmir.
- Yeni plugin əlavə etməzdən əvvəl şablonda hazır olanlara baxılır.
- Yeni ümumi sinifin docblock-unda **"niyə"** yazılır, təkcə "nə" yox.
  Nümunə: `App\Support\Date\DayRange` - `whereDate()` indeksi işlətmir, ona görə
  aralıq işlədilir. Bu izah olmasa növbəti dəfə kimsə `whereDate()`-ə qayıdır.
