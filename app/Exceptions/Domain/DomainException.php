<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Exception;
use Illuminate\Http\Response;
use Throwable;

/**
 * İş qaydası pozulanda atılan istisnaların bazası.
 *
 * Niyə: servis «balans kifayət etmir» kimi halı `return false` ilə bildirəndə
 * səbəb itir, controller isə hər yerdə `if (!$ok) return error(...)` yazır.
 * İstisna səbəbi, HTTP status-u və maşın kodunu birlikdə daşıyır; Handler
 * onu API zərfinə özü çevirir - controller-də try/catch yazılmır.
 *
 *   final class BalanceNotEnough extends DomainException
 *   {
 *       protected int $status = Response::HTTP_UNPROCESSABLE_ENTITY;
 *       protected string $errorCode = 'BALANCE_NOT_ENOUGH';
 *   }
 *
 *   throw new BalanceNotEnough('Balansınız kifayət etmir.');
 *
 * Bu istisna `system-errors` jurnalına DÜŞMÜR (gözlənilən haldır, xəta deyil).
 */
abstract class DomainException extends Exception
{
    protected int $status = Response::HTTP_UNPROCESSABLE_ENTITY;

    protected string $errorCode = 'DOMAIN_ERROR';

    /** @var array<string, mixed> */
    protected array $errors = [];

    public function __construct(string $message = '', array $errors = [], ?Throwable $previous = null)
    {
        parent::__construct($message !== '' ? $message : 'Əməliyyat yerinə yetirilmədi.', 0, $previous);

        $this->errors = $errors;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /** @return array<string, mixed> */
    public function errors(): array
    {
        return $this->errors;
    }
}
