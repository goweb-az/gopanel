<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gopanel\Backup;

use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Backup\BackupStartRequest;
use App\Models\Backup\Backup;
use App\Repositories\Gopanel\BackupRepository;
use App\Services\Gopanel\Backup\BackupService;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Backup bölməsi - bazanın və paneldən yüklənən faylların arxivi.
 *
 * Arxivlər `storage/app/backups/` altındadır: nə git-ə düşür, nə də birbaşa
 * URL ilə açılır. Endirmə yalnız buradan, icazə yoxlaması ilə mümkündür.
 */
class BackupController extends GoPanelController
{
    public function __construct(
        private readonly BackupService $service,
        private readonly BackupRepository $repository,
    ) {
        parent::__construct();
    }

    public function index()
    {
        return view('gopanel.pages.backup.index', [
            'summary' => $this->service->summary(),
            'can'     => $this->permissions(),
        ]);
    }

    /** Yeni backup növbəyə salınır - ağır iş sorğunun içində icra olunmur. */
    public function start(BackupStartRequest $request)
    {
        try {
            $backup = $this->service->start(
                $request->backupType(),
                auth('gopanel')->id()
            );

            $this->success_response(
                $backup,
                $request->backupType()->title() . ' backup-ı növbəyə salındı. Hazır olanda siyahıda görünəcək.'
            );
        } catch (RuntimeException $e) {
            // İstifadəçiyə göstərilə bilən səbəb (yer azdır, artıq işləyir...)
            $this->response['message'] .= $e->getMessage();
        } catch (Throwable $e) {
            $this->response['message'] .= 'Backup başladıla bilmədi: ' . $e->getMessage();
        }

        return $this->response_json();
    }

    /**
     * Siyahının avtomatik yenilənməsi üçün - hazırda işləyən backup varmı.
     * JS bunu yalnız işləyən backup olanda soruşur (bax: js/modules/backup.js).
     */
    public function status()
    {
        $this->success_response([
            'in_progress' => $this->service->summary()['in_progress'],
        ], 'Vəziyyət alındı');

        return $this->response_json();
    }

    public function download(Backup $item): BinaryFileResponse
    {
        Gate::forUser(auth('gopanel')->user())->authorize('view', $item);

        abort_unless($item->isDownloadable(), 404, 'Arxiv tapılmadı və ya hələ hazır deyil.');

        return response()->download($item->absolutePath(), $item->file_name);
    }

    /**
     * Qeyd və onunla birlikdə arxiv faylı silinir (model `deleting` hadisəsi).
     *
     * Panelin ümumi `general.delete` ünvanı işlədilmir: o, modul icazəsini
     * yoxlamır, backup arxivi isə bütün bazanı ehtiva edir.
     */
    public function delete(Backup $item)
    {
        try {
            Gate::forUser(auth('gopanel')->user())->authorize('delete', $item);

            $this->repository->delete($item);

            $this->success_response([], 'Backup silindi.');
        } catch (Throwable $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }

    /** Blade-ə hazır icazə bayraqları - `@can` gopanel guard-ı ilə işləmir. */
    private function permissions(): array
    {
        $admin = auth('gopanel')->user();

        return [
            'add'    => (bool) $admin?->can('gopanel.backup.add'),
            'delete' => (bool) $admin?->can('gopanel.backup.delete'),
        ];
    }
}
