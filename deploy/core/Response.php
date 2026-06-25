<?php

namespace YYFSS\Core;

class Response
{
    public static function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success($data = null, string $message = 'success', int $code = 200): void
    {
        self::json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function error(string $message = 'error', int $code = 400, $data = null): void
    {
        self::json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $code >= 100 && $code < 600 ? $code : 400);
    }

    public static function paginate(array $items, int $total, int $page, int $pageSize): void
    {
        self::success([
            'list' => $items,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => $pageSize > 0 ? (int)ceil($total / $pageSize) : 0,
        ]);
    }
}
