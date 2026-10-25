<?php

declare(strict_types=1);

namespace App\Helpers\Gopanel;

use App\Enums\System\AuthorRoleEnum;
use App\Models\Author\RecordAuthor;
use App\Models\Gopanel\Admin;
use Illuminate\Database\Eloquent\Model;

/**
 * `record_authors` qeydlərinin paneldəki görünüşü (siyahı xanası, düymə, zolaq).
 *
 * Niyə bir yerdə: bir neçə bölmə eyni sətri göstərir - HTML hər datatable-da
 * təkrarlansa biri «Əlavə etdi», digəri «Müəllif» yazar. CSS qarşılığı
 * `public/assets/gopanel/css/assignment.css`-dədir.
 *
 * ⚠️ Çağırmazdan əvvəl əlaqə eager load olunmalıdır
 * (`with(['creator.admin', 'assignee.admin'])`), əks halda siyahıda N+1 yaranır.
 *
 * İcazə adları route prefiksindən qurulur: `<prefix>.assign` və
 * `<prefix>.assign.takeover` (bir əməliyyat = bir icazə).
 */
final class AuthorCell
{
    /**
     * «Əlavə etdi: Oruc Seyidov · 12.03.2026» nişanı.
     *
     * Müəllif bilinmirsə boş sətir qaytarılır - «-» yazmaq sütunu səs-küylə
     * doldurur, məlumatın olmaması onsuz da görünür.
     */
    public static function creatorLine(Model $model): string
    {
        $creator = $model->creator ?? null;

        if (! $creator instanceof RecordAuthor) {
            return '';
        }

        $date = ($creator->assigned_at ?? $creator->created_at)?->format('d.m.Y');

        return self::tag(
            'bx-user-plus',
            'Əlavə etdi',
            $creator->display_name . ($date ? ' · ' . $date : ''),
            'Bu qeydi əlavə edən',
        );
    }

    /**
     * Hazırkı məsulun nişanı.
     *
     * Köhnəlmiş təyinat solğun göstərilir: qeyd qalır, amma «bu adamla
     * dəqiqləşdir» mesajı TTL-dən sonra etibarını itirir.
     */
    public static function assigneeBadge(Model $model): string
    {
        $assignee = $model->assignee ?? null;

        if (! $assignee instanceof RecordAuthor) {
            return '';
        }

        $stale = $assignee->isStale();

        return self::tag(
            'bx-user-voice',
            'Məsul',
            $assignee->display_name . ($stale ? ' (köhnəlib)' : ''),
            'Bu qeydlə məşğul olan admin',
            $stale ? '' : 'gp-author-tag--active',
        );
    }

    /**
     * Siyahı sətrindəki «üzərimə götür» düyməsi.
     *
     * Davranış `js/modules/assignment/gp-assignment.js`-dədir: klik → izahlı
     * təsdiq → POST → cədvəl yenilənir.
     *
     * Boş sətir qaytarılan hallar: icazə yoxdur; qeyd onsuz da mənimdir;
     * başqasının üzərindədir və nə «takeover» icazəm var, nə də təyinat köhnəlib.
     *
     * @param  string  $prefix  route prefiksi (`gopanel.sales.orders`)
     * @param  string  $label   qeydin adı («sifariş»)
     */
    public static function claimButton(Model $model, string $prefix, string $label): string
    {
        $admin = auth('gopanel')->user();

        if (! $admin instanceof Admin || ! $admin->can($prefix . '.assign')) {
            return '';
        }

        $assignee = $model->assignee ?? null;
        $assignee = $assignee instanceof RecordAuthor ? $assignee : null;

        if ($assignee !== null && (int) $assignee->admin_id === (int) $admin->getKey()) {
            return '';
        }

        $stale = $assignee?->isStale() ?? false;

        if ($assignee !== null && ! $stale && ! $admin->can($prefix . '.assign.takeover')) {
            return '';
        }

        $takeover = $assignee !== null;

        return '<button type="button" class="btn btn-sm btn-outline-' . ($takeover ? 'warning' : 'primary') . ' waves-effect waves-light"'
            . ' data-gp-assign-action'
            . ' data-gp-assign-url="' . e(route($prefix . '.assign', $model)) . '"'
            . ' data-gp-assign-label="' . e($label) . '"'
            . ' data-gp-assign-owner="' . e((string) $assignee?->display_name) . '"'
            . ' data-gp-assign-stale="' . ($stale ? '1' : '0') . '"'
            . ($takeover ? ' data-gp-takeover' : '')
            . ' data-bs-toggle="tooltip" title="' . ($takeover ? 'Üzərimə götürürəm' : 'Üzərimə götür') . '">'
            . '<i class="bx ' . ($takeover ? 'bx-user-pin' : 'bx-user-check') . '"></i>'
            . '</button> ';
    }

    /**
     * Detal səhifəsindəki «kim məşğuldur» zolağının hazır datası.
     *
     * Niyə burada: blade-də `@php` yazılmır - vəziyyət, icazə və mətnlər
     * controller-də hazırlanıb `gopanel.component.assignment-banner`-ə ötürülür.
     * `null` = adminin `<prefix>.assign` icazəsi yoxdur, zolaq göstərilmir.
     *
     * @return array<string, mixed>|null
     */
    public static function banner(Model $model, string $prefix, string $label): ?array
    {
        $admin = auth('gopanel')->user();

        if (! $admin instanceof Admin || ! $admin->can($prefix . '.assign')) {
            return null;
        }

        $assignee = $model->assignee ?? null;
        $assignee = $assignee instanceof RecordAuthor ? $assignee : null;

        $mine = $assignee !== null && (int) $assignee->admin_id === (int) $admin->getKey();
        $stale = $assignee?->isStale() ?? false;
        $canTakeover = $admin->can($prefix . '.assign.takeover');

        return [
            'state'         => $assignee === null ? 'free' : ($mine ? 'mine' : 'other'),
            'assign_url'    => route($prefix . '.assign', $model),
            'release_url'   => route($prefix . '.release', $model),
            'label'         => $label,
            'owner'         => (string) $assignee?->display_name,
            'assigned_at'   => $assignee?->assigned_at?->format('d.m.Y H:i'),
            'stale'         => $stale,
            'can_takeover'  => $canTakeover,
            'show_takeover' => $assignee !== null && ! $mine && ($canTakeover || $stale),
            'ttl_hours'     => (int) config('gopanel.assignment.ttl_hours', 8),
        ];
    }

    /** Tarixçə sətri: «14.03.2026 14:20 - 14.03.2026 17:40». */
    public static function periodLabel(RecordAuthor $author): string
    {
        $from = $author->assigned_at?->format('d.m.Y H:i') ?? '-';

        if ($author->ended_at === null) {
            return $from . ' - hazırda';
        }

        return $from . ' - ' . $author->ended_at->format('d.m.Y H:i');
    }

    /**
     * Tarixçə komponentinin hazır sətirləri - blade yalnız çap edir.
     *
     * @param  iterable<RecordAuthor>  $assignments
     * @return list<array{name: string, period: string, current: bool, stale: bool, end_reason: ?string}>
     */
    public static function historyRows(iterable $assignments): array
    {
        $rows = [];

        foreach ($assignments as $author) {
            $rows[] = [
                'name'       => $author->display_name,
                'period'     => self::periodLabel($author),
                'current'    => $author->is_current !== null,
                'stale'      => $author->is_current !== null && $author->isStale(),
                'end_reason' => $author->end_reason?->label(),
            ];
        }

        return $rows;
    }

    /** Rol adı - siyahı başlıqlarında işlədilir. */
    public static function roleLabel(RecordAuthor $author): string
    {
        return ($author->role ?? AuthorRoleEnum::CREATOR)->label();
    }

    /**
     * «Etiket: dəyər» nişanı.
     *
     * Adminin adı QƏSDƏN etiketli və çərçivəli verilir: sadə mətn sətri
     * müştəri sütununun içində **müştərinin adı kimi oxunurdu**.
     */
    private static function tag(string $icon, string $label, string $value, string $hint, string $modifier = ''): string
    {
        return '<span class="gp-author-tag ' . $modifier . '" title="' . e($hint) . '">'
            . '<i class="bx ' . $icon . '"></i>'
            . '<span class="gp-author-tag__label">' . e($label) . ':</span> '
            . e($value)
            . '</span>';
    }
}
