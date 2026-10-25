<?php

declare(strict_types=1);

namespace App\Exceptions\Domain\System;

use App\Exceptions\Domain\DomainException;
use Throwable;

/**
 * Təyinat əməliyyatı icazəsizdir - məsələn başqasının üzərindəki qeydi
 * buraxmaq cəhdi, və ya panel hesabı olmadan çağırış.
 *
 * Niyə ayrıca istisna (`AuthorizationException` yox): mesaj konkret vəziyyəti
 * izah edir («yalnız öz qeydinizi buraxa bilərsiniz»), ümumi «icazə yoxdur» yox.
 */
final class RecordAssignmentDenied extends DomainException
{
    protected int $status = 403;

    protected string $errorCode = 'RECORD_ASSIGNMENT_DENIED';

    public function __construct(
        string $message = 'Bu əməliyyat üçün icazəniz yoxdur.',
        array $errors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $errors, $previous);
    }
}
