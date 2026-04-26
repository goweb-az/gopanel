<?php

/*
|--------------------------------------------------------------------------
| Gopanel Avtomatik Yeniləmə Sistemi (Auto-Updater)
|--------------------------------------------------------------------------
|
| Bu sistem Gopanel admin panelin GitHub repo-dan avtomatik yeniləməsini
| təmin edir. İş prinsipi:
|
| 1. MANIFEST (gopanel_updates.json):
|    - GitHub repo-nun kök qovluğunda saxlanılır
|    - Hər yeni versiya üçün dəyişdirilmiş faylların siyahısını ehtiva edir
|    - Format: { "current_version": "1.0.1", "updates": [{ "version": "...", "files": [...] }] }
|
| 2. LOKAl VERSİYA (gopanel_version.json):
|    - Layihənin kök qovluğunda saxlanılır (git-ə əlavə olunmur)
|    - Cari qurulmuş versiyanı, son commit SHA-nı və yeniləmə tarixçəsini saxlayır
|    - Bu fayl vasitəsilə hansı yeniləmələrin artıq tətbiq edildiyini müəyyən edir
|
| 3. YENİLƏMƏ AXINI:
|    a) "Yeniləmələri yoxla" → GitHub API ilə manifest oxunur
|    b) Lokal versiya ilə müqayisə → yeni versiyalar filtr olunur
|    c) Hər fayl üçün konflikt yoxlanılır (lokal dəyişiklik varmı?)
|    d) İstifadəçi faylları seçir → diff ilə fərqə baxa bilər
|    e) "Qur" düyməsi → seçilmiş fayllar GitHub-dan yüklənir
|       - Əvvəlcə mövcud faylların backup-ı alınır (storage/app/gopanel-backups/)
|       - Sonra yeni versiya faylları yazılır
|    f) Tarixçədə hər yeniləmə qeydə alınır (kim, nə vaxt, hansı fayllar)
|
| 4. GERİ ALMA (Rollback):
|    - Bütün yeniləmə geri alına bilər (bütün fayllar backup-dan qaytarılır)
|    - Tək-tək fayllar da ayrıca geri qaytarıla bilər
|    - Tarixçədə diff düyməsi ilə backup vs mövcud versiya müqayisə olunur
|
| 5. KONFLİKT YOXLAMASI:
|    - Əgər istifadəçi faylı yerli olaraq dəyişibsə (base commit-dən fərqlidirsə)
|      fayl "conflict" statusu alır və xəbərdarlıq göstərilir
|    - İstifadəçi yenə də yeniləyə bilər, amma diqqətli olmalıdır
|
| QEYD: GitHub token olmadan saatda 60 API sorğusu limiti var.
|       Token əlavə etməklə bu limit 5000-ə çatır.
|       Token üçün .env faylına GOPANEL_GITHUB_TOKEN dəyişənini əlavə edin.
|
*/

return [
    // Yeniləmə sistemini aktiv/deaktiv etmək
    'enabled' => env('GOPANEL_UPDATER_ENABLED', true),

    // GitHub repo konfiqurasiyası
    'github' => [
        'owner'  => env('GOPANEL_GITHUB_OWNER', 'goweb-az'),   // GitHub istifadəçi/təşkilat adı
        'repo'   => env('GOPANEL_GITHUB_REPO', 'gopanel'),      // Repo adı
        'branch' => env('GOPANEL_GITHUB_BRANCH', 'master'),     // Yeniləmə branch-ı (master = stabil)
        'token'  => env('GOPANEL_GITHUB_TOKEN', null),           // GitHub Personal Access Token (rate limit artırmaq üçün)
    ],

    // Backup qovluğu — yeniləmədən əvvəl köhnə fayllar burada saxlanılır
    'backup_path'  => storage_path('app/gopanel-backups'),

    // Lokal versiya faylı — cari qurulmuş versiya və tarixçə burada saxlanılır
    'version_file' => base_path('gopanel_version.json'),

    // Manifest fayl adı — GitHub repo-da bu adla axtarılır
    'manifest'     => 'gopanel_updates.json',
];
