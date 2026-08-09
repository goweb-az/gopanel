<?php

declare(strict_types=1);

namespace App\Support\Gopanel;

use Carbon\CarbonImmutable;

/**
 * İnfo kartlarının "köhnə dövrə görə artım/azalma" hesablaması üçün dövr.
 *
 * Filtrdə tarix seçilibsə həmin aralıq, seçilməyibsə son N gün götürülür.
 * `previous()` eyni uzunluqda dərhal əvvəlki aralığı qaytarır - müqayisə
 * beləliklə həmişə bərabər uzunluqlu iki dövr arasında olur.
 */
final class PeriodRange
{
    public function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly bool $custom = false,
    ) {
    }

    /**
     * Filtrdən dövr qurur.
     *
     * @param  string|null  $from  Y-m-d və ya d.m.Y
     * @param  string|null  $to    Y-m-d və ya d.m.Y
     */
    public static function fromFilters(?string $from, ?string $to, int $defaultDays = 30): self
    {
        $start = self::parse($from);
        $end   = self::parse($to);

        if ($start === null && $end === null) {
            $end   = CarbonImmutable::now()->endOfDay();
            $start = $end->subDays($defaultDays - 1)->startOfDay();

            return new self($start, $end);
        }

        $end   = ($end ?? CarbonImmutable::now())->endOfDay();
        $start = ($start ?? $end->subDays($defaultDays - 1))->startOfDay();

        return new self($start, $end, true);
    }

    /** Eyni uzunluqda dərhal əvvəlki aralıq. */
    public function previous(): self
    {
        $length = $this->days();

        return new self(
            $this->from->subDays($length)->startOfDay(),
            $this->from->subDay()->endOfDay(),
            $this->custom,
        );
    }

    /** Aralığın gün sayı (hər iki uc daxil). */
    public function days(): int
    {
        return max(1, $this->from->startOfDay()->diffInDays($this->to->startOfDay()) + 1);
    }

    /** Kart altında göstərilən izah: "son 30 gün" / "01.07.2026 - 28.07.2026". */
    public function label(): string
    {
        if ($this->custom) {
            return $this->from->format('d.m.Y') . ' - ' . $this->to->format('d.m.Y');
        }

        return 'son ' . $this->days() . ' gün';
    }

    /**
     * Aralığın bütün günləri (Y-m-d) - qrafik seriyasında boş günlər də olsun deyə.
     *
     * @return string[]
     */
    public function dayKeys(): array
    {
        $keys = [];
        $day  = $this->from->startOfDay();

        while ($day->lessThanOrEqualTo($this->to)) {
            $keys[] = $day->format('Y-m-d');
            $day    = $day->addDay();
        }

        return $keys;
    }

    private static function parse(?string $value): ?CarbonImmutable
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach (['d.m.Y', 'Y-m-d'] as $format) {
            $parsed = CarbonImmutable::canBeCreatedFromFormat($value, $format)
                ? CarbonImmutable::createFromFormat($format, $value)
                : null;

            if ($parsed) {
                return $parsed;
            }
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
