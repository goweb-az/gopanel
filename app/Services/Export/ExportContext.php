<?php

declare(strict_types=1);

namespace App\Services\Export;

/**
 * Export handler-inə ötürülən DƏYİŞMƏZ kontekst: hansı bölmə, hansı filtrlər,
 * faylın hara və hansı formatda yazılacağı.
 *
 * Modelə bağlı DEYİL - queue-lu export job-u da, sinxron controller çağırışı da
 * eyni obyekti qurur. Layihədə `ExportJob` kimi model varsa, onu `meta` massivinə
 * id ilə qoymaq kifayətdir (job payload-ında model daşımırıq).
 *
 * @see \App\Services\Export\Contracts\ExportHandler
 */
final class ExportContext
{
    /**
     * @param  string  $section    Export edilən bölmə açarı (məs. `blogs`, `users`)
     * @param  array<string, mixed>  $params  Filtrlər (from/to, status, search...)
     * @param  string  $disk       Faylın yazılacağı disk (private olmalıdır)
     * @param  string  $filePath   Diskdəki tam yol
     * @param  string  $extension  `xlsx` | `csv` | `pdf`
     * @param  array<string, mixed>  $meta   Sərbəst əlavə (job_id, user_id, locale...)
     */
    public function __construct(
        public readonly string $section,
        public readonly array $params,
        public readonly string $disk,
        public readonly string $filePath,
        public readonly string $extension,
        public readonly array $meta = [],
    ) {
    }

    public function isPdf(): bool
    {
        return $this->extension === 'pdf';
    }

    public function isExcel(): bool
    {
        return in_array($this->extension, ['xlsx', 'xls', 'csv'], true);
    }

    /** Filtrdən dəyər (yoxdursa default). */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    /** Metadan dəyər (yoxdursa default). */
    public function meta(string $key, mixed $default = null): mixed
    {
        return $this->meta[$key] ?? $default;
    }

    /**
     * Tarix aralığı - HƏMİŞƏ dolu qaytarır (verilməyibsə son 30 gün).
     *
     * @return array{0: string, 1: string} [from, to] - `Y-m-d`
     */
    public function dates(int $defaultDays = 30): array
    {
        return [
            (string) ($this->params['from'] ?? now()->subDays($defaultDays - 1)->format('Y-m-d')),
            (string) ($this->params['to'] ?? now()->format('Y-m-d')),
        ];
    }

    /** Fayl adı (yolsuz) - mail əlavəsi və yükləmə başlığı üçün. */
    public function fileName(): string
    {
        return basename($this->filePath);
    }

    /** `01.07.2026 - 30.07.2026` şəklində oxunaqlı aralıq. */
    public function dateRange(): string
    {
        [$from, $to] = $this->dates();

        return date('d.m.Y', strtotime($from)) . ' - ' . date('d.m.Y', strtotime($to));
    }
}
