<?php

declare(strict_types=1);

namespace App\Exceptions\Domain\System;

use App\Exceptions\Domain\DomainException;
use Throwable;

/**
 * Sətir artıq başqa adminin üzərindədir.
 *
 * Niyə 409 (Conflict): sorğuda səhv yoxdur, sadəcə qeydin vəziyyəti
 * əməliyyata imkan vermir. Üzərinə götürmək ayrıca `takeover` icazəsi ilə
 * mümkündür - bu istisna onu bağlamır, yalnız adi «götür» axınını dayandırır.
 */
final class RecordAlreadyAssigned extends DomainException
{
    protected int $status = 409;

    protected string $errorCode = 'RECORD_ALREADY_ASSIGNED';

    public function __construct(
        string $message = 'Bu qeyd artıq başqa istifadəçinin üzərindədir.',
        array $errors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $errors, $previous);
    }
}
