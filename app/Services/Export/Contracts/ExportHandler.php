<?php

declare(strict_types=1);

namespace App\Services\Export\Contracts;

use App\Contracts\Export\ExportHandler as ExportHandlerContract;

/**
 * @deprecated Yeni kodda `App\Contracts\Export\ExportHandler` işlədilir.
 *
 * Müqavilə `app/Contracts/` altına köçürülüb. Bu interfeys silinmir, çünki
 * starter üzərində qurulmuş layihələrdə onu implement edən handler-lər var -
 * namespace dəyişsəydi həmin layihələr sınardı. Törəmə interfeys olduğu üçün
 * köhnə handler yeni müqaviləyə də uyğun gəlir.
 */
interface ExportHandler extends ExportHandlerContract
{
}
