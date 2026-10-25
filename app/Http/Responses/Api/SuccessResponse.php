<?php

declare(strict_types=1);

namespace App\Http\Responses\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * API-nin uğurlu cavab zərfi - .claude/rules/04-api.md §1 müqaviləsi:
 *
 *   { "success": true, "message": "", "data": {}, "meta": { "pagination": {...} } }
 *
 * Niyə tək sinif: hər controller öz `response()->json([...])`-unu yazanda bir
 * endpoint `status`, digəri `success`, üçüncüsü `result` qaytarır və mobil
 * tətbiq hər birini ayrıca parse edir. Zərf burada TƏK yerdə qurulur.
 *
 * `data` HƏMİŞƏ obyekt və ya massivdir - null `{}`-ə çevrilir, ki sonradan sahə
 * əlavə etmək müqaviləni pozmasın. Paginator verilərsə `data` = sətirlər,
 * səhifə məlumatı isə `meta.pagination`-a düşür.
 */
final class SuccessResponse
{
    public static function make(
        JsonResource|ResourceCollection|LengthAwarePaginator|array|null $data = null,
        ?string $message = null,
        int $status = Response::HTTP_OK,
        array $meta = [],
    ): JsonResponse {
        if ($data instanceof LengthAwarePaginator) {
            $meta['pagination'] = self::paginationMeta($data);
        } elseif ($data instanceof ResourceCollection && $data->resource instanceof LengthAwarePaginator) {
            $meta['pagination'] = self::paginationMeta($data->resource);
        }

        $payload = [
            'success' => true,
            'message' => $message ?? '',
            'data'    => self::normalizeData($data),
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    private static function normalizeData(mixed $data): mixed
    {
        if ($data === null) {
            return new \stdClass();
        }

        if ($data instanceof LengthAwarePaginator) {
            return $data->items();
        }

        if ($data instanceof JsonResource) {
            // Resource-un öz `data` sarğısı ikiqat olmasın deyə həll olunmuş massiv
            return $data->resolve(request());
        }

        return $data;
    }

    /**
     * @return array{current_page: int, per_page: int, total: int, last_page: int}
     */
    private static function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
            'last_page'    => $paginator->lastPage(),
        ];
    }
}
