<?php

namespace Tests\Feature\Gopanel;

use App\Enums\Gopanel\BackupStatus;
use App\Enums\Gopanel\BackupType;
use App\Models\Backup\Backup;
use App\Support\Files\ByteSize;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BackupModuleTest extends TestCase
{
    public function test_backup_model_configuration_is_ready(): void
    {
        $backup = new Backup();

        $this->assertSame('backups', $backup->getTable());

        foreach (['uid', 'type', 'mode', 'status', 'file_name', 'path', 'size', 'admin_id', 'meta'] as $column) {
            $this->assertContains($column, $backup->getFillable());
        }

        $this->assertInstanceOf(BelongsTo::class, $backup->admin());
    }

    public function test_backup_enums_expose_titles_and_tones(): void
    {
        $this->assertSame('Baza', BackupType::Database->title());
        $this->assertSame('Fayllar', BackupType::Files->title());
        $this->assertSame(['database', 'files'], BackupType::values());
        $this->assertSame('files', BackupType::Files->folder());

        $this->assertSame('Hazır', BackupStatus::Completed->title());
        $this->assertSame('success', BackupStatus::Completed->tone());
        $this->assertTrue(BackupStatus::Running->inProgress());
        $this->assertFalse(BackupStatus::Completed->inProgress());
    }

    public function test_archive_is_not_downloadable_when_file_is_missing(): void
    {
        $backup = new Backup([
            'type'   => BackupType::Database->value,
            'status' => BackupStatus::Completed->value,
            'path'   => 'backups/database/does-not-exist.sql.gz',
        ]);

        $this->assertFalse($backup->archiveExists());
        $this->assertFalse($backup->isDownloadable());
        $this->assertSame('backups/database/does-not-exist.sql.gz.files.json', $backup->manifestPath());
    }

    public function test_backup_routes_are_declared(): void
    {
        foreach (['index', 'start', 'status', 'download', 'delete'] as $action) {
            $this->assertTrue(
                Route::has('gopanel.backup.' . $action),
                'gopanel.backup.' . $action . ' route-u yoxdur'
            );
        }
    }

    public function test_backup_permissions_and_sidebar_are_registered(): void
    {
        $names = collect(config('gopanel.permission_list.gopanel.Backup'))->pluck('name')->all();

        $this->assertContains('gopanel.backup.index', $names);
        $this->assertContains('gopanel.backup.add', $names);
        $this->assertContains('gopanel.backup.delete', $names);

        // Backup-da redaktə anlayışı yoxdur - arxiv yarandıqdan sonra dəyişmir
        $this->assertNotContains('gopanel.backup.edit', $names);

        $sidebar = collect(config('gopanel.sidebar_menu_list'));
        $this->assertTrue($sidebar->contains(fn ($item) => ($item['route'] ?? null) === 'gopanel.backup.index'));
    }

    public function test_backup_config_keeps_archives_outside_public_folder(): void
    {
        $this->assertSame('backups', config('gopanel.backup.root'));
        $this->assertStringContainsString('site', (string) config('gopanel.backup.files_source'));
        $this->assertGreaterThan(0, (int) config('gopanel.backup.min_free_space'));
        $this->assertGreaterThan(0, (int) config('gopanel.backup.job_timeout'));

        // Parol nə config-də, nə də əmr sətrində olmamalıdır
        $options = (array) config('gopanel.backup.mysqldump_options');
        foreach ($options as $option) {
            $this->assertStringNotContainsString('--password', $option);
        }
    }

    public function test_archive_folders_are_group_readable_and_setgid(): void
    {
        $mode = (int) config('gopanel.backup.directory_permission');

        // setgid biti — qrup valideyndən miras qalsın
        $this->assertSame(02000, $mode & 02000, 'setgid biti yoxdur');
        // qrupa oxu + YAZMA — yazma olmasa fayl silinə bilmir
        $this->assertSame(060, $mode & 060, 'qrupa rw hüququ yoxdur');
        // «others» heç nə görməməlidir — arxivdə bütün baza var
        $this->assertSame(0, $mode & 07, 'kənar istifadəçiyə hüquq verilib');

        $fileMode = (int) config('gopanel.backup.file_permission');
        $this->assertSame(040, $fileMode & 060, 'qrup arxivi oxuya bilmir');
        $this->assertSame(0, $fileMode & 07, 'arxiv «others» üçün oxunaqlıdır');
    }

    public function test_jobs_do_not_use_storage_make_directory(): void
    {
        // `Storage::makeDirectory()` qovluğu 0700 yaradır və veb server
        // arxivi görmür (bax: docs/BACKUP_PERMISSIONS.md)
        foreach (['CreateDatabaseBackup', 'CreateFilesBackup'] as $job) {
            $code = file_get_contents(base_path("app/Jobs/Backup/{$job}.php"));

            // (şərhdə adı çəkilə bilər - burada ÇAĞIRIŞ axtarılır)
            $this->assertStringNotContainsString('->makeDirectory(', $code);
            $this->assertStringContainsString('ensureFolder', $code);
            $this->assertStringContainsString('protectFile', $code);
        }
    }

    public function test_backup_job_carries_only_the_id(): void
    {
        $job = file_get_contents(base_path('app/Jobs/Backup/BackupJob.php'));

        $this->assertStringContainsString('public int $backupId', $job);
        $this->assertStringContainsString('public int $tries = 1', $job);
    }

    public function test_byte_size_formats_human_readable_values(): void
    {
        $this->assertSame('0 B', ByteSize::human(0));
        $this->assertSame('0 B', ByteSize::human(-5));
        $this->assertSame('512 B', ByteSize::human(512));
        $this->assertSame('1 KB', ByteSize::human(1024));
        $this->assertSame('1.5 MB', ByteSize::human((int) (1.5 * 1024 * 1024)));
        $this->assertSame('—', ByteSize::humanOrDash(0));
    }
}
