<?php

declare(strict_types=1);

namespace App\Models\Author;

use App\Enums\System\AuthorEndReasonEnum;
use App\Enums\System\AuthorRoleEnum;
use App\Enums\System\AuthorSourceEnum;
use App\Models\BaseModel;
use App\Models\Gopanel\Admin;
use App\Models\User\User;
use App\Traits\System\AddUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Sətrin müəllifi / məsulu - bütün modellər üçün vahid morph qeydi.
 *
 * Cədvəl tarixçə saxlayır: qeyd silinmir və üzərinə yazılmır. Hazırkı qeydin
 * `is_current` dəyəri `1`, keçmişlərin `null` olur - `0` HEÇ VAXT yazılmır,
 * çünki unikal indeks onu real dəyər sayar (miqrasiyadakı izaha bax).
 *
 * Yazma yalnız `App\Services\Author\RecordAuthorService` üzərindən gedir.
 */
class RecordAuthor extends BaseModel
{
    use AddUuid;

    protected $table = 'record_authors';

    /** Müəllif qeydinin özü jurnallanmır - o, onsuz da jurnalın cavabıdır. */
    protected $logEnabled = false;

    protected $fillable = [
        'uid',
        'authorable_type',
        'authorable_id',
        'role',
        'admin_id',
        'user_id',
        'source',
        'author_name',
        'is_current',
        'assigned_at',
        'ended_at',
        'end_reason',
    ];

    protected $casts = [
        'role'        => AuthorRoleEnum::class,
        'source'      => AuthorSourceEnum::class,
        'end_reason'  => AuthorEndReasonEnum::class,
        'assigned_at' => 'datetime',
        'ended_at'    => 'datetime',
    ];

    public function authorable(): MorphTo
    {
        return $this->morphTo();
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Yalnız hazırkı qeydlər.
     *
     * `where('is_current', true)` YAZILMIR - sütun nullable-dır və keçmiş
     * qeydlərdə `null` olur.
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNotNull('is_current');
    }

    public function scopeRole(Builder $query, AuthorRoleEnum $role): Builder
    {
        return $query->where('role', $role->value);
    }

    /**
     * Siyahıda və tarixçədə görünən ad.
     *
     * Sıra: bağlı admin → bağlı istifadəçi → snapshot → mənbənin adı.
     * Admin hesabı silinsə belə sətir «-» olmur.
     */
    public function getDisplayNameAttribute(): string
    {
        $user = $this->user;
        $userName = $user === null
            ? null
            : ($user->full_name ?? trim(($user->name ?? '') . ' ' . ($user->surname ?? '')));

        foreach ([$this->admin?->full_name, $userName, $this->author_name] as $name) {
            if (is_string($name) && trim($name) !== '') {
                return trim($name);
            }
        }

        return ($this->source ?? AuthorSourceEnum::SYSTEM)->fallbackName();
    }

    /**
     * Təyinat köhnəlibmi - `config/gopanel/assignment.php` → `ttl_hours`.
     *
     * Köhnəlmiş qeyd avtomatik bağlanmır (cron yoxdur); sadəcə üzərinə
     * götürmək ayrıca «takeover» icazəsi tələb etmir və mesaj fərqli yazılır.
     * Səbəb: 8 saat toxunulmamış iş onsuz da sahibsizdir.
     */
    public function isStale(): bool
    {
        if ($this->assigned_at === null) {
            return false;
        }

        $hours = (int) config('gopanel.assignment.ttl_hours', 8);

        return $hours > 0 && $this->assigned_at->copy()->addHours($hours)->isPast();
    }
}
