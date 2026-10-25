<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Jobs\Export\SendExportMailJob;
use App\Support\Export\ExportRegistry;
use App\Support\Export\ExportRenderer;
use App\Support\Export\PdfExporter;
use App\Support\Export\PdfUnavailableException;
use Illuminate\Contracts\Auth\Authenticatable;
use RuntimeException;

/**
 * İxracın mail ilə göndərilməsi - bütün bölmələrin işlətdiyi tək giriş nöqtəsi.
 *
 * Panel `POST gopanel/exports/mail` endpoint-inə müraciət edir, endpoint isə
 * buraya. Yeni bölməyə göndərmə əlavə etmək = `config/custom/export.php` →
 * `definitions`-a bir bənd + blade-də `gopanel.exports.buttons` partial-ına
 * açar vermək. Yeni controller, route və ya JS yazılmır.
 *
 * İcazə BURADA, sorğu anında yoxlanılır, job-da yox: queue-da `$admin->can()`
 * işləmir (`Gate::before` login tələb edir) - bax 02-gopanel.md § 8.
 */
final class ExportMailService
{
    /**
     * @param  array<string, mixed>  $params  ixracın süzgəcləri (siyahılarda) və ya `uid` (tək sənədlərdə)
     *
     * @throws RuntimeException icazə yoxdursa, açar tanınmırsa və ya PDF mümkün deyilsə
     */
    public function queue(
        string $key,
        array $params,
        string $format,
        string $email,
        Authenticatable $admin,
        ?string $note = null,
    ): string {
        if (!ExportRegistry::mailable($key)) {
            throw new RuntimeException('Bu sənəd mail ilə göndərilə bilmir.');
        }

        $this->authorize($key, $admin);

        $format = ExportRenderer::normalizeFormat($format);

        // Növbəyə düşməmiş imtina edilir - əks halda admin «göndərilir» görür,
        // job isə arxa planda sükutla yıxılır.
        if ($format === 'pdf' && !PdfExporter::isAvailable()) {
            throw PdfUnavailableException::missingPackage();
        }

        $job = new SendExportMailJob($key, $params, $format, $email, (int) $admin->getAuthIdentifier(), $note);

        $connection = config('custom.export.mail.queue_connection');
        $queue = config('custom.export.mail.queue');

        if (!empty($connection)) {
            $job->onConnection($connection);
        }

        if (!empty($queue)) {
            $job->onQueue($queue);
        }

        dispatch($job);

        return ExportRegistry::label($key);
    }

    /**
     * Endirmə ilə eyni icazə tələb olunur: mail endirmənin başqa formasıdır,
     * onunla icazə divarından yan keçmək olmamalıdır.
     *
     * @throws RuntimeException
     */
    public function authorize(string $key, Authenticatable $admin): void
    {
        $permission = ExportRegistry::permission($key);

        if ($permission !== null && !(method_exists($admin, 'can') && $admin->can($permission))) {
            throw new RuntimeException('Bu sənəd üçün icazəniz yoxdur.');
        }
    }
}
