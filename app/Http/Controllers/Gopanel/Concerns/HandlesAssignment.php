<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gopanel\Concerns;

use App\Enums\System\AuthorRoleEnum;
use App\Enums\System\LogChannelEnum;
use App\Exceptions\Domain\DomainException;
use App\Exceptions\Domain\System\RecordAssignmentDenied;
use App\Models\Author\RecordAuthor;
use App\Services\Activity\ActivityLogger;
use App\Services\Author\RecordAuthorService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * «Bu qeydlə kim məşğuldur» - götür / burax / üzərinə götür endpoint-ləri.
 *
 * Bölmə controller-i bu trait-i qoşur və öz modeli ilə iki nazik metod elan
 * edir (route model binding abstrakt `Model` tipini həll edə bilmir):
 *
 *   public function assign(Request $request, Order $item): JsonResponse
 *   {
 *       return $this->applyAssignment($request, $item);
 *   }
 *
 *   public function release(Order $item): JsonResponse
 *   {
 *       return $this->applyRelease($item);
 *   }
 *
 *   protected function assignmentRoutePrefix(): string { return 'gopanel.sales.orders'; }
 *
 * Təyinat QIFIL DEYİL: səhifə bloklanmır. Məqsəd iki adminin eyni müştəriyə
 * zəng etməsinin qarşısını almaqdır. Qeydlər silinmir - tarixçədə qalır.
 *
 * Domen istisnası panel JSON-una burada çevrilir, çünki `Handler` yalnız
 * `api/*` üçün `DomainException`-ın status/kodunu saxlayır - paneldə 500 olardı.
 */
trait HandlesAssignment
{
    /**
     * Qeydi öz üzərinə götürür. `takeover=1` göndərilsə başqasının üzərindən
     * alınır - bu, ayrıca icazə tələb edir (köhnəlmiş təyinat istisnadır).
     */
    protected function applyAssignment(Request $request, Model $item): JsonResponse
    {
        try {
            $service = app(RecordAuthorService::class);
            $takeover = $request->boolean('takeover');
            $admin = auth('gopanel')->user();

            $previous = $takeover ? $service->current($item, AuthorRoleEnum::ASSIGNEE) : null;

            // Başqasının üzərindən almaq ayrıca icazədir - route middleware-i
            // bunu edə bilmir, çünki hər iki axın eyni endpoint-dədir.
            if ($previous !== null
                && (int) $previous->admin_id !== (int) $admin?->getKey()
                && ! $previous->isStale()
                && ! $admin?->can($this->assignmentRoutePrefix() . '.assign.takeover')
            ) {
                throw new RecordAssignmentDenied(
                    'Bu qeyd ' . $previous->display_name . ' adlı istifadəçinin üzərindədir. '
                    . 'Üzərinizə götürmək üçün icazəniz yoxdur.',
                );
            }

            $author = $takeover ? $service->takeover($item) : $service->claim($item);
        } catch (DomainException $e) {
            return $this->assignmentError($e);
        }

        // Eyni adam təkrar basıbsa (`claim` mövcud qeydi qaytarır) jurnal yazılmır.
        if ($author->wasRecentlyCreated) {
            $this->logAssignment($item, $author, $previous);
            $this->afterAssignment($item, $author, $previous);
        }

        return response()->json([
            'status'   => 'success',
            'success'  => true,
            'message'  => 'Qeyd sizin üzərinizə keçdi.',
            'assignee' => $author->display_name,
        ]);
    }

    /** Qeydi buraxır - sətir bağlanır, silinmir. */
    protected function applyRelease(Model $item): JsonResponse
    {
        try {
            $released = app(RecordAuthorService::class)->release($item);
        } catch (DomainException $e) {
            return $this->assignmentError($e);
        }

        if ($released) {
            ActivityLogger::on($this->assignmentLogChannel())
                ->subject($item)
                ->event('released')
                ->write();
        }

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => $released ? 'Qeyd buraxıldı.' : 'Qeyd onsuz da boşdur.',
        ]);
    }

    /** Təyinat route-larının prefiksi - icazə adları da buradan qurulur. */
    abstract protected function assignmentRoutePrefix(): string;

    /** Bölmənin fayl jurnalı kanalı - default `gopanel`. */
    protected function assignmentLogChannel(): LogChannelEnum|string
    {
        return LogChannelEnum::GOPANEL;
    }

    /**
     * Təyinatdan sonrakı əlavə addım - default heç nə.
     *
     * Niyə boş hook: törəmə layihədə əl dəyişəndə köhnə məsula bildiriş
     * göndərilir (o, müştəri ilə danışığın yarısında ola bilər). Starter-də
     * bildiriş modulu yoxdur - bölmə bu metodu üstələyib öz servisini çağırır.
     */
    protected function afterAssignment(Model $item, RecordAuthor $author, ?RecordAuthor $previous): void
    {
    }

    private function logAssignment(Model $item, RecordAuthor $author, ?RecordAuthor $previous): void
    {
        $reassigned = $previous !== null && (int) $previous->admin_id !== (int) $author->admin_id;

        ActivityLogger::on($this->assignmentLogChannel())
            ->subject($item)
            ->event($reassigned ? 'reassigned' : 'assigned')
            ->with([
                'assignee'          => $author->display_name,
                'previous_assignee' => $reassigned ? $previous->display_name : null,
            ])
            ->write();
    }

    private function assignmentError(DomainException $e): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'success' => false,
            'code'    => $e->errorCode(),
            'message' => $e->getMessage(),
        ], $e->status());
    }
}
