<?php

/*
|--------------------------------------------------------------------------
| API ümumi tənzimləmələri
|--------------------------------------------------------------------------
|
| `App\Support\Api\PaginationParams` buradan oxuyur. Niyə maksimum var:
| kənardan gələn `per_page=100000` bütün cədvəli bir sorğuda yaddaşa yükləyir
| (.claude/rules/04-api.md §3).
|
*/

return [

    'pagination' => [
        'per_page'     => (int) env('API_PER_PAGE', 20),
        'max_per_page' => (int) env('API_MAX_PER_PAGE', 100),
    ],

];
