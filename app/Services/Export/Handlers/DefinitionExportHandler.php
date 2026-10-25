<?php

declare(strict_types=1);

namespace App\Services\Export\Handlers;

use App\Contracts\Export\ExportHandler;
use App\Exports\Gopanel\ExportDefinition;
use App\Services\Export\ExportContext;
use App\Services\Export\ExportResult;
use App\Support\Export\ExportRenderer;

/**
 * `ExportHandler` müqaviləsini `ExportDefinition` üzərindən yerinə yetirən körpü.
 *
 * NİYƏ: starter-də iki yanaşma var idi - queue-lu `ExportHandler` (fayl diskə,
 * nəticə `ExportResult`) və sinxron `ExportDefinition` (endirmə/mail). Hər
 * handler öz Excel-ini qurmasın deyə bu sinif tərifi `ExportRenderer`-ə verir:
 * queue-lu fayl da brauzerdən endirilənlə eyni brendli sənəd olur.
 *
 * Törəmə sinif yalnız `definition()` yazır:
 *
 * ```php
 * final class BlogExportHandler extends DefinitionExportHandler
 * {
 *     protected function definition(ExportContext $context): ExportDefinition
 *     {
 *         return new BlogExport($context->params);
 *     }
 * }
 * ```
 */
abstract class DefinitionExportHandler implements ExportHandler
{
    public function __construct(protected readonly ExportRenderer $renderer = new ExportRenderer())
    {
    }

    abstract protected function definition(ExportContext $context): ExportDefinition;

    public function handle(ExportContext $context): ExportResult
    {
        $export = $this->definition($context);

        $rows = $this->renderer->store(
            $export,
            $context->isPdf() ? 'pdf' : 'excel',
            $context->disk,
            $context->filePath,
        );

        return new ExportResult($export->title(), $rows, $export->summary());
    }
}
