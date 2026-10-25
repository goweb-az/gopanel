<?php

namespace App\Helpers\Common;

use App\Support\Activity\ActivityChangeFormatter;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class ActivityLogHelper
{
    /**
     * Heç vaxt loglanmayan / təsvirdə maskalanan həssas sahələr.
     */
    public const SENSITIVE_ATTRIBUTES = [
        'password',
        'password_confirmation',
        'remember_token',
        'app_token',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'secret',
        'otp',
        'otp_code',
        'pin',
    ];

    /**
     * Causer axtarılan guard-lar (prioritet sırası ilə).
     * gopanel → admin, web → sayt istifadəçisi, api → mobil/API.
     * Konfiqurasiyada olmayan guard səssizcə ötürülür - törəmə layihə öz
     * guard-ını (məs. `panel`) bura əlavə edə bilər.
     */
    public const CAUSER_GUARDS = ['gopanel', 'web', 'api'];

    /**
     * `activity_messages.php`-dəki MODEL bloklarını qaytarır (ortaq açarlar xaric).
     *
     * Faylda model bloklarından başqa ortaq siyahılar da var (`attribute_labels`,
     * `technical_attributes`...). Onları ayırmaq üçün `title` açarına baxmaq
     * kifayət deyil - `attribute_labels` içində də `'title' => 'başlıq'` sətri ola
     * bilər. Model açarı `class_basename()`-dir, yəni həmişə BÖYÜK hərflə başlayır;
     * ortaq açarlar isə kiçik hərflə (snake_case) yazılır.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function modelBlocks(): array
    {
        return array_filter(
            (array) config('custom.activity_messages', []),
            fn ($config, $key) => is_string($key)
                && $key !== ''
                && ctype_upper($key[0])
                && is_array($config)
                && is_string($config['title'] ?? null),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * Activity-yə əməliyyatı edən istifadəçini bağlayır.
     * Spatie-nin default causer resolver-i yalnız default guard-a baxır -
     * gopanel/mobil sorğularında causer boş qalırdı, bu metod bütün guard-ları yoxlayır.
     */
    public static function associateCauser(Activity $activity): void
    {
        // Artıq təyin olunubsa (məs. CauserResolver tapıb) toxunma
        if (!is_null($activity->causer_id)) {
            return;
        }

        $causer = self::resolveCauser();
        if ($causer instanceof Model) {
            $activity->causer()->associate($causer);
        }
    }

    /**
     * Aktiv guard-lardan istifadəçini tapır (cron/queue-da null).
     */
    public static function resolveCauser(): ?Authenticatable
    {
        foreach (self::CAUSER_GUARDS as $guard) {
            try {
                if (Auth::guard($guard)->check()) {
                    return Auth::guard($guard)->user();
                }
            } catch (\Throwable) {
                // Guard konfiqurasiyada yoxdursa və ya session hazır deyilsə - növbətisinə keç
                continue;
            }
        }

        return null;
    }

    /**
     * Causer-in insan-oxunaqlı adı. Konsol/cron/queue kontekstində 'Sistem'.
     */
    public static function causerName(): string
    {
        try {
            $user = self::resolveCauser();
        } catch (\Throwable) {
            return 'Sistem';
        }

        if (!$user) {
            return 'Sistem';
        }

        // Admin: full_name sütunu; User: full_name accessor-u və ya name+surname
        $name = trim((string) ($user->full_name
            ?? trim(($user->name ?? '') . ' ' . ($user->surname ?? ''))));

        return $name !== '' ? $name : (string) ($user->email ?? 'Sistem');
    }

    /**
     * Config-dən modelin mesajını oxuyub placeholder-ları əvəz edir.
     *
     * Placeholder-lar:
     *  - `:causer` → əməliyyatı edən istifadəçinin adı (yoxdursa 'Sistem')
     *  - `:changes` → nə dəyişdi: «giriş saatı: 09:00 → 10:00» (yalnız `updated`-də mənalıdır)
     *  - `:any_attribute` → modelin istənilən attribute-u (accessor-lar daxil)
     *
     * Davranış zəmanətləri:
     *  - Mövcud olmayan / null attribute → '-'
     *  - Həssas attribute (:password və s.) → '***'
     *  - Tarix → 'd.m.Y H:i', bool → 'bəli'/'xeyr', massiv/obyekt → JSON
     *  - Attribute oxunarkən xəta (məs. accessor içində exception) → '-' (əməliyyat pozulmur)
     *  - Config-də mesaj yoxdursa → universal fallback mesajı (restored daxil)
     */
    public static function resolveDescription(Model $model, Activity $activity, string $eventName): void
    {
        $modelName = class_basename($model);
        $messages  = config("custom.activity_messages.{$modelName}", []);

        if (isset($messages[$eventName]) && is_string($messages[$eventName])) {
            $description = $messages[$eventName];

            // Bütün :placeholder-ları tap (uzun adlar əvvəl əvəzlənsin deyə sırala)
            preg_match_all('/:([a-z0-9_]+)/i', $description, $matches);
            $placeholders = array_unique($matches[1]);
            usort($placeholders, fn($a, $b) => strlen($b) <=> strlen($a));

            foreach ($placeholders as $attr) {
                $description = str_replace(":{$attr}", self::placeholderValue($model, $attr, $activity), $description);
            }

            // «- -» kimi boş quyruqlar qalmasın (dəyişiklik siyahısı boş olanda)
            $description = trim(preg_replace('/\s*[--]\s*-\s*$/u', '', $description));
        } else {
            $description = self::fallbackDescription($modelName, $eventName);
        }

        $activity->description = $description;
    }

    /**
     * Modelin əlavə konteksti (hansı qrafik, hansı gün, hansı şirkət) log sətrinə yazılır.
     *
     * Niyə: `subject_id` tək başına heç nə demir - hadisəni araşdıranda hər dəfə
     * əl ilə `workdays` → `timesheets` zəncirini keçmək lazım gəlirdi. İndi bu
     * məlumat log sətrinin içindədir və detal pəncərəsində göstərilir.
     *
     * Model `activityContext(): array` metodu təyin edibsə çağırılır - əks halda
     * heç nə əlavə olunmur (mövcud modellərin davranışı dəyişmir).
     */
    public static function attachContext(Model $model, Activity $activity): void
    {
        if (!method_exists($model, 'activityContext')) {
            return;
        }

        $context = array_filter(
            $model->activityContext(),
            fn($value) => $value !== null && $value !== '',
        );

        if ($context === []) {
            return;
        }

        $properties = collect($activity->properties ?? []);
        $causer     = self::resolveCauser();

        $activity->properties = $properties->put('context', $context + [
            'causer_name' => self::causerName(),
            'causer_id'   => $causer?->getKey(),
            'causer_type' => $causer ? get_class($causer) : null,
        ]);
    }

    /**
     * «Nə dəyişdi» sətri: köhnə və yeni dəyərlərin insan-oxunaqlı fərqi.
     *
     * Qayda BURADA YAZILMIR - `ActivityChangeFormatter`-dədir, çünki eyni
     * etiket/dəyər çevirməsi paneldəki «Tarixçə» pəncərəsində də lazımdır.
     * İki nüsxə saxlanılsaydı, log sətri ilə tarixçə fərqli mətn göstərərdi.
     */
    private static function changesSummary(Model $model, Activity $activity): string
    {
        $properties = collect($activity->properties ?? []);
        $new        = (array) ($properties->get('attributes') ?? []);
        $old        = (array) ($properties->get('old') ?? []);

        if ($new === []) {
            return '-';
        }

        return ActivityChangeFormatter::sentence(class_basename($model), $new, $old);
    }

    /**
     * Tək placeholder-ın dəyərini təhlükəsiz şəkildə string-ə çevirir.
     */
    private static function placeholderValue(Model $model, string $attr, ?Activity $activity = null): string
    {
        if ($attr === 'causer') {
            return self::causerName();
        }

        if ($attr === 'changes') {
            return $activity ? self::changesSummary($model, $activity) : '-';
        }

        if (in_array(strtolower($attr), self::SENSITIVE_ATTRIBUTES, true)) {
            return '***';
        }

        try {
            $value = $model->getAttribute($attr);
        } catch (\Throwable) {
            return '-';
        }

        return self::stringify($value);
    }

    /**
     * İstənilən attribute dəyərini oxunaqlı string-ə çevirir.
     * Çevirmə qaydası `ActivityChangeFormatter`-dədir - tək mənbə.
     */
    private static function stringify(mixed $value): string
    {
        return ActivityChangeFormatter::stringify($value);
    }

    /**
     * Config-də mesaj olmayan hadisələr üçün universal Azərbaycanca fallback.
     */
    private static function fallbackDescription(string $modelName, string $eventName): string
    {
        $causer = self::causerName();

        $eventLabels = [
            'created'  => 'yaratdı',
            'updated'  => 'yenilədi',
            'deleted'  => 'sildi',
            'restored' => 'bərpa etdi',
        ];

        if (isset($eventLabels[$eventName])) {
            return "{$causer} {$modelName} qeydini {$eventLabels[$eventName]}.";
        }

        return "{$modelName} modelində {$eventName} əməliyyatı aparıldı.";
    }
}
