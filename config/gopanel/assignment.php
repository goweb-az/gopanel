<?php

/**
 * Qeyd təyinatı («bu qeydlə kim məşğuldur») - `App\Traits\System\HasAuthors`.
 *
 * Sənəd: docs/authors-settings.md
 */
return [

    /*
     * Təyinat neçə saatdan sonra «köhnəlmiş» sayılır.
     *
     * Köhnəlmiş təyinat avtomatik bağlanmır - sadəcə onu üzərinə götürmək
     * ayrıca `<prefix>.assign.takeover` icazəsi tələb etmir. Səbəb: admin işi
     * buraxmağı unudanda qeyd əbədi «onun» qalmasın. 0 = heç vaxt köhnəlmir.
     */
    'ttl_hours' => (int) env('GOPANEL_ASSIGNMENT_TTL_HOURS', 8),

];
