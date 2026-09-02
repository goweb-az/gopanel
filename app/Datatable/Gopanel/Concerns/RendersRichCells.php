<?php

declare(strict_types=1);

namespace App\Datatable\Gopanel\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Cədvəl hüceyrələrinin və sətir düymələrinin ortaq qəlibləri.
 *
 * NİYƏ trait:
 * Tək mənbədir - qəlib burada dəyişəndə bu trait-i işlədən bütün panel
 * cədvəlləri eyni anda yenilənir. Əks halda hər datatable öz HTML-ini yazır
 * və eyni panel daxilində sətirlər fərqli şriftdə/rəngdə görünür.
 *
 * Qarşılığı olan CSS: `public/assets/gopanel/css/custom.css` — «Cədvəl
 * hüceyrələri» bloku. Sinif adları dəyişəndə hər ikisi birlikdə dəyişməlidir.
 */
trait RendersRichCells
{
    /** Qalın başlıq + altında solğun kiçik izah. */
    protected function titleCell(?string $title, ?string $sub = null): string
    {
        $html = '<div class="gp-cell-main">' . e(($title === null || $title === '') ? '—' : $title) . '</div>';

        if ($sub !== null && $sub !== '') {
            $html .= '<div class="gp-cell-sub">' . e($sub) . '</div>';
        }

        return $html;
    }

    /** Solğun kiçik mətn (boşdursa tire). */
    protected function mutedCell(?string $value): string
    {
        return '<span class="gp-cell-sub">' . ($value !== null && $value !== '' ? e($value) : '—') . '</span>';
    }

    /** Uzun mətn - iki sətirdən sonra kəsilir, cədvəli dağıtmır. */
    protected function textCell(?string $value, int $limit = 120): string
    {
        $value = trim(strip_tags(html_entity_decode((string) $value)));

        if ($value === '') {
            return '<span class="gp-cell-sub">—</span>';
        }

        return '<div class="gp-cell-sub gp-cell-clamp">' . e(mb_substr($value, 0, $limit)) . '</div>';
    }

    /**
     * Yumşaq fonlu nişan.
     *
     * Qeyd: `?:` işlədilmir - `'0'` PHP-də falsy-dir və sıfır sayı tire kimi
     * görünürdü; yalnız null/boş sətir tire ilə əvəzlənir.
     */
    protected function badge(?string $text, string $tone = 'secondary'): string
    {
        $value = ($text === null || $text === '') ? '—' : $text;

        return '<span class="badge gp-badge gp-badge-' . $tone . '">' . e($value) . '</span>';
    }

    /** Tarix hüceyrəsi: üstdə gün, altda saat. */
    protected function dateCell(Carbon|string|null $value): string
    {
        if (blank($value)) {
            return '<span class="gp-cell-sub">—</span>';
        }

        $date = $value instanceof Carbon ? $value : Carbon::parse($value);

        return '<div class="gp-cell-main">' . $date->format('d.m.Y') . '</div>'
            . '<div class="gp-cell-sub">' . $date->format('H:i') . '</div>';
    }

    /** Sətir düymələrini tək sətirdə saxlayan sarğı. */
    protected function actionsCell(array $buttons): string
    {
        return '<div class="actions">' . implode('', array_filter($buttons)) . '</div>';
    }

    /** Adi keçid düyməsi (endirmə, qalereya və s.). */
    protected function linkBtn(string $url, string $icon, string $title, string $tone = 'success', string $extraClass = ''): string
    {
        return '<a href="' . $url . '" class="btn btn-outline-' . $tone . ' waves-effect waves-light ' . $extraClass . '"'
            . ' data-bs-toggle="tooltip" data-bs-placement="top" title="' . e($title) . '">'
            . '<i class="' . $icon . '"></i></a>';
    }

    /**
     * Silmə düyməsi - `main.js`-dəki `.delete` axını ilə işləyir.
     *
     * `$url` verilməsə panelin ümumi silmə ünvanı işlədilir. Öz icazəsi olan
     * modullar (məsələn backup) buraya ÖZ route-unu ötürür - ümumi endpoint
     * modul icazəsini yoxlamır.
     */
    protected function deleteRowBtn(Model $item, string $title = 'Məlumatı sil', ?string $url = null): string
    {
        $key = method_exists($item, 'getIdentifierIdAttribute') ? $item->identifier_id : $item->id;
        $url = $url ?? route('gopanel.general.delete', $key);

        return '<a href="#" class="btn btn-outline-danger waves-effect waves-light delete"'
            . ' data-url="' . $url . '" data-key="' . get_class($item) . '"'
            . ' data-bs-toggle="tooltip" data-bs-placement="top" title="' . e($title) . '">'
            . '<i class="fas fa-trash"></i></a>';
    }
}
