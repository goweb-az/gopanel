<?php

namespace App\Http\Controllers\Gopanel\System;

use App\Http\Controllers\GoPanelController;
use App\Services\Gopanel\GitHubUpdateService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class UpdateController extends GoPanelController
{
    protected GitHubUpdateService $updateService;

    public function __construct()
    {
        parent::__construct();
        $this->updateService = new GitHubUpdateService();
    }

    /**
     * Yeniləmələr səhifəsi
     */
    public function index()
    {
        $localVersion = $this->updateService->getLocalVersion();

        $localVersion['last_checked_at_formatted'] = !empty($localVersion['last_checked_at'])
            ? Carbon::parse($localVersion['last_checked_at'])->format('d.m.Y H:i')
            : null;

        // Tarixçədəki tarixləri formatla
        if (!empty($localVersion['update_history'])) {
            foreach ($localVersion['update_history'] as &$history) {
                $history['date_formatted'] = Carbon::parse($history['date'])->format('d.m.Y H:i');
            }
        }

        return view('gopanel.pages.system.updates.index', compact('localVersion'));
    }

    /**
     * Yeniləmələri yoxla (AJAX)
     */
    public function check()
    {
        try {
            if (!config('gopanel.updater.enabled')) {
                $this->response['message'] = 'Yeniləmə sistemi deaktivdir';
                return $this->response_json();
            }

            $available = $this->updateService->getAvailableUpdates();
            if ($available === null) {
                $this->response['message'] = 'GitHub ilə əlaqə qurmaq mümkün olmadı';
                return $this->response_json();
            }

            // Hər yeniləmənin faylları üçün konflikt yoxla
            $localVersion = $this->updateService->getLocalVersion();
            $installedCommit = $localVersion['installed_commit'] ?? null;

            foreach ($available['updates'] as &$update) {
                $update['files_status'] = $this->updateService->checkFileConflicts(
                    $update['files'],
                    $installedCommit
                );
            }

            // Son yoxlama tarixini yenilə
            $localVersion['last_checked_at'] = now()->toIso8601String();
            $this->updateService->saveLocalVersion($localVersion);

            $this->success_response($available, 'Yeniləmələr yoxlandı');
        } catch (Exception $e) {
            $this->response['message'] = 'Xəta: ' . $e->getMessage();
        }

        return $this->response_json();
    }

    /**
     * Faylın diff-ini göstər (AJAX)
     */
    public function diff(Request $request)
    {
        try {
            $path = $request->input('path');
            if (!$path) {
                $this->response['message'] = 'Fayl yolu göstərilməyib';
                return $this->response_json();
            }

            $diff = $this->updateService->getFileDiff($path);
            $this->success_response($diff, 'Diff hazırdır');
        } catch (Exception $e) {
            $this->response['message'] = 'Xəta: ' . $e->getMessage();
        }

        return $this->response_json();
    }

    /**
     * Seçilmiş faylları yenilə (AJAX)
     */
    public function apply(Request $request)
    {
        try {
            $files = $request->input('files', []);
            $version = $request->input('version');

            if (empty($files) || !$version) {
                $this->response['message'] = 'Fayl və ya versiya göstərilməyib';
                return $this->response_json();
            }

            $result = $this->updateService->applyFiles($files, $version, [
                'admin_name' => auth()->user()->name ?? 'Admin',
                'description' => $request->input('description', ''),
            ]);

            if (!empty($result['errors'])) {
                $this->response['message'] = count($result['errors']) . ' faylda xəta baş verdi';
                $this->response['data'] = $result;
                $this->response_code = 207; // Partial success
                return $this->response_json();
            }

            // Cache təmizlə
            try {
                Artisan::call('cache:clear');
                Artisan::call('view:clear');
                Artisan::call('optimize:clear');
            } catch (Exception $e) {
                // Cache clear uğursuz olsa da, yeniləmə uğurludur
            }

            $this->success_response($result, count($result['updated_files']) . ' fayl uğurla yeniləndi');
        } catch (Exception $e) {
            $this->response['message'] = 'Xəta: ' . $e->getMessage();
        }

        return $this->response_json();
    }

    /**
     * Backup-dan geri al (AJAX)
     */
    public function rollback(Request $request)
    {
        try {
            $backupId = $request->input('backup_id');
            if (!$backupId) {
                $this->response['message'] = 'Backup ID göstərilməyib';
                return $this->response_json();
            }

            $result = $this->updateService->rollback($backupId);

            if (!$result['success']) {
                $this->response['message'] = $result['error'] ?? 'Geri alma xətası';
                return $this->response_json();
            }

            // Cache təmizlə
            try {
                Artisan::call('cache:clear');
                Artisan::call('view:clear');
                Artisan::call('optimize:clear');
            } catch (Exception $e) {
                // ignore
            }

            $this->success_response($result, count($result['restored_files']) . ' fayl geri qaytarıldı');
        } catch (Exception $e) {
            $this->response['message'] = 'Xəta: ' . $e->getMessage();
        }

        return $this->response_json();
    }

    /**
     * Tarixçədəki faylın backup vs cari müqayisəsi (AJAX)
     */
    public function historyDiff(Request $request)
    {
        try {
            $backupId = $request->input('backup_id');
            $path = $request->input('path');

            if (!$backupId || !$path) {
                $this->response['message'] = 'backup_id və path lazımdır';
                return $this->response_json();
            }

            $backupDir = config('gopanel.updater.backup_path');
            $backupFilePath = "{$backupDir}/{$backupId}/{$path}";
            $localPath = base_path($path);

            $backupContent = File::exists($backupFilePath)
                ? File::get($backupFilePath)
                : null;

            $currentContent = File::exists($localPath)
                ? File::get($localPath)
                : null;

            $this->success_response([
                'path' => $path,
                'backup_content' => $backupContent,
                'current_content' => $currentContent,
                'backup_exists' => $backupContent !== null,
                'current_exists' => $currentContent !== null,
            ], 'Diff hazırdır');
        } catch (Exception $e) {
            $this->response['message'] = 'Xəta: ' . $e->getMessage();
        }

        return $this->response_json();
    }

    /**
     * Tək faylı backup-dan geri al (AJAX)
     */
    public function rollbackFile(Request $request)
    {
        try {
            $backupId = $request->input('backup_id');
            $path = $request->input('path');

            if (!$backupId || !$path) {
                $this->response['message'] = 'backup_id və path lazımdır';
                return $this->response_json();
            }

            $backupDir = config('gopanel.updater.backup_path');
            $backupFilePath = "{$backupDir}/{$backupId}/{$path}";

            if (!File::exists($backupFilePath)) {
                $this->response['message'] = 'Backup faylı tapılmadı';
                return $this->response_json();
            }

            $targetPath = base_path($path);
            File::ensureDirectoryExists(dirname($targetPath));
            File::copy($backupFilePath, $targetPath);

            $this->success_response(['path' => $path], "{$path} uğurla geri qaytarıldı");
        } catch (Exception $e) {
            $this->response['message'] = 'Xəta: ' . $e->getMessage();
        }

        return $this->response_json();
    }
}
