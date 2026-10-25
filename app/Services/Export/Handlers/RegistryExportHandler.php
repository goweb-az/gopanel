<?php

declare(strict_types=1);

namespace App\Services\Export\Handlers;

use App\Exports\Gopanel\ExportDefinition;
use App\Services\Export\ExportContext;
use App\Support\Export\ExportRegistry;

/**
 * Konteksin `section` açarını `ExportRegistry`-də axtaran hazır handler.
 *
 * NİYƏ: reyestrdə artıq qeydiyyatdan keçmiş tərif üçün ayrıca handler sinfi
 * yazmaq lazım gəlməsin. `config/custom/export.php`:
 *
 * ```php
 * 'handlers' => [
 *     'activity.activity-logs' => \App\Services\Export\Handlers\RegistryExportHandler::class,
 * ],
 * ```
 *
 * Bölmə açarı ilə reyestr açarı eyni olmalıdır.
 */
final class RegistryExportHandler extends DefinitionExportHandler
{
    protected function definition(ExportContext $context): ExportDefinition
    {
        return ExportRegistry::make($context->section, $context->params);
    }
}
