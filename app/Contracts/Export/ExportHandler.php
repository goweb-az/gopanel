<?php

declare(strict_types=1);

namespace App\Contracts\Export;

use App\Services\Export\ExportContext;
use App\Services\Export\ExportResult;

/**
 * Bir export tipini hazırlayan strategiya.
 *
 * NİYƏ `app/Contracts/` altındadır: bu, servisin daxili detalı deyil, kənar
 * kodun (törəmə layihədəki handler-lərin) implement etdiyi müqavilədir -
 * bütün müqavilələr tək qovluqda axtarılsın deyə buradadır.
 *
 * Yeni export tipi əlavə etmək üçün:
 *  1) Bu interfeysi implement edən sinif yaz → `app/Services/Export/Handlers/`.
 *  2) `config/custom/export.php` → `handlers` xəritəsinə sətir əlavə et.
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
