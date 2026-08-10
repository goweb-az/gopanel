<?php

declare(strict_types=1);

namespace App\Services\Export\Contracts;

use App\Services\Export\ExportContext;
use App\Services\Export\ExportResult;

/**
 * Bir export tipini hazırlayan strategiya.
 *
 * Yeni export tipi əlavə etmək üçün:
 *  1) Bu interfeysi implement edən sinif yaz → `app/Services/Export/Handlers/`.
 *  2) `app/Enums/Export/ExportType`-a case + `handlerClass()` sətri əlavə et
 *     (və ya `config/custom/export.php` → `handlers` xəritəsinə yaz).
 *
 * Export-u işlədən job/controller-ə TOXUNMAQ LAZIM DEYİL - o, handler-i
 * xəritədən tapıb çağırır.
 */
interface ExportHandler
{
    /**
     * Faylı yaradır və konteksdəki `filePath`-ə yazır.
     */
    public function handle(ExportContext $context): ExportResult;
}
