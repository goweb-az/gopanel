<?php

declare(strict_types=1);

namespace App\Jobs\Export;

use App\Services\Activity\LogService;
use App\Services\Mail\MailService;
use App\Services\Mail\Templates\ExportMail;
use App\Support\Export\ExportRegistry;
use App\Support\Export\ExportRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * İxrac faylını qurub mail qoşması kimi göndərir.
 *
 * NİYƏ növbədə: böyük siyahının Excel/PDF-i on saniyələrlə çəkə bilir, SMTP
 * cavabı da gözlənilir - admin «Göndər» deyəndən sonra səhifə kilidlənməməlidir.
 *
 * Payload-da YALNIZ skalyarlar var (01-umumi.md § 1): reyestr açarı (sinif adı
 * yox - bax `ExportRegistry`), süzgəclər, format, ünvan və admin `id`-si.
 * İcazə job-dan ƏVVƏL `ExportMailService`-də yoxlanılıb.
 *
 * Bildiriş: starter-də admin bildiriş servisi yoxdur, ona görə nəticə
 * `export` jurnal kanalına yazılır. Törəmə layihədə bildiriş bölməsi varsa
 * (docs/rules/gopanel-notifications-module.md) `handle()`/`failed()` sonuna
 * `dispatch()` əlavə olunur.
 */
final class SendExportMailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    /**
     * @param  array<string, mixed>  $params
     */
    public function __construct(
        public readonly string $key,
        public readonly array $params,
        public readonly string $format,
        public readonly string $email,
        public readonly int $adminId,
        public readonly ?string $note = null,
    ) {
    }

    public function handle(ExportRenderer $renderer): void
    {
        $export = ExportRegistry::make($this->key, $this->params);
        $file = $renderer->render($export, $this->format);

        $details = array_filter([
            'Sənəd'  => $export->title(),
            'Format' => ExportRenderer::formatLabel($this->format),
            'Sətir'  => (string) $file->rowCount,
            'Tarix'  => now()->format('d.m.Y H:i'),
        ] + $export->appliedFilters(), static fn ($value): bool => $value !== '');

        // Job artıq növbədədir - MailService-in öz növbəsi açılmır.
        (new MailService($export->title()))->send($this->email, new ExportMail(
            title: $export->title(),
            body: 'Sorğunuza əsasən hazırlanan sənəd bu məktuba qoşulub.',
            file: $file,
            details: $details,
            note: $this->note,
        ));

        (new LogService('export', false))->info('İxrac mail ilə göndərildi: ' . $export->title(), [
            'key'      => $this->key,
            'format'   => $this->format,
            'email'    => $this->email,
            'admin_id' => $this->adminId,
            'rows'     => $file->rowCount,
            'size_kb'  => $file->sizeKb(),
        ]);
    }

    /**
     * Uğursuzluq jurnala düşür - admin sənədin getmədiyini oradan görür.
     * Jurnal xətası özü də udulur (01-umumi.md § 7).
     */
    public function failed(Throwable $exception): void
    {
        try {
            (new LogService('export', false))->error('İxracın mail ilə göndərilməsi alınmadı: ' . $exception->getMessage(), [
                'key'      => $this->key,
                'label'    => ExportRegistry::label($this->key),
                'email'    => $this->email,
                'admin_id' => $this->adminId,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
