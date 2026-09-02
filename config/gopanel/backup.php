<?php

/**
 * Panel backup bölməsinin tənzimləmələri.
 *
 * Arxivlər `storage/app/backups/` altındadır - `storage/app/.gitignore`
 * onsuz da hər şeyi bağlayır, ona görə git-ə düşmür və birbaşa URL ilə
 * açıla bilmir (public qovluğunda deyil). Endirmə yalnız panel route-u
 * üzərindən, icazə yoxlaması ilə mümkündür.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Yollar
    |--------------------------------------------------------------------------
    | `root` — `storage/app` daxilində nisbi yol (Storage `local` diski).
    | `files_source` — hansı qovluq arxivlənir. Panelin yüklədiyi bütün
    | fayllar `FileUploader::toPublic()` ilə `public/site/` altına düşür.
    */

    'root' => 'backups',

    'files_source' => public_path('site'),

    /*
    |--------------------------------------------------------------------------
    | mysqldump
    |--------------------------------------------------------------------------
    | Giriş məlumatları `config/database.php`-dən götürülür — burada parol
    | saxlanılmır. Parol əmr sətrinə də yazılmır: müvəqqəti `defaults-file`
    | yaradılır (0600), iş bitəndə silinir. Əks halda parol serverdə
    | `ps aux` çıxışında hər kəsə görünərdi.
    |
    | Windows-da (OpenServer) binary tam yolla göstərilir, məs.:
    | BACKUP_MYSQLDUMP_BINARY="C:/OpenServer/modules/database/MySQL-8.0/bin/mysqldump.exe"
    */

    'mysqldump_binary' => env('BACKUP_MYSQLDUMP_BINARY', 'mysqldump'),

    'mysqldump_options' => [
        '--single-transaction',   // cədvəlləri kilidləmir - sayt yazmağa davam edir
        '--quick',                // sətirləri yaddaşda yığmır
        '--routines',
        '--events',
        '--default-character-set=utf8mb4',
    ],

    /*
    |--------------------------------------------------------------------------
    | Təhlükəsizlik həddi
    |--------------------------------------------------------------------------
    | Backup başlamazdan əvvəl diskdə ən azı bu qədər boş yer olmalıdır.
    | Avtomatik silmə yoxdur (paneldən əl ilə silinir), ona görə bu yoxlama
    | diskin dolub saytı dayandırmasının qarşısını alır.
    */

    'min_free_space' => (int) env('BACKUP_MIN_FREE_SPACE', 2 * 1024 * 1024 * 1024),   // 2 GB

    /*
    |--------------------------------------------------------------------------
    | Vaxt limiti (saniyə)
    |--------------------------------------------------------------------------
    | İlk fayl arxivi 5+ GB ola bilər, ona görə uzundur. Növbə işçisindəki
    | `--timeout` dəyəri bundan kiçik olmamalıdır.
    */

    'job_timeout' => (int) env('BACKUP_JOB_TIMEOUT', 3600),

    /*
    |--------------------------------------------------------------------------
    | Fayl icazələri (Linux serverlərdə kritikdir)
    |--------------------------------------------------------------------------
    | Arxivi növbə işçisi (supervisor istifadəçisi, məs. `myproject`) yaradır,
    | paneldən isə veb server istifadəçisi (`www-data`) oxuyur. Laravel-in
    | `local` diski qovluğu 0700 ilə yaradır — belə olanda veb server qovluğa
    | girə bilmir və panel hazır arxivi «Fayl yoxdur» sayır.
    |
    | 2770 = sahib tam, QRUP oxuyub-yaza bilir, kənar istifadəçi ümumiyyətlə
    | girə bilmir. Arxivdə bütün baza olduğu üçün «others» hüququ bilərəkdən
    | verilmir.
    |
    | Qrupa YAZMA hüququ lazımdır: faylı silmək üçün onun özündə deyil,
    | QOVLUQDA yazma icazəsi tələb olunur — 2750 olsaydı paneldən sətir
    | silinər, arxiv isə diskdə sahibsiz qalardı.
    |
    | Başdakı «2» — setgid biti. O olmasa qovluğun içində yaranan hər yeni
    | fayl işçinin öz qrupunu alır və veb server yenə oxuya bilmir; setgid ilə
    | qrup valideyndən (`storage/app`) miras qalır. Diqqət: `chmod 770` yazmaq
    | bu biti SİLİR — əl ilə düzəldəndə `chmod 2770` işlədilir.
    |
    | Windows-da bu dəyərlərin praktiki təsiri yoxdur (NTFS `chmod`-u yox sayır).
    | Detallar: docs/BACKUP_PERMISSIONS.md
    */

    'directory_permission' => 02770,

    'file_permission' => 0640,

];
