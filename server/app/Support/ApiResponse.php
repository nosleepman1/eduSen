<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiResponse
{
    public static function success(string $message, mixed $data = null, int $status = 200, ?array $meta = null): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        $meta ??= self::paginationMeta($data);

        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    public static function error(string $message, int $status = 400, ?string $code = null, ?array $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($code !== null) {
            $payload['code'] = $code;
        }

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * Pagination metadata for a paginated payload.
     *
     * Nesting a resource collection under a `data` key collapses it to the bare
     * item list: Laravel only emits its own `links`/`meta` envelope when the
     * collection is the whole response. Every list endpoint therefore returned a
     * page of results with no way to tell how many pages there were. Rebuilding
     * the metadata here covers all of them at once, rather than asking each
     * controller to remember.
     *
     * @return array{current_page: int, per_page: int, from: ?int, to: ?int, total?: int, last_page?: int, has_more_pages?: bool}|null
     */
    private static function paginationMeta(mixed $data): ?array
    {
        $paginator = $data instanceof ResourceCollection ? $data->resource : $data;

        if (! $paginator instanceof Paginator && ! $paginator instanceof LengthAwarePaginator) {
            return null;
        }

        $meta = [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];

        // simplePaginate() knows whether another page exists but not how many.
        if ($paginator instanceof LengthAwarePaginator) {
            return $meta + [
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ];
        }

        return $meta + ['has_more_pages' => $paginator->hasMorePages()];
    }
}
