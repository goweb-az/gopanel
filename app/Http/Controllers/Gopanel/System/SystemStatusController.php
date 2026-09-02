<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gopanel\System;

use App\Http\Controllers\GoPanelController;
use App\Services\Gopanel\System\SystemStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * «Sistem vəziyyəti» — serverin anlıq monitorunu göstərir.
 *
 * Səhifə iki hissədən ibarətdir:
 *   1. Qrafiklər (CPU, yaddaş, disk) — JS-ə yalnız rəqəm gedir.
 *   2. Qalan bütün bloklar — serverdə blade ilə render olunur və hazır HTML
 *      kimi göndərilir. Beləliklə formatlaşdırma tək yerdə (blade-də) qalır
 *      (bax: 01-umumi.md § 3).
 *
 * İcazə həm route middleware-i, həm də burada yoxlanılır: bölmə serverin
 * daxili məlumatını (baza adı, yollar, versiyalar) açır.
 */
class SystemStatusController extends GoPanelController
{
    public function __construct(private readonly SystemStatusService $service)
    {
        parent::__construct();
    }

    public function index(): View
    {
        $this->authorizeSection();

        $status = $this->service->snapshot();

        return view('gopanel.pages.system_status.index', [
            'status'     => $status,
            'gauges'     => $this->service->gauges($status),
            'crontab'    => $this->service->crontab(),
            'refreshMs'  => (int) config('gopanel.system_status.refresh_ms'),
            'historyMax' => (int) config('gopanel.system_status.history_points'),
        ]);
    }

    /** Səhifənin avtomatik yenilənməsi — canlı hissə yenidən qurulur. */
    public function data(): JsonResponse
    {
        $this->authorizeSection();

        $status = $this->service->snapshot();

        $this->success_response([
            'gauges'     => $this->service->gauges($status),
            'checked_at' => $status['checked_at'],
            'html'       => [
                // Qrafiklərin altındakı təfərrüat sətirləri ayrıca gəlir ki,
                // ApexCharts obyektləri hər yenilənmədə yenidən qurulmasın.
                'cpu'    => $this->gaugeMeta($status['cpu']),
                'memory' => $this->gaugeMeta($status['memory']),
                'disk'   => $this->gaugeMeta($status['disk']),
                'live'   => view('gopanel.pages.system_status.partials.live', [
                    'status' => $status,
                ])->render(),
            ],
        ], 'Vəziyyət alındı');

        return $this->response_json();
    }

    private function authorizeSection(): void
    {
        /** @var \App\Models\Gopanel\Admin|null $admin */
        $admin = auth('gopanel')->user();

        abort_unless(
            (bool) $admin?->can('gopanel.system-status.index'),
            403,
            'Bu bölməyə icazəniz yoxdur.'
        );
    }

    /** Qrafikin altındakı «etiket → dəyər» sətirləri. */
    private function gaugeMeta(array $metric): string
    {
        return view('gopanel.pages.system_status.partials.gauge-meta', [
            'metric' => $metric,
        ])->render();
    }
}
