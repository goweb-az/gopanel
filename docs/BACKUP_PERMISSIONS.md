# Backup modulu — fayl icazələri (server tələbləri)

Bu sənəd panelin **Backup** bölməsinin serverdə düzgün işləməsi üçün lazım olan
fayl/qovluq icazələrini izah edir. Eyni kod başqa layihələrdə də işlədilirsə
(judo, qrgate və s.) bu qaydalar olduğu kimi keçərlidir.

> Modulun ümumi izahı: [backup.md](backup.md).
> Buradakı hər şey **Linux serverə** aiddir - Windows/OpenServer-də NTFS
> `chmod`/`chgrp` çağırışlarını sadəcə nəzərə almır və bölmə onsuz da işləyir.

---

## 1. Problemin kökü: iki fərqli istifadəçi

| Əməliyyat | Kim icra edir | Nümunə istifadəçi |
|---|---|---|
| Arxivin yaradılması (mysqldump, zip) | queue worker (supervisor) | `<layihə>` (supervisor istifadəçisi) |
| Siyahının göstərilməsi, endirmə, silmə | veb server (PHP-FPM) | `www-data` |

Arxivi **bir istifadəçi yaradır, başqası oxuyur və silir**. Ona görə icazələr
qrup (group) üzərindən qurulur.

## 2. Laravel-in tələsi

`Storage::disk('local')` üçün `config/filesystems.php`-də `visibility`
göstərilməyibsə default **`private`**-dır:

- qovluqlar → `0700`
- fayllar → `0600`

Yəni `Storage::makeDirectory()` ilə yaradılan qovluğa **yalnız yaradan
istifadəçi** girə bilir. Veb server üçün arxiv sanki mövcud deyil.

> **Nəticə:** backup qovluqlarını `Storage::makeDirectory()` ilə yaratmaq
> OLMAZ. `mkdir` + açıq `chmod` işlədilir.

## 3. Tələb olunan icazələr

| Obyekt | Rejim | İzah |
|---|---|---|
| `storage/app/backups` | `2770` | setgid + qrupa oxu/yazma |
| `storage/app/backups/database` | `2770` | eyni |
| `storage/app/backups/files` | `2770` | eyni |
| `*.sql.gz`, `*.zip`, `*.files.json` | `0640` | sahib yazır, qrup oxuyur |
| Qovluq və faylların **qrupu** | veb serverin qrupu (`www-data`) | — |

### Niyə məhz belə

**a) Qrupa OXU — fayl görünməsi üçün**

`0700` olanda `www-data` qovluğa girə bilmir, `Storage::exists()` `false`
qaytarır və panel hazır arxivi «Fayl yoxdur» kimi göstərir.

**b) Qrupa YAZMA — fayl silinməsi üçün**

POSIX-də faylı silmək üçün **faylın özündə deyil, onun olduğu QOVLUQDA** yazma
icazəsi lazımdır. `2750` (qrupa yalnız `r-x`) olanda:

- arxiv görünür və endirilir ✅
- `unlink` səssiz uğursuz olur → **sətir bazadan silinir, fayl diskdə qalır** ❌

Ona görə qovluq `2770` olmalıdır (`2750` yetərli deyil).

**c) setgid (başdakı «2») — qrupun miras qalması üçün**

setgid olmayanda qovluğun içində yaranan hər yeni qovluq/fayl **yaradan
prosesin öz qrupunu** alır (`<layihə>` (supervisor istifadəçisi)), valideynin qrupunu yox (`www-data`).
Nəticədə yeni `files/` qovluğu `judo:judo` yaranır və (a) problemi geri qayıdır.

> ⚠ `chmod 750` və ya `chmod 770` yazmaq **setgid bitini silir**.
> Əl ilə düzəldəndə mütləq `chmod 2770` yazılır.

**d) «others» üçün heç bir hüquq verilmir**

Arxivdə bazanın tam nüsxəsi (istifadəçilər, hash-lər, ayarlar) və bütün
istifadəçi faylları var. Serverdəki digər istifadəçilər onu oxuya bilməməlidir.
`0777` / `0644` kimi «asan» variantlar qəbuledilməzdir.

## 4. Kodda necə təmin olunur

`config/gopanel/backup.php`:

```php
'directory_permission' => 02770,   // setgid + qrupa rwx
'file_permission'      => 0640,
```

`App\Services\Gopanel\Backup\BackupService`:

| Metod | Nə edir |
|---|---|
| `ensureFolder(BackupType)` | qovluğu `mkdir` ilə yaradır, `chmod` edir, qrupu düzəldir; həm kök (`backups`), həm tip qovluğu üçün — köhnə səhv icazələr özü düzəlir |
| `protectFile(string $absolutePath)` | yazılmış arxivə/siyahıya `file_permission` verir və qrupu düzəldir |
| `inheritGroup(string $path)` | qrupu `storage/app` ilə eyniləşdirir (setgid itirilibsə də düzəlir); `chgrp` alınmazsa səssiz keçir |

Job-larda `Storage::makeDirectory()` **işlədilmir** — `ensureFolder()` çağırılır,
fayl yazıldıqdan sonra isə `protectFile()`.

`App\Models\Backup\Backup::deleteArchive()` — silinmə alınmayanda **istisna
atır**, qeyd də silinmir. Beləliklə paneldə sətir yox olub diskdə sahibsiz
5 GB-lıq fayl qalması mümkün deyil.

## 5. `.files.json` siyahısı — xüsusi diqqət

Fayl backup-ı **artımlıdır**: hər arxivin yanında `<arxiv>.files.json` saxlanılır
və orada həmin arxivə düşən faylların siyahısı olur.

Bu siyahını **panel də oxuyur** (yeni backup başlamazdan əvvəl «yeni fayl varmı»
yoxlaması). Əgər siyahı `www-data` üçün oxunmazsa:

- sistem «heç nə arxivlənməyib» qərarına gəlir,
- növbəti backup **bütün 5+ GB-ı yenidən** arxivləyir,
- disk sürətlə dolur.

Ona görə `.files.json` faylına da `0640` + düzgün qrup verilir.

## 6. Serverdə bir dəfəlik düzəliş

```bash
cd /var/www/<layihə>/public

sudo chgrp -R www-data storage/app/backups
sudo chmod 2770 storage/app/backups storage/app/backups/database storage/app/backups/files
sudo chmod 640  storage/app/backups/database/* storage/app/backups/files/*

ls -ld storage/app/backups storage/app/backups/*
```

Gözlənilən nəticə:

```
drwxrws--- deploy www-data ... storage/app/backups
drwxrws--- deploy www-data ... storage/app/backups/database
drwxrws--- deploy www-data ... storage/app/backups/files
```

`rws` — qrupa yazma (`w`) və setgid (`s`) yerindədir.

## 7. Əlaqəli tələ: deploy-dan sonra queue worker

Laravel bütün `config/` faylını **proses başlayanda bir dəfə** oxuyur.
Uzunömürlü queue worker deploy-dan əvvəl başlayıbsa:

- yeni sinif faylları PSR-4 ilə iş vaxtı yüklənir (kod **işləyir**),
- yeni config faylı isə onun üçün **yoxdur** → `config(...)` `null` qaytarır.

Nəticədə tapşırıq mənasız xəta ilə bitir (məsələn «Mənbə qovluğu tapılmadı: »
— yoldan sonra boşluq). `php artisan optimize:clear` bunu **həll etmir** —
worker ayrıca prosesdir:

```bash
sudo supervisorctl restart <layihə>-worker:*
```

**Qayda: hər `git pull`-dan sonra worker yenidən başladılır.**

## 8. Diaqnostika cədvəli

| Əlamət | Səbəb | Düzəliş |
|---|---|---|
| Vəziyyət «Hazır», ölçü sütununda «Fayl yoxdur» | qovluq `0700` və ya qrup səhv | `chmod 2770` + `chgrp www-data` |
| Endirmə düyməsi çıxmır | eyni səbəb | eyni |
| Sətir silinir, fayl qalır | qovluqda qrupa yazma yoxdur (`2750`) | `chmod 2770` |
| Hər dəfə tam (full) arxiv çıxır | `.files.json` oxunmur | fayla `0640` + düzgün qrup |
| «Mənbə qovluğu tapılmadı: » (yol boş) | worker köhnə config ilə işləyir | `supervisorctl restart` |
| Arxiv başqa qovluqda yaranır | işi başqa quraşdırmanın işçisi götürüb (eyni baza) | hər mühit öz bazasına baxsın |

Sonuncu hal üçün job başlayanda `meta.ran_on` (host + `base_path`) yazılır —
panel «Fayl yoxdur» nişanının tooltip-ində arxivin hansı qovluqda yaradıldığını
göstərir.
