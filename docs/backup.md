# Backup (panel arxivi)

Panelin `Backup` bölməsi iki şeyi arxivləyir: **bazanı** və **paneldən yüklənən
faylları**. Arxivlər serverdə `storage/app/backups/` altındadır — nə git-ə düşür,
nə də birbaşa URL ilə açıla bilir; endirmə yalnız panel route-u üzərindən,
icazə yoxlaması ilə mümkündür.

Ünvan: `/gopanel/backup` · Route prefiksi `gopanel.backup.*`

---

## 1. İki backup tipi

| Tip | Nə arxivlənir | Format | Rejim |
|---|---|---|---|
| `database` | Bütün baza (`mysqldump`) | `.sql.gz` | həmişə tam |
| `files` | `public/site/` (paneldən yüklənən şəkil/sənəd/video) | `.zip` | **artımlı** |

### Niyə fayllar artımlıdır

`public/site/` qovluğu böyüyür (GB-larla) və məzmunu demək olar tamamilə
jpg/png/mp4-dür. Hər dəfə tam zip çıxarmaq diski doldurur və hər arxiv onlarla
dəqiqə çəkir.

Panel yükləmələrində fayl adı `uniqid()` ilə unikaldır (`FileUploader`), yəni
mövcud fayl heç vaxt üzərinə yazılmır. Ona görə hər arxivə **yalnız əvvəlkilərdə
olmayan** fayllar düşür və arxivlərin cəmi = bütün fayllar.

**Bərpa:** bütün `files` arxivləri **köhnədən yeniyə doğru** eyni qovluğa açılır.
Baza arxivi isə tək fayldır.

### «Hansı fayllar artıq arxivlənib» sualı

Vahid siyahı saxlanılmır. Hər uğurlu fayl arxivinin yanında `<arxiv>.files.json`
manifesti olur və `BackupService::archivedFiles()` onları birləşdirir.

Səbəbi: paneldən bir backup silinəndə onun manifesti də silinir (bax
`Backup::booted()`), deməli həmin fayllar avtomatik «arxivlənməmiş» sayılır və
növbəti backup-a yenidən düşür. Ayrıca təmizləmə məntiqinə ehtiyac qalmır.

---

## 2. Fayl xəritəsi

```text
app/Enums/Gopanel/BackupType.php            database | files
app/Enums/Gopanel/BackupStatus.php          pending | running | completed | failed
app/Models/Backup/Backup.php                qeyd + arxivin silinməsi
app/Policies/Gopanel/BackupPolicy.php       CrudPolicy törəməsi (update = false)
app/Queries/Gopanel/Backup/BackupQuery.php  bütün SELECT-lər
app/Repositories/Gopanel/BackupRepository.php  vəziyyət yazılışı (tək yer)
app/Services/Gopanel/Backup/BackupService.php  yoxlamalar, növbəyə salma, statistika
app/Jobs/Backup/BackupJob.php               ortaq skelet: running/completed/failed
app/Jobs/Backup/CreateDatabaseBackup.php    mysqldump + gzip
app/Jobs/Backup/CreateFilesBackup.php       artımlı zip + manifest
app/Datatable/Gopanel/Backup/BackupDatatable.php
app/Http/Controllers/Gopanel/Backup/BackupController.php
app/Http/Requests/Gopanel/Backup/BackupStartRequest.php
config/gopanel/backup.php
resources/views/gopanel/pages/backup/index.blade.php
public/assets/gopanel/js/modules/backup.js
database/migrations/2026_09_02_100000_create_backups_table.php
```

---

## 3. Quraşdırma

1. **Miqrasiya:**

   ```bash
   php artisan migrate
   ```

2. **İcazələr** (`config/gopanel/permission_list.php` → qrup `Backup`):

   ```bash
   php artisan db:seed --class=PermissionSeeder
   ```

   | İcazə | Nə verir |
   |---|---|
   | `gopanel.backup.index` | bölməni görmək **və arxiv endirmək** |
   | `gopanel.backup.add` | yeni backup çıxarmaq |
   | `gopanel.backup.delete` | qeydi və arxivi silmək |

   > **Diqqət:** `index` icazəsi arxiv endirmək deməkdir, arxivdə isə bütün baza
   > var. Bu icazəni hər vəzifəyə paylamayın.

3. **`.env`** (`config/gopanel/backup.php` oxuyur):

   ```dotenv
   BACKUP_MYSQLDUMP_BINARY=mysqldump
   BACKUP_MIN_FREE_SPACE=2147483648   # 2 GB
   BACKUP_JOB_TIMEOUT=3600
   ```

4. **Növbə işçisi.** Job-lar `ShouldQueue`-dur. `QUEUE_CONNECTION=sync` olanda
   arxiv sorğunun içində çıxarılır — kiçik layihədə işləyir, böyük fayl
   qovluğunda sorğu vaxt aşımına düşür. Prod-da:

   ```dotenv
   QUEUE_CONNECTION=database
   ```

   ```bash
   php artisan queue:table && php artisan migrate      # bir dəfə
   php artisan queue:work --timeout=3600
   ```

   `--timeout` `BACKUP_JOB_TIMEOUT`-dan **kiçik olmamalıdır** — əks halda işçi
   arxivi yarımçıq kəsir və qeyd «Xəta» kimi qalır.

---

## 4. İşləmə axını

```text
Panel düyməsi (backup.js)
   → POST gopanel.backup.start   (BackupStartRequest: icazə + tip yoxlaması)
   → BackupService::start()      (işləyən backup? disk yeri? yeni fayl var?)
   → Backup qeydi: pending       (BackupRepository::createPending)
   → CreateDatabaseBackup | CreateFilesBackup növbəyə düşür
        → running  (+ meta.ran_on: host və base_path)
        → arxiv yaradılır
        → completed (path, size, file_count)
   → JS 2 saniyədən bir gopanel.backup.status soruşur, cədvəli yeniləyir
```

Backup bitəndə sorğu dayandırılır — boş yerə server yüklənmir.

---

## 5. Təhlükəsizlik qərarları

- **Parol heç yerdə görünmür.** `mysqldump` üçün müvəqqəti `defaults-extra-file`
  yaradılır (0600) və iş bitəndə silinir. Parol əmr sətrində olsaydı serverdə
  `ps aux` işlədən hər kəs onu görərdi.
- **Arxiv public deyil.** `storage/app/backups/` — `storage/app/.gitignore`
  onsuz da hər şeyi bağlayır. Endirmə `BackupController::download()`-dandır və
  `BackupPolicy::view()` yoxlanılır.
- **Silmə öz route-undadır.** Panelin ümumi `gopanel.general.delete` ünvanı
  modul icazəsini yoxlamır; backup arxivi bütün bazanı ehtiva etdiyi üçün
  `gopanel.backup.delete` route-u və policy işlədilir.
- **Disk yoxlaması.** Avtomatik silmə YOXDUR (köhnə arxivlər əl ilə silinir),
  ona görə başlamazdan əvvəl `min_free_space` yoxlanılır — disk dolub saytı
  dayandırmasın.

---

## 6. Fayl icazələri (Linux serverdə MÜTLƏQ oxu)

Arxivi **bir istifadəçi yaradır** (queue worker), **başqası oxuyur və silir**
(veb server). Laravel-in `local` diski qovluğu `0700` yaradır - belə olanda
panel hazır arxivi «Fayl yoxdur» kimi göstərir.

Ona görə qovluqlar `Storage::makeDirectory()` ilə YOX,
`BackupService::ensureFolder()` ilə yaradılır (`2770` + setgid), yazılmış fayla
isə `protectFile()` (`0640`) tətbiq olunur.

Tam izah, diaqnostika və serverdə birdəfəlik düzəliş əmrləri:
**[BACKUP_PERMISSIONS.md](BACKUP_PERMISSIONS.md)**

---

## 7. Tez-tez rast gəlinən problemlər

| Əlamət | Səbəb və həll |
|---|---|
| «Fayl yoxdur» nişanı (vəziyyət «Hazır») | Arxivi BAŞQA quraşdırmanın işçisi yaradıb. Sətrin üstünə gətirin — `meta.ran_on` hansı qovluqda/hostda icra olunduğunu göstərir. İki nüsxə eyni bazaya baxırsa növbələri ayırın. |
| `mysqldump: Unknown table 'column_statistics'` | Server MariaDB, binary isə MySQL 8-dir. `BACKUP_MYSQLDUMP_BINARY`-ni serverlə eyni ailədən olan binary-yə yönəldin (OpenServer: `C:/OpenServer/modules/database/MariaDB-10.3/bin/mysqldump.exe`). |
| `'mysqldump' is not recognized` | Binary PATH-də yoxdur — `.env`-də tam yol yazın. |
| «Yeni fayl yoxdur» xətası | Bu xəta deyil: sonuncu fayl backup-ından bəri heç nə yüklənməyib. Qeyd yaradılmır ki, siyahıda yalançı «Xəta» sətri görünməsin. |
| Backup «Növbədə» qalır | Növbə işçisi işləmir (`QUEUE_CONNECTION=database` olub `queue:work` başlamayıb). |
| «Diskdə yer azdır» | `min_free_space` həddi. Köhnə arxivləri silin və ya həddi dəyişin. |

---

## 8. Bərpa (restore)

```bash
# Baza
gunzip < db-<baza>-<tarix>.sql.gz | mysql -u <user> -p <baza>

# Fayllar — KÖHNƏDƏN YENİYƏ doğru, hamısı eyni qovluğa
unzip -o files-2026-01-01-000000-full.zip        -d public/
unzip -o files-2026-02-01-000000-incremental.zip -d public/
```

Arxivin içindəki yollar `site/...` ilə başlayır, ona görə açılış nöqtəsi
`public/` qovluğudur.
