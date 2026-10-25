<?php

declare(strict_types=1);

namespace App\Traits\System;

use App\Enums\System\AuthorRoleEnum;
use App\Models\Author\RecordAuthor;
use App\Services\Author\RecordAuthorService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Throwable;

/**
 * Modelə müəllif (`creator`) və məsul (`assignee`) qoşur - OPT-IN.
 *
 * Bölməyə qoşulmaq üçün modelə `use HasAuthors;` yazmaq kifayətdir -
 * cədvələ sütun əlavə edilmir (bax `record_authors` miqrasiyası). Müəllif
 * qeydi `created` hadisəsində avtomatik yazılır, məsul isə panel əməliyyatı
 * ilə (`HandlesAssignment` → `RecordAuthorService`).
 *
 * Trait-i işlətməyən modellərə heç nə olmur: BaseModel-ə əlavə edilmir,
 * çünki hər lookup/tərcümə sətri üçün əlavə INSERT lazımsız yükdür.
 *
 * Siyahıda N+1 olmasın deyə: `->with(['creator.admin', 'assignee.admin'])`.
 *
 * Sənəd: docs/authors-settings.md
 */
trait HasAuthors
{
    /**
     * Müəllif qeydi sətrin yaranma anında yazılır.
     *
     * Yazılış xətası əsas əməliyyatı POZA BİLMƏZ: müəllif qeydinin
     * alınmaması sətrin yaranmasına mane olmamalıdır
     * (`LogsAdminActivity`-dəki eyni zəmanət).
     */
    public static function bootHasAuthors(): void
    {
        static::created(function ($model): void {
            try {
                app(RecordAuthorService::class)->setCreator($model);
            } catch (Throwable $e) {
                report($e);
            }
        });
    }

    /** Bütün qeydlər - keçmiş məsullar da daxil. */
    public function authors(): MorphMany
    {
        return $this->morphMany(RecordAuthor::class, 'authorable');
    }

    /** Sətri yaradan - bir dəfəlikdir, dəyişmir. */
    public function creator(): MorphOne
    {
        return $this->morphOne(RecordAuthor::class, 'authorable')
            ->where('role', AuthorRoleEnum::CREATOR->value)
            ->whereNotNull('is_current');
    }

    /** Hazırda məşğul olan admin - yoxdursa `null`. */
    public function assignee(): MorphOne
    {
        return $this->morphOne(RecordAuthor::class, 'authorable')
            ->where('role', AuthorRoleEnum::ASSIGNEE->value)
            ->whereNotNull('is_current');
    }

    /** Əl dəyişmə tarixçəsi - yenidən köhnəyə doğru. */
    public function assignmentHistory(): MorphMany
    {
        return $this->morphMany(RecordAuthor::class, 'authorable')
            ->where('role', AuthorRoleEnum::ASSIGNEE->value)
            ->orderByDesc('assigned_at')
            ->orderByDesc('id');
    }
}
