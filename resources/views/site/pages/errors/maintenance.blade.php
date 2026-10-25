{{--
    Sayt bağlı olanda (site_status = 0, SITE_STATUS_ENFORCE=true) 503 səhifəsi.

    Bilərəkdən müstəqildir: sayt layout-una, menyuya, tərcümə cədvəlinə
    bağlı deyil - baza/keş problemi zamanı da açılmalıdır. Görünüş
    `site.component.under_construction`-dan gəlir; törəmə layihə onu dəyişir.
--}}
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ config('app.name') }} - Sayt yenilənir</title>
</head>
<body>
    @include('site.component.under_construction')
</body>
</html>
