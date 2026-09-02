<?php

declare(strict_types=1);

namespace App\Services\Gopanel\Seo;

use App\DTOs\Gopanel\ContentPayload;
use App\Models\Seo\LlmsTxt;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Cache;

/**
 * `llms.txt` məzmununun saxlanması.
 *
 * NİYƏ servis: fayl `LlmsTxt::getCached()` ilə `rememberForever` saxlanılır.
 * Keş silinməsə panel yeni mətni göstərir, `/llms.txt` isə köhnəsini verir -
 * heç bir xəta olmadan. İki addım bir yerdə saxlanılır ki, ayrılmasın.
 */
class LlmsTxtService
{
    public function __construct(private readonly BaseRepository $repository)
    {
    }

    public function save(LlmsTxt $item, ContentPayload $payload): LlmsTxt
    {
        /** @var LlmsTxt $item */
        $item = $this->repository->save($item, $payload->attributes);

        Cache::forget('llms_txt');

        return $item;
    }
}
