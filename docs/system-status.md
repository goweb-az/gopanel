# Sistem vəziyyəti (monitor)

Panelin `Sistem vəziyyəti` bölməsi serverin **anlıq** göstəricilərini göstərir:
prosessor, yaddaş, disk, növbə, planlaşdırıcı, baza, storage və backup xülasəsi.

**Heç bir cədvəl saxlanılmır** — hər sorğuda canlı dəyər oxunur; qrafikin
tarixçəsi yalnız brauzerdə, səhifə açıq qaldığı müddətdə yığılır.

Ünvan: `/gopanel/system-status` · Route prefiksi `gopanel.system-status.*`

---

## 1. Səhifə nə göstərir

| Blok | Mənbə | Qeyd |
|---|---|---|
| Prosessor | `/proc/stat` (Linux), `wmic` (Windows) | Faiz iki ölçmənin fərqindən çıxır |
| Yaddaş (RAM) | `/proc/meminfo`, `wmic` | `MemAvailable` işlədilir, `MemFree` yox |
| Disk | `disk_total_space()` / `disk_free_space()` | Layihənin yerləşdiyi bölmə |
| Canlı yük | brauzer | Səhifə bağlananda sıfırlanır |
| Növbə | `jobs`, `failed_jobs` cədvəlləri | `sync` sürücüsündə xəbərdarlıq verilir |
| Planlaşdırıcı | heartbeat faylı + `Schedule::events()` | Aşağıda ayrıca izah |
| Server / PHP / Baza | `php_uname`, `ini_get`, `information_schema` | — |
| Storage / Ehtiyat nüsxələr | qovluq ölçüləri + `backups` cədvəli | [backup.md](backup.md) |
| Crontab | `crontab -l` | Yalnız səhifə ilk açılanda oxunur |

---

## 2. Fayl xəritəsi

```text
app/Helpers/Gopanel/ServerMetricsHelper.php        CPU / RAM / disk / uptime (OS səviyyəsi)
app/Queries/Gopanel/System/QueueQuery.php          növbə, uğursuz işlər, baza statistikası
app/Services/Gopanel/System/SystemStatusService.php məlumatı hazırlayır və formatlayır
app/Http/Controllers/Gopanel/System/SystemStatusController.php
config/gopanel/system_status.php
resources/views/gopanel/pages/system_status/index.blade.php
resources/views/gopanel/pages/system_status/partials/{gauge-meta,info-card,live}.blade.php
public/assets/gopanel/js/modules/system-status.js
app/Console/Kernel.php                             heartbeat yazan planlaşdırılmış iş
```

---

## 3. Yenilənmə necə işləyir

```text
Səhifə açılır → tam HTML + qrafiklər qurulur
   ↓ hər 5 saniyədən bir (config: refresh_ms)
GET gopanel.system-status.data
   ↓ cavab
{ gauges: {...}, checked_at: "...", html: { cpu, memory, disk, live } }
   ↓
JS yalnız rəqəmləri qrafikə verir, HTML bloklarını isə OLDUĞU KİMİ yerinə qoyur
```

**Niyə HTML serverdən gəlir:** formatlaşdırma (ölçü, tarix, müddət, rəng)
yalnız blade-də qalsın deyə. JS-də təkrarlansaydı, eyni dəyər iki yerdə iki cür
görünərdi (bax: `.claude/rules/01-umumi.md` § 3).

**Niyə qrafiklər ayrıdır:** ApexCharts obyektləri hər yenilənmədə yenidən
qurulsaydı animasiya sıfırdan başlayardı və canlı qrafikin tarixçəsi itərdi.
Ona görə qrafiklərin altındakı sətirlər (`gauge-meta`) ayrıca partial-dır.

Səhifə arxa fonda olanda (`document.hidden`) sorğu göndərilmir — brauzerin
digər tabında açıq qalan panel serveri yükləməsin.

---

## 4. Planlaşdırıcı (cron) göstəricisi

Sual: **cron `schedule:run`-u həqiqətən çağırırmı?**

`App\Console\Kernel::schedule()` hər dəqiqə bir fayla vaxt damgası yazır:

```text
storage/app/system/scheduler-heartbeat.txt
```

Səhifə həmin faylın yaşına baxır (`scheduler_stale_seconds`, default 180 san.):

- fayl təzədir → **İşləyir**
- fayl köhnədir → **Dayanıb**
- fayl yoxdur → **Heç vaxt işləməyib**

> **Niyə keş yox, fayl:** `php artisan cache:clear` göstəricini sıfırlasaydı,
> hər deploy-dan sonra cron «dayanıb» görünərdi.

Serverin crontab-ında bu sətir olmalıdır:

```cron
* * * * * cd /var/www/<layihə> && php artisan schedule:run >> /dev/null 2>&1
```

Səhifədəki «Planlaşdırılmış işlər» cədvəli `php artisan schedule:list`-in panel
versiyasıdır — konsol kernel-i HTTP sorğusunda container-dən çıxarılır ki,
`Kernel::schedule()` işə düşsün.

---

## 5. Növbə göstəricisi

- `QUEUE_CONNECTION=sync` olanda **xəbərdarlıq** göstərilir: işlər növbəyə
  düşmür, sorğunun içində icra olunur. Backup kimi ağır işlər üçün prod-da
  `database` olmalıdır.
- `jobs` cədvəli yoxdursa (`php artisan queue:table` işlədilməyibsə) səhifə
  sınmır, sadəcə bunu yazır.
- Növbədəki ən köhnə iş `stale_job_seconds`-dan (default 5 dəq.) çox
  gözləyirsə xəbərdarlıq verilir — adətən bu, worker-in dayandığını bildirir.

---

## 6. Konfiqurasiya

`config/gopanel/system_status.php`:

| Açar | Default | Nə edir |
|---|---|---|
| `refresh_ms` | 5000 | Səhifənin özünü yeniləmə aralığı |
| `history_points` | 60 | Canlı qrafikdə saxlanılan nöqtə sayı (≈5 dəq.) |
| `job_list_limit` | 10 | Növbə cədvəllərində sətir sayı |
| `stale_job_seconds` | 300 | Bundan çox gözləyən iş xəbərdarlıq verir |
| `scheduler_stale_seconds` | 180 | Heartbeat bundan köhnədirsə cron «dayanıb» |
| `heartbeat_file` | `storage/app/system/...` | Cron siqnalının yeri |
| `show_crontab` | `true` | `crontab -l` səhifədə göstərilsinmi |
| `thresholds` | 75 / 90 | Sarı və qırmızı rəng həddi |

`.env` açarları: `SYSTEM_STATUS_REFRESH_MS`, `SYSTEM_STATUS_SHOW_CRONTAB`.

---

## 7. İcazə

Tək icazə: **`gopanel.system-status.index`**.

Bölmə serverin daxili məlumatını (yollar, versiyalar, baza adı, növbə
vəziyyəti) açır, ona görə yalnız texniki adminlərə verilir. Yoxlama iki
yerdədir — route middleware-i **və** controller (`authorizeSection()`).

```bash
php artisan db:seed --class=PermissionSeeder
```

---

## 8. Mühit fərqləri

| Göstərici | Linux (prod) | Windows (dev) |
|---|---|---|
| CPU faizi | `/proc/stat` | `wmic cpu get loadpercentage` |
| Yük (1/5/15) | var | yoxdur (`—`) |
| Prosessor adı | var | yoxdur (`—`) |
| RAM | `/proc/meminfo` | `wmic OS get ...` |
| Swap | var | yoxdur |
| İşləmə müddəti | `/proc/uptime` | yoxdur |
| Crontab | `crontab -l` | yoxdur — izah mətni göstərilir |

Alınmayan göstərici **`—`** kimi görünür; səhifə heç vaxt xəta ilə dayanmır.

---

## 9. Tez-tez rast gəlinən problemlər

| Əlamət | Səbəb və həll |
|---|---|
| «Planlaşdırıcı: Heç vaxt işləməyib» | Cron qurulmayıb və ya `schedule:run` işləmir. Bir dəfə `php artisan schedule:run` işlədib yoxlayın. |
| «Planlaşdırıcı: Dayanıb» | Cron əvvəl işləyib, indi dayanıb — crontab sətrini və deploy-dan sonra fayl icazələrini yoxlayın. |
| Növbə bloku boşdur, xəbərdarlıq var | `QUEUE_CONNECTION=sync`. Prod-da `database` + `queue:work`. |
| CPU/RAM «—» | Windows-da bəzi göstəricilər oxunmur; Linux-da `/proc` bağlıdırsa (bəzi konteynerlərdə) eyni nəticə olur. |
| Crontab bloku boşdur | `crontab -l` veb server istifadəçisi üçün boşdur — cron adətən root/deploy istifadəçisindədir. Bu normaldır; «Planlaşdırıcı» göstəricisi əsas cavabdır. |
| Səhifə yavaş açılır | `show_crontab` xarici proses çağırır; lazım deyilsə `SYSTEM_STATUS_SHOW_CRONTAB=false`. |
