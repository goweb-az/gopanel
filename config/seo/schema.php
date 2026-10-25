<?php

/*
|--------------------------------------------------------------------------
| JSON-LD (schema.org) qrafının sabit dəyərləri
|--------------------------------------------------------------------------
|
| `App\Services\Seo\SchemaService` qovşaqları bazadan (SiteSetting,
| ContactInfo, Social, meta) qurur. Bazada saxlanmayan, amma layihədən
| layihəyə dəyişən dəyərlər buradadır - kodda şəhər/ölkə hardcode edilmir.
*/

return [

    'organization' => [
        // `Organization`, `LocalBusiness`, `Corporation`, `EducationalOrganization` ...
        'type'             => env('SEO_SCHEMA_ORG_TYPE', 'Organization'),
        // Boş qalsa `SiteSetting.company_name` → `config('app.name')`
        'name'             => env('SEO_SCHEMA_ORG_NAME'),
        'address_locality' => env('SEO_SCHEMA_ADDRESS_LOCALITY', 'Bakı'),
        'address_country'  => env('SEO_SCHEMA_ADDRESS_COUNTRY', 'AZ'),
        'contact_type'     => 'customer support',
    ],

    /*
     * Hər səhifəyə avtomatik əlavə olunan baza qovşaqları.
     * Dəstəklənənlər: `organization`, `website`.
     */
    'base_nodes' => ['organization', 'website'],

    /*
     * `ItemList` qovşağında maksimum element sayı - çox elementli siyahı
     * səhifəni ağırlaşdırır, axtarış sistemləri onsuz da kəsir.
     */
    'item_list_limit' => 50,
];
