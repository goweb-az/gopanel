<?php

namespace App\Services\Sms;

use App\Contracts\Sms\SmsProvider;

/**
 * @deprecated Yeni kodda `App\Contracts\Sms\SmsProvider` işlədilir.
 *
 * Bu interfeys yalnız GERİYƏ UYĞUNLUQ üçün saxlanılır: starter üzərində
 * qurulmuş layihələrdə artıq `implements ProviderInterface` yazan provayderlər
 * var və onların namespace-i dəyişəndə həmin layihələr sınır. Ona görə sinif
 * silinmir - müqavilə `Contracts`-a köçürülüb, bu isə ondan törəyir.
 * Beləliklə köhnə provayder avtomatik olaraq `SmsProvider` də sayılır.
 */
interface ProviderInterface extends SmsProvider
{
}
