<?php

declare(strict_types=1);

namespace App\Enums\System;

/**
 * `record_authors.role` - bir sətirdə insanın hansı rolu daşıdığı.
 *
 * Niyə bir cədvəl: hər iki rol eyni formadadır - «filan sətirdə filan adamın
 * filan rolu var». İki ayrı cədvəl eyni morph məntiqini iki dəfə yazmaq olardı.
 */
enum AuthorRoleEnum: string
{
    /** Sətri yaradan - bir dəfəlikdir, heç vaxt dəyişmir. */
    case CREATOR = 'creator';

    /** Sətirlə hazırda məşğul olan admin - əl dəyişdikcə tarixçə yaranır. */
    case ASSIGNEE = 'assignee';

    public function label(): string
    {
        return match ($this) {
            self::CREATOR  => 'Əlavə edən',
            self::ASSIGNEE => 'Məsul',
        };
    }
}
