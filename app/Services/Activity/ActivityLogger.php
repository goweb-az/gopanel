<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Enums\System\LogChannelEnum;
use App\Helpers\Common\ActivityLogHelper;
use App\Support\Activity\ActivityMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use Throwable;

/**
 * Domen hadisəsini BİR çağırışla hər iki jurnala yazır.
 *
 *  1. `activity_log` (spatie) - paneldəki «Fəaliyyət jurnalı», subject/causer
 *     əlaqələri ilə: «hansı qeyddə nə baş verdi» sualı üçün.
 *  2. `LogService` kanalı (`file_logs` + gündəlik fayl) - texniki iz, tam kontekst.
 *
 * Mesaj mətni hər ikisində EYNİDİR və tək mənbədən gəlir:
 * `config/custom/activity_messages.php` → `{Model}.{event}` qəlibi
 * (`App\Support\Activity\ActivityMessage`). Eyni hadisə iki yerdə fərqli cümlə
 * ilə görünmür.
 *
 * Niyə ayrıca sinif: modelin `created/updated/deleted` hadisələrini
 * `LogsAdminActivity` trait-i tutur, amma domen hadisələrinin çoxu model
 * sahəsinin dəyişməsi deyil - «şəkil silindi», «status dəyişdi», «qeyd üzərinə
 * götürüldü». Onları servis öz axınında bilərəkdən yazır.
 *
 *   ActivityLogger::on(LogChannelEnum::GOPANEL)
 *       ->subject($blog)
 *       ->event('published')
 *       ->with(['url' => $url])
 *       ->write();
 *
 *   ActivityLogger::record('gopanel', $blog, 'published');   // string kanal da olar
 *
 * Trait-dən fərqli olaraq burada config bloku MƏCBURİ deyil: çağıran açıq
 * şəkildə yazmaq istəyir. Qəlib yoxdursa universal cümlə yazılır.
 */
class ActivityLogger
{
    private string $channel;

    private ?Model $subject = null;

    private string $event = 'updated';

    private string $level = 'info';

    /** @var array<string, mixed> */
    private array $context = [];

    private ?string $message = null;

    /**
     * Mesaj qəlibinin oxunduğu config qrupu. Normalda subject-in sinif adıdır;
     * açıq verilməsi model olmayan hadisə ailələri üçündür (`Integration`).
     */
    private ?string $group = null;

    /**
     * `:changes` mənbəyi: `['attributes' => [...], 'old' => [...]]`.
     *
     * @var array{attributes: array<string, mixed>, old: array<string, mixed>}|null
     */
    private ?array $changeSet = null;

    private bool $details = false;

    private bool $toActivityLog = true;

    private function __construct(LogChannelEnum|string $channel)
    {
        $this->channel = $channel instanceof LogChannelEnum ? $channel->value : $channel;
    }

    public static function on(LogChannelEnum|string $channel): self
    {
        return new self($channel);
    }

    /**
     * Hadisəni bir sətirdə yazır - fluent zəncir lazım olmayan hallar üçün.
     *
     * @param  array<string, mixed>  $context
     */
    public static function record(
        LogChannelEnum|string $channel,
        ?Model $subject,
        string $event,
        array $context = [],
        string $level = 'info',
        ?string $group = null,
    ): void {
        $logger = self::on($channel)->event($event)->with($context)->level($level);

        if ($subject !== null) {
            $logger->subject($subject);
        }

        if ($group !== null) {
            $logger->group($group);
        }

        $logger->write();
    }

    public function subject(Model $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function event(string $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function group(string $group): self
    {
        $this->group = $group;

        return $this;
    }

    /** `info` / `warning` / `error`... - `config/custom/logging.php` → `levels`. */
    public function level(string $level): self
    {
        $this->level = $level;

        return $this;
    }

    /** @param  array<string, mixed>  $context */
    public function with(array $context): self
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    /**
     * `:changes` üçün dəyişən sahələr.
     *
     * @param  array<string, mixed>  $new  dəyişikdən SONRAKI dəyərlər
     * @param  array<string, mixed>  $old  dəyişikdən ƏVVƏLKİ dəyərlər
     */
    public function changes(array $new, array $old = []): self
    {
        $this->changeSet = ['attributes' => $new, 'old' => $old];

        return $this;
    }

    /**
     * Modelin son `save()`-də dəyişən sahələrini `:changes`-ə verir.
     *
     * `save()`-dan SONRA çağırılır (`getChanges()` yalnız onda doludur). Köhnə
     * dəyərləri isə `save()` özü silir - `syncOriginal()`-dan sonra
     * `getOriginal()` artıq YENİ dəyəri qaytarır. Ona görə köhnə dəyərlər
     * `save()`-dan ƏVVƏL götürülüb ötürülür:
     *
     *   $before = $blog->getOriginal();
     *   $blog->update($data);
     *   ActivityLogger::on('gopanel')->subject($blog)->changesFromModel($blog, $before)->write();
     *
     * `$before` verilmədikdə `getOriginal()` işlədilir - bu yalnız model
     * `saved` hadisəsinin içində (sync-dən əvvəl) düzgün nəticə verir.
     *
     * @param  array<string, mixed>|null  $before
     */
    public function changesFromModel(Model $model, ?array $before = null): self
    {
        $new = $model->getChanges();
        unset($new['updated_at'], $new['created_at']);

        if ($new === []) {
            return $this;
        }

        $old = [];

        foreach (array_keys($new) as $key) {
            $old[$key] = $before !== null && array_key_exists($key, $before)
                ? $before[$key]
                : $model->getOriginal($key);
        }

        return $this->changes($new, $old);
    }

    /** Qəlibi keçib mesajı əl ilə vermək (nadir hal - qəlib üstünlükdür). */
    public function message(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Adi səviyyədə də backtrace yaz. Default söndürülüb - `LogService` ilə eyni
     * siyasət (hər log üçün detal səs-küy idi); xəta səviyyələrində onsuz da yazılır.
     */
    public function withDetails(): self
    {
        $this->details = true;

        return $this;
    }

    /**
     * Yalnız kanal jurnalı - `activity_log`-a yazma.
     * Texniki addımlar üçündür (provayder sorğusu): admin üçün məna daşımır.
     */
    public function withoutActivityLog(): self
    {
        $this->toActivityLog = false;

        return $this;
    }

    /** Hər iki sistemə yazır. Jurnal xətası əsas əməliyyatı POZA BİLMƏZ. */
    public function write(): void
    {
        try {
            $message = $this->message ?? ActivityMessage::render(
                $this->subject,
                $this->event,
                $this->changeSet,
                $this->group,
                $this->context,
            );
            $context = $this->buildContext();

            LogService::channel($this->channel, true, $this->details)->{$this->level}($message, $context);

            if ($this->toActivityLog) {
                $this->writeActivityLog($message, $context);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Bu subject üzrə yazılmış activity sətirləri (panel detal səhifəsi üçün). */
    public static function forSubject(Model $subject): Builder
    {
        return Activity::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->latest();
    }

    /**
     * Kanal jurnalının konteksti: kim, nəyin üzərində, hansı hadisə + çağıranın datası.
     * Aktor sahələri `LogService::actorContext()`-dən gəlir - xəta logları ilə eyni forma.
     *
     * @return array<string, mixed>
     */
    private function buildContext(): array
    {
        $causer = ActivityLogHelper::resolveCauser();

        $base = [
            'event'       => $this->event,
            'causer_name' => ActivityLogHelper::causerName(),
            'causer_type' => $causer ? class_basename($causer) : null,
        ] + LogService::actorContext();

        if ($this->subject !== null) {
            $base['subject_type'] = class_basename($this->subject);
            $base['subject_id']   = $this->subject->getKey();
            $base['subject_uid']  = $this->subject->getAttribute('uid');
            $base += $this->modelContext($this->subject);
        }

        // Çağıranın datası bazanın üstündən yazır - o, hadisəyə daha yaxındır
        return array_filter(
            array_merge($base, $this->context),
            fn ($value) => $value !== null && $value !== '',
        );
    }

    /**
     * Modelin `activityContext()` bloku (varsa) - `ActivityLogHelper::attachContext()` ilə eyni mənbə.
     *
     * @return array<string, mixed>
     */
    private function modelContext(Model $model): array
    {
        if (! method_exists($model, 'activityContext')) {
            return [];
        }

        try {
            $context = $model->activityContext();
        } catch (Throwable) {
            return [];
        }

        return is_array($context)
            ? array_filter($context, fn ($value) => $value !== null && $value !== '')
            : [];
    }

    /** @param  array<string, mixed>  $context */
    private function writeActivityLog(string $message, array $context): void
    {
        $logName = $this->group ?? ($this->subject ? class_basename($this->subject) : $this->channel);

        $properties = ['context' => $context];

        if ($this->changeSet !== null) {
            $properties += $this->changeSet;
        }

        $activity = activity($logName)
            ->event($this->event)
            ->withProperties($properties);

        if ($this->subject !== null) {
            $activity = $activity->performedOn($this->subject);
        }

        $causer = ActivityLogHelper::resolveCauser();

        if ($causer instanceof Model) {
            $activity = $activity->causedBy($causer);
        }

        $saved = $activity->log($message);

        // Spatie `log()` subject-in `tapActivity()`-ni çağırır; `LogsAdminActivity`
        // orada təsviri MODEL qəlibindən yenidən qurur (kontekstsiz → `:url` «-»)
        // və `properties.context`-i modelin kontekstinə dəyişir. Domen hadisəsinin
        // öz mesajı və konteksti qalsın deyə geri yazılır.
        if ($saved instanceof Activity
            && ($saved->description !== $message || ($saved->properties['context'] ?? null) !== $context)) {
            $saved->description = $message;
            $saved->properties = collect($saved->properties ?? [])->merge($properties);
            $saved->saveQuietly();
        }
    }
}
