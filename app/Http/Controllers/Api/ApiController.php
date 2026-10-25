<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Responses\Api\ErrorResponse;
use App\Http\Responses\Api\SuccessResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;

/**
 * Bütün API controller-lərinin ortaq bazası.
 *
 * Cavab zərfi burada TƏK yerdən qurulur (SuccessResponse / ErrorResponse).
 * Controller-də `response()->json(...)` və try/catch (Throwable) yazılmır -
 * istisnalar `app/Exceptions/Handler.php` tərəfindən zərfə çevrilir.
 *
 *   final class BlogController extends ApiController
 *   {
 *       public function index(BlogIndexRequest $request, BlogQuery $query): JsonResponse
 *       {
 *           return $this->success(BlogResource::collection($query->paginate(...)));
 *       }
 *   }
 */
abstract class ApiController extends BaseController
{
    protected function success(
        JsonResource|ResourceCollection|LengthAwarePaginator|array|null $data = null,
        ?string $message = null,
        int $status = Response::HTTP_OK,
        array $meta = [],
    ): JsonResponse {
        return SuccessResponse::make($data, $message, $status, $meta);
    }

    protected function created(
        JsonResource|ResourceCollection|LengthAwarePaginator|array|null $data = null,
        ?string $message = null,
        array $meta = [],
    ): JsonResponse {
        return $this->success($data, $message ?? 'Məlumat uğurla yaradıldı.', Response::HTTP_CREATED, $meta);
    }

    protected function updated(
        JsonResource|ResourceCollection|LengthAwarePaginator|array|null $data = null,
        ?string $message = null,
        array $meta = [],
    ): JsonResponse {
        return $this->success($data, $message ?? 'Məlumat uğurla yeniləndi.', Response::HTTP_OK, $meta);
    }

    protected function deleted(?string $message = null): JsonResponse
    {
        return $this->success(null, $message ?? 'Məlumat uğurla silindi.');
    }

    protected function error(
        ?string $message = null,
        int $status = Response::HTTP_BAD_REQUEST,
        ?string $code = null,
        array $errors = [],
    ): JsonResponse {
        return ErrorResponse::make($message ?? 'Əməliyyat yerinə yetirilmədi.', $status, $code, $errors);
    }

    protected function notFound(?string $message = null): JsonResponse
    {
        return $this->error($message ?? 'Məlumat tapılmadı.', Response::HTTP_NOT_FOUND, 'RESOURCE_NOT_FOUND');
    }

    protected function validationError(array $errors, ?string $message = null): JsonResponse
    {
        return ErrorResponse::validation($errors, $message);
    }

    protected function unauthorized(?string $message = null): JsonResponse
    {
        return $this->error($message ?? 'Doğrulanmamış istifadəçi.', Response::HTTP_UNAUTHORIZED, 'UNAUTHENTICATED');
    }

    protected function forbidden(?string $message = null): JsonResponse
    {
        return $this->error($message ?? 'Bu əməliyyat üçün icazəniz yoxdur.', Response::HTTP_FORBIDDEN, 'FORBIDDEN');
    }
}
