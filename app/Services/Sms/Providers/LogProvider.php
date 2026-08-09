<?php

namespace App\Services\Sms\Providers;

use App\Services\Activity\LogService;
use App\Services\Sms\ProviderInterface;

/**
 * Lokal/test provayder - heç nə göndərmir, yalnız `logs/sms/` faylına yazır.
 *
 * `.env` → `SMS_PROVIDER` verilməyəndə default budur: yeni layihə ilk gündən
 * işləyir və heç kimə təsadüfi SMS getmir.
 */
class LogProvider implements ProviderInterface
{
    protected LogService $logging;

    public function __construct()
    {
        $this->logging = new LogService('sms');
    }

    public function send($phone, $message)
    {
        $this->logging->info("[LOG PROVIDER] SMS göndərilmədi (yalnız jurnal): {$phone}", [
            'phone'   => $phone,
            'message' => $message,
        ]);

        return $this;
    }
}
