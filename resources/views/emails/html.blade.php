{{--
    Hazır HTML məktub - məzmun olduğu kimi çap olunur, qəlib əlavə edilmir.

    DİQQƏT: `{!! !!}` işlədilir, yəni HTML escape OLUNMUR. Bura yalnız
    layihənin ÖZ redaktorundan (kampaniya/şablon) gələn məzmun ötürülməlidir -
    istifadəçidən birbaşa gələn mətn üçün `emails.basic` işlədilir.
--}}
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $data['subject'] ?? config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background:#ffffff;">
{!! $data['html'] ?? '' !!}
</body>
</html>
