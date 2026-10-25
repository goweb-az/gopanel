<?php

declare(strict_types=1);

namespace App\Support\Api;

use Illuminate\Http\Request;

/**
 * Siyahi endpoint-lerinde `per_page` / `page` parametrlerinin tek noqteden
 * oxunmasi. Default ve maksimum deyerler config/api.php-dedir - controller-de
 * hardcode edilmir.
 *
 *   $params = PaginationParams::fromRequest($request);
 *   $query->paginate($params->perPage, ['*'], 'page', $params->page);
 */
final class PaginationParams
{
    private function __construct(
        public readonly int $perPage,
        public readonly int $page,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return self::make(
            $request->input('per_page'),
            $request->input('page'),
        );
    }

    public static function make(mixed $perPage = null, mixed $page = null): self
    {
        $default = (int) config('api.pagination.per_page', 20);
        $max = (int) config('api.pagination.max_per_page', 100);

        $resolvedPerPage = is_numeric($perPage) ? (int) $perPage : $default;
        $resolvedPage = is_numeric($page) ? (int) $page : 1;

        return new self(
            perPage: max(1, min($resolvedPerPage, $max)),
            page: max(1, $resolvedPage),
        );
    }
}
