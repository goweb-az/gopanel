<?php

declare(strict_types=1);

namespace App\Support\Import;

use JsonException;

/**
 * Uğursuz import elementlərinin siyahısı.
 *
 * PRİNSİP: bir sətrin xətası bütün importu dayandırmır, amma **heç vaxt
 * səssizcə itmir** - hər biri səbəbi və mərhələsi ilə yığılır, JSON faylına
 * yazılır (`toJson()`) və növbəti dəfə `fromJson()` + `externalIds()` ilə
 * yalnız uğursuzlar təkrar işlədilir (`--retry-failed` naxışı).
 *
 * Saf sinifdir - fayla yazmaq çağıranın işidir (`Storage::put($path, $bucket->toJson())`),
 * ona görə istənilən diskdə və testdə eyni işləyir.
 */
final class FailureBucket
{
    /** @var array<int, array<string, mixed>> */
    private array $rows = [];

    /**
     * @param  int|string  $externalId  mənbədəki identifikator (sətir nömrəsi, xarici id, SKU)
     * @param  string  $stage  hansı mərhələdə yıxıldı (`validate`, `save`, `image`...)
     * @param  array<string, mixed>  $context  əlavə sahələr (əsas açarları üstələmir)
     */
    public function add(int|string $externalId, string $stage, string $reason, array $context = []): void
    {
        $this->rows[] = array_merge($context, [
            'external_id' => $externalId,
            'stage'       => $stage,
            'reason'      => $reason,
            'failed_at'   => now()->toIso8601String(),
        ]);
    }

    public function isEmpty(): bool
    {
        return $this->rows === [];
    }

    public function count(): int
    {
        return count($this->rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->rows;
    }

    /**
     * Təkrar cəhd üçün unikal identifikatorlar.
     *
     * @return array<int, int|string>
     */
    public function externalIds(): array
    {
        return array_values(array_unique(array_column($this->rows, 'external_id')));
    }

    /**
     * Mərhələ üzrə say - yekun cədvəldə «harada yıxılır?» sualına cavab.
     *
     * @return array<string, int>
     */
    public function countByStage(): array
    {
        $counts = [];

        foreach ($this->rows as $row) {
            $stage = (string) ($row['stage'] ?? '');
            $counts[$stage] = ($counts[$stage] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public static function fromArray(array $rows): self
    {
        $bucket = new self();

        foreach ($rows as $row) {
            if (is_array($row)) {
                $bucket->rows[] = $row;
            }
        }

        return $bucket;
    }

    /** Fayla yazmaq üçün - Azərbaycan hərfləri `\u` kimi qaçırılmır, oxunaqlı qalır. */
    public function toJson(): string
    {
        return (string) json_encode($this->rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @throws JsonException fayl pozuqdursa - səssizcə boş bucket qaytarmaq
     *                       uğursuzları itirmək deməkdir
     */
    public static function fromJson(string $json): self
    {
        $rows = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return self::fromArray(is_array($rows) ? $rows : []);
    }
}
