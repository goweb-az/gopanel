<?php

declare(strict_types=1);

namespace App\Support\Gopanel;

use App\Models\Gopanel\Admin;
use Illuminate\Database\Eloquent\Model;

/**
 * «Əvvəlcə üzərinizə götürün» qapısı - istəyə bağlı.
 *
 * Bölmə bunu çağırırsa, sətri dəyişmək üçün admin onun **hazırkı məsulu**
 * olmalıdır. Səbəb iş qaydasıdır, texniki deyil: iki nəfər eyni müraciəti
 * paralel aparanda müştəriyə iki dəfə zəng gedir və statuslar bir-birinin
 * üstündən yazılır. Təyinat bu işi görünən edir.
 *
 * Qapı **oxumağa toxunmur** - səhifə, ixrac və tarixçə sərbəst açılır.
 *
 * Cavab 409-dur, 403 yox: adminin icazəsi var, sadəcə bir addım atmayıb.
 * Kod `NOT_ASSIGNED`-dır və `js/modules/assignment/gp-assignment-gate.js`
 * məhz ona baxıb «üzərinə götür / baxmağa davam et» sualını göstərir.
 *
 *   if (! AssignmentGuard::allows($item, auth('gopanel')->user())) {
 *       return response()->json(AssignmentGuard::payload($item), AssignmentGuard::STATUS);
 *   }
 */
final class AssignmentGuard
{
    public const CODE = 'NOT_ASSIGNED';

    public const STATUS = 409;

    /**
     * Admin bu sətri dəyişə bilirmi.
     *
     * Super admin istisnadır: sistem işini təyinat olmadan da görə bilməlidir
     * (nasazlıq araşdırması). `HasAuthors` işlətməyən model həmişə açıqdır -
     * qapını yanlış modelə qoymaq bölməni bağlamasın.
     */
    public static function allows(Model $record, ?Admin $admin): bool
    {
        if ($admin === null) {
            return false;
        }

        if ($admin->is_super) {
            return true;
        }

        if (! method_exists($record, 'assignee')) {
            return true;
        }

        $assignee = self::assignee($record);

        return $assignee !== null && (int) $assignee->admin_id === (int) $admin->getKey();
    }

    /** Hazırda kim məsuldur - heç kim deyilsə `null`. */
    public static function assigneeName(Model $record): ?string
    {
        if (! method_exists($record, 'assignee')) {
            return null;
        }

        return self::assignee($record)?->display_name;
    }

    /**
     * JS-in gözlədiyi cavab - panelin `status` müqaviləsi daxil.
     *
     * @return array<string, mixed>
     */
    public static function payload(Model $record): array
    {
        $holder = self::assigneeName($record);

        return [
            'status'  => 'error',
            'success' => false,
            'code'    => self::CODE,
            'message' => $holder === null
                ? 'Dəyişiklik etmək üçün əvvəlcə bu qeydi öz üzərinizə götürməlisiniz.'
                : 'Bu qeyd hazırda ' . $holder . ' üzərindədir. Dəyişiklik etmək üçün onu öz üzərinizə götürməlisiniz.',
            'holder'  => $holder,
        ];
    }

    private static function assignee(Model $record): ?Model
    {
        return $record->relationLoaded('assignee')
            ? $record->getRelation('assignee')
            : $record->assignee()->with('admin')->first();
    }
}
