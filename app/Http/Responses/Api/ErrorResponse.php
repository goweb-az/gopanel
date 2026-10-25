<?php

declare(strict_types=1);

namespace App\Http\Responses\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * API-nin xəta zərfi - .claude/rules/04-api.md §1:
 *
 *   { "success": false, "message": "Səhv sorğu", "code": "VALIDATION_ERROR", "errors": {} }
 *
 * `code` maşın üçündür (sabit, tərcümə olunmur) - client mesaja yox, koda baxır.
 * `message` insan üçündür (Azərbaycan dilində). Daxili detal (trace, SQL)
 * BURAYA YAZILMIR - jurnala gedir.
 */
final class ErrorResponse
{
    public static function make(
        string $message,
        int $status = Response::HTTP_BAD_REQUEST,
        ?string $code = null,
        array $errors = [],
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
            'code'    => $code ?? self::defaultCode($status),
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * Validasiya xətası - `errors` sahə üzrə qalır, `message` isə onları oxunaqlı
     * cümləyə yığır ki, client toast-da ümumi «məlumatlar düzgün deyil» yox,
     * nəyin səhv olduğunu göstərsin:
     *
     *   Göndərilən məlumatlar düzgün deyil. 1. Ad mütləqdir. 2. Şəkil formatı …
     *
     * @param  array<string, array<int, string>|string>  $errors
     */
    public static function validation(array $errors, ?string $message = null): JsonResponse
    {
        return self::make(
            message: self::validationMessage($errors, $message ?? 'Göndərilən məlumatlar düzgün deyil.'),
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            code: 'VALIDATION_ERROR',
            errors: $errors,
        );
    }

    /**
     * @param  array<string, array<int, string>|string>  $errors
     */
    public static function validationMessage(array $errors, string $prefix): string
    {
        // Eyni mətn bir neçə sahədə ola bilər (`items.0`, `items.1`) - bir dəfə yazılır.
        $messages = [];

        foreach ($errors as $value) {
            foreach ((array) $value as $text) {
                $text = trim((string) $text);

                if ($text !== '' && !in_array($text, $messages, true)) {
                    $messages[] = $text;
                }
            }
        }

        if ($messages === []) {
            return $prefix;
        }

        if (count($messages) === 1) {
            return $prefix . ' ' . $messages[0];
        }

        $lines = [];

        foreach ($messages as $index => $text) {
            $lines[] = ($index + 1) . '. ' . $text;
        }

        return $prefix . ' ' . implode(' ', $lines);
    }

    /** Status-a görə sabit kod - 04-api.md və api-architecture sənədindəki xəritə. */
    private static function defaultCode(int $status): string
    {
        return match ($status) {
            401     => 'UNAUTHENTICATED',
            403     => 'FORBIDDEN',
            404     => 'RESOURCE_NOT_FOUND',
            405     => 'METHOD_NOT_ALLOWED',
            419     => 'SESSION_EXPIRED',
            422     => 'VALIDATION_ERROR',
            429     => 'TOO_MANY_REQUESTS',
            503     => 'SERVICE_UNAVAILABLE',
            default => $status >= 500 ? 'SERVER_ERROR' : 'BAD_REQUEST',
        };
    }
}
