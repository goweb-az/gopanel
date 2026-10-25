<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gopanel\Export;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gopanel\Export\ExportDownloadRequest;
use App\Http\Requests\Gopanel\Export\ExportMailRequest;
use App\Services\Activity\LogService;
use App\Services\Export\ExportMailService;
use App\Support\Export\ExportRegistry;
use App\Support\Export\ExportRenderer;
use App\Support\Export\PdfUnavailableException;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * İxracın **ortaq** endpoint-ləri: endirmə (Excel/PDF) və mail ilə göndərmə.
 *
 * NİYƏ ortaq: hər bölmə üçün ayrıca `export` route-u yazılmır - sənəd `key`
 * ilə seçilir (`config/custom/export.php` → `definitions`), icazə həmin
 * açarın öz icazəsidir. Yeni bölməyə ixrac əlavə etmək = bir tərif sinfi +
 * bir config bəndi + blade-də `gopanel.exports.buttons` partial-ı.
 *
 * Controller nazikdir: validasiya/icazə FormRequest-də, iş ExportRenderer və
 * ExportMailService-dədir.
 */
final class ExportController extends Controller
{
    public function download(ExportDownloadRequest $request, ExportRenderer $renderer): Response
    {
        $key = $request->exportKey();
        $format = $request->exportFormat();

        try {
            $export = ExportRegistry::make($key, $request->exportParams());
            $response = $renderer->download($export, $format);
        } catch (PdfUnavailableException $e) {
            return $this->fail($request->expectsJson(), $e->getMessage(), 422);
        }

        $this->log('İxrac endirildi: ' . $export->title(), [
            'key'      => $key,
            'format'   => $format,
            'admin_id' => $request->user('gopanel')?->getAuthIdentifier(),
            'filters'  => $export->appliedFilters(),
        ]);

        return $response;
    }

    public function mail(ExportMailRequest $request, ExportMailService $service): JsonResponse
    {
        try {
            $service->queue(
                key: (string) $request->validated('key'),
                params: $request->exportParams(),
                format: (string) $request->validated('format'),
                email: (string) $request->validated('email'),
                admin: $request->user('gopanel'),
                note: $request->validated('note'),
            );
        } catch (PdfUnavailableException $e) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => $e->getMessage()], 403);
        }

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => 'Sənəd hazırlanır və ' . $request->validated('email') . ' ünvanına göndəriləcək.',
        ]);
    }

    /**
     * Endirmə linkə klikdir (səhifə naviqasiyası) - JSON yox, əvvəlki
     * səhifəyə xəta mesajı ilə qayıdılır. AJAX çağırışına isə panelin
     * standart `{status: 'error'}` zərfi verilir.
     */
    private function fail(bool $json, string $message, int $status): Response
    {
        if ($json) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => $message], $status);
        }

        return back()->with('error', $message)->withErrors(['export' => $message]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function log(string $message, array $context): void
    {
        try {
            (new LogService('export', false))->info($message, $context);
        } catch (Throwable $e) {
            // Jurnal xətası endirməni dayandırmamalıdır (01-umumi.md § 7).
            report($e);
        }
    }
}
