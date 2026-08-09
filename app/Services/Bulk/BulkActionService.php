<?php

declare(strict_types=1);

namespace App\Services\Bulk;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Throwable;

/**
 * Siyahılardakı TOPLU əməliyyatların ortaq gövdəsi (checkbox ilə seçilənlər).
 *
 * BU SİNİF LAYİHƏDƏN ASILI DEYİL - hər modul öz `XxxBulkService`-ini bundan
 * törədir. Nümunə:
 *
 * ```php
 * final class BlogBulkService extends BulkActionService
 * {
 *     public const ACTION_PUBLISH = 'publish';
 *     public const ACTION_DELETE  = 'delete';
 *
 *     public static function actions(): array
 *     {
 *         return [self::ACTION_PUBLISH, self::ACTION_DELETE];
 *     }
 *
 *     public static function abilityFor(string $action): string
 *     {
 *         return 'blog.' . $action;
 *     }
 *
 *     protected function fetch(array $ids, string $action): Collection
 *     {
 *         return Blog::whereIn('id', $ids)->get();
 *     }
 *
 *     protected function apply(Model $model, string $action): void
 *     {
 *         match ($action) {
 *             self::ACTION_PUBLISH => app(BlogService::class)->publish($model),
 *             self::ACTION_DELETE  => app(BlogService::class)->delete($model),
 *         };
 *     }
 *
 *     protected function label(Model $model): string { return $model->title; }
 *
 *     protected function emptySelectionMessage(): string { return 'Heç bir yazı seçilməyib.'; }
 * }
 * ```
 *
 * Burada yeni iş məntiqi YOXDUR - hər əməliyyat tək sətir üçün işləyən eyni
 * servisi çağırır. Səbəb: toplu rejim tək rejimdən fərqli davransa, iki yerdə
 * iki nəticə alınardı.
 *
 * Bir sətrin xətası qalanları DAYANDIRMIR - nəticədə uğurlu/uğursuz/ötürülən
 * sayı qaytarılır və istifadəçi nəyin alınmadığını görür.
 *
 * Törəmə siniflər yalnız «nəyi, necə» hissəsini verir:
 * `fetch()` (hansı sətirlər), `apply()` (əməliyyat), `label()` (xəta mətnində ad).
 */
abstract class BulkActionService
{
    /** Xəta siyahısı bu qədər fərqli sətirdən sonra kəsilir - mesaj oxunaqlı qalsın. */
    private const MAX_ERRORS = 5;

    /** @return array<int, string> icazə verilən əməliyyat açarları */
    abstract public static function actions(): array;

    /** Əməliyyatın tələb etdiyi icazə - bir əməliyyat = bir icazə. */
    abstract public static function abilityFor(string $action): string;

    /**
     * Əməliyyata uyğun sətirlər.
     *
     * Bərpa yalnız SİLİNMİŞ sətirlərə aiddir, qalanlar dirilərə - seçimdən
     * kənarda qalanlar `skipped` kimi sayılır.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, Model>
     */
    abstract protected function fetch(array $ids, string $action): Collection;

    /** Tək sətrə əməliyyatı tətbiq edir (mövcud tək-sətir servisi çağırılır). */
    abstract protected function apply(Model $model, string $action): void;

    /** Xəta mətnində görünəcək ad. */
    abstract protected function label(Model $model): string;

    /** Heç nə seçilməyəndə göstərilən mesaj (məs. «Heç bir şirkət seçilməyib.»). */
    abstract protected function emptySelectionMessage(): string;

    /**
     * @param  array<int, int>  $ids
     * @return array{done: int, failed: int, skipped: int, errors: array<int, string>}
     */
    public function run(array $ids, string $action): array
    {
        if (!in_array($action, static::actions(), true)) {
            throw new RuntimeException('Naməlum əməliyyat.');
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            throw new RuntimeException($this->emptySelectionMessage());
        }

        $records = $this->fetch($ids, $action);

        $done   = 0;
        $failed = 0;
        $errors = [];

        foreach ($records as $record) {
            try {
                $this->apply($record, $action);
                $done++;
            } catch (Throwable $e) {
                $failed++;

                // Eyni səbəb təkrarlanırsa bir dəfə göstərilir
                $message = $this->label($record) . ': ' . $e->getMessage();

                if (!in_array($message, $errors, true) && count($errors) < self::MAX_ERRORS) {
                    $errors[] = $message;
                }
            }
        }

        return [
            'done'    => $done,
            'failed'  => $failed,
            // Seçilib, amma vəziyyətinə uyğun gəlməyənlər (məs. bərpada diri sətir)
            'skipped' => max(0, count($ids) - $records->count()),
            'errors'  => $errors,
        ];
    }

    /**
     * Nəticənin insan dilində xülasəsi.
     *
     * @param  array{done: int, failed: int, skipped: int, errors: array<int, string>}  $result
     * @param  string  $noun  «personal» / «şirkət» / «sahib»
     * @param  string  $verb  «silindi» / «bərpa olundu» …
     */
    protected static function summarize(array $result, string $noun, string $verb): string
    {
        $text = $result['done'] . ' ' . $noun . ' ' . $verb . '.';

        if ($result['skipped'] > 0) {
            $text .= ' ' . $result['skipped'] . ' sətir vəziyyətinə uyğun gəlmədiyi üçün ötürüldü.';
        }

        if ($result['failed'] > 0) {
            $text .= ' ' . $result['failed'] . ' sətirdə xəta oldu.';
        }

        return $text;
    }
}
