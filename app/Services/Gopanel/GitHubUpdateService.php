<?php

namespace App\Services\Gopanel;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class GitHubUpdateService
{
    protected string $owner;
    protected string $repo;
    protected string $branch;
    protected ?string $token;
    protected string $apiBase = 'https://api.github.com';

    public function __construct()
    {
        $config = config('gopanel.updater.github');
        $this->owner  = $config['owner'];
        $this->repo   = $config['repo'];
        $this->branch = $config['branch'];
        $this->token  = $config['token'];
    }

    /**
     * GitHub API sorğusu üçün HTTP client
     */
    protected function request()
    {
        $http = Http::withHeaders([
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'Gopanel-Updater',
        ]);

        if ($this->token) {
            $http = $http->withToken($this->token);
        }

        return $http;
    }

    /**
     * GitHub-dan manifest faylını (gopanel_updates.json) oxu
     */
    public function getManifest(): ?array
    {
        try {
            $manifestFile = config('gopanel.updater.manifest');
            $response = $this->request()->get(
                "{$this->apiBase}/repos/{$this->owner}/{$this->repo}/contents/{$manifestFile}",
                ['ref' => $this->branch]
            );

            if (!$response->successful()) {
                if ($response->status() === 404) {
                    Log::info('Gopanel Updater: Manifest fayl GitHub repo-da tapılmadı (hələ push edilməyib)');
                    return ['current_version' => '0.0.0', 'updates' => []];
                }
                Log::error('Gopanel Updater: GitHub API xətası', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $content = base64_decode($response->json('content'));
            return json_decode($content, true);
        } catch (\Exception $e) {
            Log::error('Gopanel Updater: Manifest oxuma xətası', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Master branch-ın son commit SHA-sını al
     */
    public function getLatestCommitSha(): ?string
    {
        try {
            $response = $this->request()->get(
                "{$this->apiBase}/repos/{$this->owner}/{$this->repo}/commits/{$this->branch}"
            );

            if (!$response->successful()) {
                return null;
            }

            return $response->json('sha');
        } catch (\Exception $e) {
            Log::error('Gopanel Updater: Commit SHA xətası', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * GitHub-dan konkret faylın məzmununu yüklə
     */
    public function getFileContent(string $path, string $ref = null): ?string
    {
        try {
            $ref = $ref ?? $this->branch;
            $response = $this->request()->get(
                "{$this->apiBase}/repos/{$this->owner}/{$this->repo}/contents/{$path}",
                ['ref' => $ref]
            );

            if (!$response->successful()) {
                return null;
            }

            return base64_decode($response->json('content'));
        } catch (\Exception $e) {
            Log::error('Gopanel Updater: Fayl yükləmə xətası', [
                'path'  => $path,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Lokal versiya faylını oxu
     */
    public function getLocalVersion(): array
    {
        $versionFile = config('gopanel.updater.version_file');

        if (!File::exists($versionFile)) {
            return [
                'installed_version' => '0.0.0',
                'installed_commit'  => null,
                'installed_at'      => null,
                'last_checked_at'   => null,
                'last_updated_at'   => null,
                'update_history'    => [],
            ];
        }

        return json_decode(File::get($versionFile), true);
    }

    /**
     * Lokal versiya faylını yenilə
     */
    public function saveLocalVersion(array $data): void
    {
        $versionFile = config('gopanel.updater.version_file');
        File::put($versionFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Manifesti oxu və mövcud versiyadan sonrakı yeniləmələri filtr et
     */
    public function getAvailableUpdates(): ?array
    {
        $manifest = $this->getManifest();
        if (!$manifest) {
            return null;
        }

        $localVersion = $this->getLocalVersion();
        $installedVersion = $localVersion['installed_version'] ?? '0.0.0';

        // installed_version-dan böyük olan yeniləmələri filtr et
        $availableUpdates = array_filter($manifest['updates'] ?? [], function ($update) use ($installedVersion) {
            return version_compare($update['version'], $installedVersion, '>');
        });

        // Versiyaya görə sırala (kiçikdən böyüyə)
        usort($availableUpdates, function ($a, $b) {
            return version_compare($a['version'], $b['version']);
        });

        return [
            'current_version'   => $installedVersion,
            'latest_version'    => $manifest['current_version'],
            'has_updates'       => !empty($availableUpdates),
            'updates'           => array_values($availableUpdates),
        ];
    }

    /**
     * Hər fayl üçün konflikt statusunu yoxla
     * Lokal fayl base (installed_commit) versiyası ilə müqayisə olunur
     */
    public function checkFileConflicts(array $files, ?string $installedCommit): array
    {
        $result = [];

        foreach ($files as $file) {
            $path   = $file['path'];
            $action = $file['action'];
            $localPath = base_path($path);

            $status = [
                'path'         => $path,
                'action'       => $action,
                'has_conflict' => false,
                'local_exists' => File::exists($localPath),
                'status'       => 'safe', // safe, conflict, new, delete
            ];

            if ($action === 'added') {
                // Yeni fayl — lokal mövcuddursa konflikt ola bilər
                $status['status'] = File::exists($localPath) ? 'conflict' : 'new';
                $status['has_conflict'] = File::exists($localPath);
            } elseif ($action === 'deleted') {
                $status['status'] = 'delete';
                $status['has_conflict'] = false;
            } elseif ($action === 'modified') {
                if (!File::exists($localPath)) {
                    // Lokal fayl yoxdur — təhlükəsiz yüklə
                    $status['status'] = 'safe';
                } elseif ($installedCommit) {
                    // Base versiya ilə müqayisə et
                    $baseContent = $this->getFileContent($path, $installedCommit);
                    $localContent = File::get($localPath);

                    if ($baseContent !== null && md5($localContent) !== md5($baseContent)) {
                        // Lokal fayl base-dən fərqlidir — istifadəçi dəyişib
                        $status['status'] = 'conflict';
                        $status['has_conflict'] = true;
                    } else {
                        $status['status'] = 'safe';
                    }
                } else {
                    // installed_commit yoxdur, müqayisə edə bilmirik — xəbərdarlıq
                    $status['status'] = 'conflict';
                    $status['has_conflict'] = true;
                }
            }

            $result[] = $status;
        }

        return $result;
    }

    /**
     * Faylın diff məlumatını al (lokal vs uzaq)
     */
    public function getFileDiff(string $path): array
    {
        $localPath = base_path($path);
        $localContent = File::exists($localPath) ? File::get($localPath) : null;
        $remoteContent = $this->getFileContent($path);

        return [
            'path'          => $path,
            'local_content'  => $localContent,
            'remote_content' => $remoteContent,
            'local_exists'   => $localContent !== null,
            'remote_exists'  => $remoteContent !== null,
        ];
    }

    /**
     * Faylları yenilə (backup + yüklə)
     */
    public function applyFiles(array $filePaths, string $targetVersion, array $meta = []): array
    {
        $backupDir = config('gopanel.updater.backup_path');
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupPath = "{$backupDir}/{$timestamp}";

        $results = [
            'backup_id'     => $timestamp,
            'updated_files' => [],
            'errors'        => [],
        ];

        foreach ($filePaths as $file) {
            $path   = $file['path'];
            $action = $file['action'];
            $localPath = base_path($path);

            try {
                // Backup
                if (File::exists($localPath)) {
                    $backupFilePath = "{$backupPath}/{$path}";
                    File::ensureDirectoryExists(dirname($backupFilePath));
                    File::copy($localPath, $backupFilePath);
                }

                if ($action === 'deleted') {
                    if (File::exists($localPath)) {
                        File::delete($localPath);
                    }
                    $results['updated_files'][] = ['path' => $path, 'action' => 'deleted'];
                } else {
                    // added və ya modified — GitHub-dan yüklə
                    $content = $this->getFileContent($path);
                    if ($content === null) {
                        $results['errors'][] = ['path' => $path, 'error' => 'GitHub-dan fayl yüklənə bilmədi'];
                        continue;
                    }

                    File::ensureDirectoryExists(dirname($localPath));
                    File::put($localPath, $content);
                    $results['updated_files'][] = ['path' => $path, 'action' => $action];
                }
            } catch (\Exception $e) {
                $results['errors'][] = ['path' => $path, 'error' => $e->getMessage()];
            }
        }

        // Versiya faylını yenilə
        $localVersion = $this->getLocalVersion();
        $latestCommit = $this->getLatestCommitSha();

        $localVersion['installed_version'] = $targetVersion;
        $localVersion['installed_commit']  = $latestCommit;
        $localVersion['last_updated_at']   = now()->toIso8601String();
        $localVersion['update_history'][]  = [
            'version'      => $targetVersion,
            'backup_id'    => $timestamp,
            'date'         => now()->toIso8601String(),
            'files'        => count($results['updated_files']),
            'file_details' => $results['updated_files'],
            'applied_by'   => $meta['admin_name'] ?? 'System',
            'description'  => $meta['description'] ?? '',
        ];

        $this->saveLocalVersion($localVersion);

        // Backup meta
        $metaPath = "{$backupPath}/_backup_meta.json";
        File::ensureDirectoryExists($backupPath);
        File::put($metaPath, json_encode([
            'version'          => $targetVersion,
            'previous_version' => $localVersion['installed_version'] ?? '0.0.0',
            'date'             => now()->toIso8601String(),
            'files'            => $results['updated_files'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $results;
    }

    /**
     * Backup-dan geri al
     */
    public function rollback(string $backupId): array
    {
        $backupDir = config('gopanel.updater.backup_path');
        $backupPath = "{$backupDir}/{$backupId}";

        if (!File::isDirectory($backupPath)) {
            return ['success' => false, 'error' => 'Backup tapılmadı'];
        }

        $metaPath = "{$backupPath}/_backup_meta.json";
        $meta = File::exists($metaPath) ? json_decode(File::get($metaPath), true) : null;

        $restoredFiles = [];
        $errors = [];

        // Backup-dakı bütün faylları geri qoytur (meta istisna)
        $files = File::allFiles($backupPath);
        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            if ($relativePath === '_backup_meta.json') {
                continue;
            }

            try {
                $targetPath = base_path($relativePath);
                File::ensureDirectoryExists(dirname($targetPath));
                File::copy($file->getPathname(), $targetPath);
                $restoredFiles[] = $relativePath;
            } catch (\Exception $e) {
                $errors[] = ['path' => $relativePath, 'error' => $e->getMessage()];
            }
        }

        // Versiya faylını geri qaytar
        if ($meta && isset($meta['previous_version'])) {
            $localVersion = $this->getLocalVersion();
            $localVersion['installed_version'] = $meta['previous_version'];
            $localVersion['last_updated_at'] = now()->toIso8601String();
            $this->saveLocalVersion($localVersion);
        }

        return [
            'success'        => empty($errors),
            'restored_files' => $restoredFiles,
            'errors'         => $errors,
        ];
    }
}
