<?php

namespace App\Traits\Activity;

use App\Helpers\Common\ActivityLogHelper;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Modellər üçün vahid activity log trait-i.
 *
 * Qaydalar:
 *  - Model config/custom/activity_messages.php-də qeydiyyatda olmalıdır, əks halda log yazılmır.
 *  - Modeldə `$logEnabled = false` varsa, log tamamilə söndürülür (config-də olsa belə).
 *  - Həssas sahələr (password, token və s.) heç vaxt properties-ə düşmür.
 *  - Causer bütün guard-lardan (gopanel/web/panel/api) axtarılır - cron/queue-da 'Sistem' olur.
 *  - Log yazılışındakı xəta əsas əməliyyatı POZA BİLMƏZ (tapActivity try/catch ilə qorunur).
 */
trait LogsAdminActivity
{
    use LogsActivity {
        shouldLogEvent as traitShouldLogEvent;
    }

    protected function shouldLogEvent(string $eventName): bool
    {
        // Model səviyyəsində söndürmə açarı
        if (property_exists($this, 'logEnabled') && $this->logEnabled === false) {
            return false;
        }

        // Yalnız config-də qeydiyyatda olan modellər loglanır
        if (!config('custom.activity_messages.' . class_basename($this))) {
            return false;
        }

        return $this->traitShouldLogEvent($eventName);
    }

    public function getActivitylogOptions(): LogOptions
    {
        if (property_exists($this, 'logEnabled') && $this->logEnabled === false) {
            return LogOptions::defaults()
                ->dontSubmitEmptyLogs()
                ->logOnly([]);
        }

        return LogOptions::defaults()
            ->logOnly($this->activityLoggableAttributes())
            ->useLogName(class_basename($this))
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Loglanacaq sahələr: fillable − hidden − həssas sahələr − `$logExcept`.
     * Parol/token kimi dəyərlər activity_log cədvəlinə heç vaxt yazılmır.
     */
    protected function activityLoggableAttributes(): array
    {
        return array_values(array_diff(
            $this->getFillable(),
            $this->getHidden(),
            ActivityLogHelper::SENSITIVE_ATTRIBUTES,
            $this->activityExcludedAttributes()
        ));
    }

    /**
     * Loga DÜŞMƏYƏN sahələr - modeldə `protected $logExcept = [...]` ilə təyin olunur.
     *
     * NİYƏ LAZIMDIR: bəzi sahələr sistemin öz işi ilə gündə dəfələrlə dəyişir və
     * onların tarixçəsi heç kimə lazım deyil - məsələn `last_seen_at`, `views`
     * hər sorğuda yenilənir və activity log-u doldurur (törəmə layihədə real hal:
     * davamiyyət skanı ilə dəyişən `at_work` yüz minlərlə sətir yaratmışdı).
     * Həssas sahələrdən fərqi: bunlar gizli deyil, sadəcə səs-küydür.
     *
     * `logOnlyDirty()` + `dontSubmitEmptyLogs()` ilə birlikdə işləyir: yalnız
     * bu sahə dəyişibsə heç bir log sətri yazılmır; başqa sahə ilə birlikdə
     * dəyişibsə log yazılır, amma bu sahə içində görünmür.
     *
     * @return array<int, string>
     */
    protected function activityExcludedAttributes(): array
    {
        return property_exists($this, 'logExcept') ? (array) $this->logExcept : [];
    }

    /**
     * Eyni siyahının OXU tərəfi - tarixçə ekranları üçün.
     *
     * `$logExcept` sonradan əlavə olunub: ondan əvvəlki log sətirləri həmin
     * sahələri hələ də özündə saxlayır. Paneldəki «Tarixçə» pəncərəsi onları
     * gizlətməlidir, əks halda lent `views` kimi sahələrin min-min
     * dəyişikliyindən ibarət olur (bax: `ActivitySubject::noiseAttributes`).
     *
     * @return array<int, string>
     */
    public function activityNoiseAttributes(): array
    {
        return $this->activityExcludedAttributes();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        try {
            ActivityLogHelper::associateCauser($activity);
            // Kontekst təsvirdən ƏVVƏL bağlanır - `:changes` properties-ə baxır
            ActivityLogHelper::attachContext($this, $activity);
            ActivityLogHelper::resolveDescription($this, $activity, $eventName);
        } catch (\Throwable $e) {
            // Log təsviri qurularkən xəta əsas əməliyyatı dayandırmamalıdır
            $activity->description = $activity->description
                ?: class_basename($this) . " modelində {$eventName} əməliyyatı aparıldı.";
            report($e);
        }
    }

    /**
     * Bu modelin CAUSER olduğu (törətdiyi) activity-lər.
     * Qeyd: subject üzrə axtarış üçün Activity::forSubject($model) istifadə edin.
     */
    public function activities()
    {
        return $this->morphMany(Activity::class, 'causer');
    }
}
