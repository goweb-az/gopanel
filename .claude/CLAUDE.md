# Gopanel

Laravel 10 / PHP 8.1 admin panel **starter**-i. Bu layihə tək bir məhsul deyil -
yeni layihələrin üzərində qurulduğu skeletdir (Proweb). Buraya yazılan hər şey
BÜTÜN gələcək layihələrdə görünəcək, ona görə layihəyə xas iş məntiqi (məsələn
davamiyyət, satış, elan) bura ƏLAVƏ EDİLMİR - yalnız ümumi, təkrar istifadə
oluna bilən qat saxlanılır.

## Kod yazmazdan ƏVVƏL oxu

`.claude/rules/` bağlayıcı iş qaydalarını saxlayır:

| Fayl | Nə üçün |
|---|---|
| [rules/01-umumi.md](rules/01-umumi.md) | Layerlər, qovluq yerləşdirməsi, blade-də məntiq yoxdur, permission adlandırması, test, geriyə uyğunluq |
| [rules/02-gopanel.md](rules/02-gopanel.md) | GoPanel (`gopanel.*`) - CRUD modulu yazma ardıcıllığı, datatable, sidebar, icazə |
| [rules/03-site.md](rules/03-site.md) | Sayt tərəfi (`web.*`) - dinamik route, SEO, tərcümə, menyu |
| [rules/04-api.md](rules/04-api.md) | API - cavab formatı, resource, autentifikasiya |

**Ziddiyyət olarsa sıralama:** istifadəçinin cari mesajı → `.claude/rules/` → bu fayl → ümumi vərdişlər.

## Layerlər

| Layer | Route | Controller | View |
|---|---|---|---|
| GoPanel (admin) | `routes/gopanel.php` (`gopanel.*`) | `app/Http/Controllers/Gopanel` | `resources/views/gopanel` |
| Sayt | `routes/web.php` | `app/Http/Controllers/Site` | `resources/views/site` |
| API | `routes/api.php` | `app/Http/Controllers/Api` | JSON Resource |

## Hazır ümumi qat (yenidən yazma - VAR)

Yeni layihədə bunlar hazırdır; təkrar yazmaq əvəzinə istifadə et.
Tam siyahı və nümunələr: [docs/shared-layer.md](../docs/shared-layer.md)

| Ehtiyac | Sinif |
|---|---|
| Keş (oxu/yaz/flush) | `App\Services\Cache\CacheService` |
| Keş açarı + qrup invalidasiyası | `App\Support\Cache\CacheKey` (+ `config/custom/cache.php`) |
| Email göndərişi | `App\Services\Mail\MailService` |
| SMS göndərişi | `App\Services\Sms\SmsService` (+ `config/custom/sms.php`) |
| Toplu (bulk) əməliyyatlar | `App\Services\Bulk\BulkActionService` (abstract) |
| Export (Excel/PDF) skeleti | `App\Services\Export\*` (+ `config/custom/export.php`) |
| Növbə monitorinqi | `App\Services\Queue\QueueMonitorService` |
| Fayl/CDN URL-i | `App\Support\Url\CdnUrl` |
| Tarix aralığı / müddət / ay adları | `App\Support\Date\{DayRange,Duration,MonthNames}` |
| Panel info kartı + trend | `App\Support\Gopanel\StatCard` |
| Dövr müqayisəsi (bu dövr / əvvəlki) | `App\Support\Gopanel\PeriodRange` |
| Kənar ikonu FA5-ə çevir | `App\Support\Gopanel\PanelIconMap` |
| Fayl jurnalı | `App\Services\Activity\LogService` |
| Fəaliyyət jurnalı | `App\Traits\Activity\LogsAdminActivity` + `config/custom/activity_messages.php` |

## Qovluq xəritəsi

```text
app/Contracts     interfeyslər (infrastruktur adapterləri)
app/DTOs          typed data transfer obyektləri
app/Datatable     server-side cədvəl sinifləri
app/Enums         sabit dəyər dəstləri (kod idarə edir)
app/Helpers       kiçik köməkçilər + global helpers.php
app/Http          Controller / Request / Middleware / Resource
app/Jobs          növbəyə düşən işlər
app/Models        Eloquent modelləri
app/Observers     model event dinləyiciləri
app/Policies      "bu istifadəçi bunu edə bilər?"
app/Queries       BÜTÜN böyük SELECT-lər (Gopanel / Site / Api)
app/Repositories  YALNIZ insert / update / delete
app/Rules         xüsusi validasiya qaydaları
app/Services      iş məntiqi, tranzaksiyalar, xarici API
app/Support       saf, vəziyyətsiz köməkçi primitivlər
app/Traits        model davranışı və təqdimat trait-ləri
config/custom     layihəyə xas config (activity_messages, cache, mail, sms, export...)
config/gopanel    panel konfiqurasiyası (sidebar, menyu, icazə siyahısı)
docs              modul sənədləri
```

## Vacib vərdişlər

- İstifadəçiyə görünən BÜTÜN mətn **Azərbaycan dilində**.
- Açar/token/parol hardcode edilmir → `config` + `.env`.
- Yeni ümumi sinif yazanda **docblock-da "niyə" yazılır**, təkcə "nə" yox.
- Layihəyə xas modul bu repoya əlavə edilmir - o, törəmə layihədə qalır.
- Bu bir starter olduğu üçün **geriyə uyğunluq** kritikdir: mövcud sinifin
  metod imzası dəyişmir, yenisi əlavə olunur.

## Sənədlər

- [docs/README.md](../docs/README.md) — bütün sənədlərin indeksi (buradan başla)
- [docs/shared-layer.md](../docs/shared-layer.md) — ümumi qat (Support/Services/Queries) kataloqu
- [docs/ai-guide.md](../docs/ai-guide.md) — praktik naxışlar və kod nümunələri (uzun)
- [docs/database-structure.md](../docs/database-structure.md) — cədvəllər, əlaqələr, trait-lər
- [docs/rules/README.md](../docs/rules/README.md) — böyük modul qurma spesifikasiyaları:
  bildirişlər, dashboard, istifadəçi idarəetməsi, kateqoriya ağacı, API, deployment

**Sıra:** bu fayl → `.claude/rules/` → `docs/shared-layer.md` → `docs/ai-guide.md`.
Ziddiyyətdə `.claude/rules/` üstündür.
