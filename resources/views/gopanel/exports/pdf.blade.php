{{--
    GoPanel ixraclarının ortaq PDF şablonu - `App\Support\Export\PdfExporter`.

    Sütunlar Excel ilə eynidir, çünki hər ikisi `ExportDefinition`-dən oxuyur.
    Dompdf xarici CSS faylını etibarlı yükləmir - stil burada inline yazılır
    (blade-də stil qadağasının PDF/email İSTİSNASI). Rənglər `ExportBranding`-dən
    gəlir, hardcode edilmir.

    Dəyişənlər: $branding, $accent, $headerColor, $headerText, $borderColor,
    $mutedColor, $summaryBg, $title, $sections, $summary, $filters, $generatedAt
--}}
<!doctype html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 18mm 10mm 16mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1f2937; }
        .brand { border-bottom: 2px solid {{ $accent }}; padding-bottom: 6px; margin-bottom: 10px; }
        .brand-name { font-size: 15px; font-weight: bold; color: {{ $accent }}; }
        .brand-contact { font-size: 8px; color: {{ $mutedColor }}; }
        .doc-title { font-size: 12px; font-weight: bold; margin: 0 0 2px; }
        .doc-meta { font-size: 8px; color: {{ $mutedColor }}; margin-bottom: 8px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data th { background: {{ $headerColor }}; color: {{ $headerText }}; font-size: 8px; padding: 5px 4px; border: 0.5px solid {{ $borderColor }}; text-align: left; }
        table.data td { padding: 4px; border: 0.5px solid {{ $borderColor }}; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        td.num, th.num { text-align: right; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .summary td { border: 0.5px solid {{ $borderColor }}; padding: 4px 6px; background: {{ $summaryBg }}; }
        .summary td.label { color: #475569; }
        .summary td.value { font-weight: bold; }
        .empty { padding: 14px; text-align: center; color: {{ $mutedColor }}; font-style: italic; border: 0.5px solid {{ $borderColor }}; margin-bottom: 10px; }
        .section-title { font-size: 10px; font-weight: bold; margin: 10px 0 4px; color: {{ $accent }}; }
        .footer { position: fixed; bottom: -8mm; left: 0; right: 0; font-size: 7.5px; color: #94a3b8; }
        .footer .page:after { content: counter(page); }
    </style>
</head>
<body>

<div class="brand">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="vertical-align:middle;">
                {{-- Dompdf fayl yolu oxuyur (URL yox) - bax ExportBranding::logoPath(). --}}
                @if (!empty($branding['logo_path']))
                    <img src="{{ $branding['logo_path'] }}" alt="" style="max-height:26px; margin-bottom:2px;"><br>
                @endif
                <span class="brand-name">{{ $branding['title'] }}</span>
            </td>
            <td style="text-align:right; vertical-align:middle;">
                <span class="brand-contact">{{ $branding['contact'] }}</span>
            </td>
        </tr>
    </table>
</div>

<div class="doc-title">{{ $title }}</div>
<div class="doc-meta">
    Yaradılma: {{ $generatedAt }}
    @foreach ($filters as $filterLabel => $filterValue)
        &nbsp;·&nbsp; {{ $filterLabel }}: {{ $filterValue }}
    @endforeach
</div>

@if (!empty($summary))
    <table class="summary">
        <tr>
            @foreach ($summary as $summaryLabel => $summaryValue)
                <td class="label">{{ $summaryLabel }}</td>
                <td class="value">{{ $summaryValue }}</td>
            @endforeach
        </tr>
    </table>
@endif

@foreach ($sections as $section)
    @if ($section['title'] !== '')
        <div class="section-title">{{ $section['title'] }}</div>
    @endif

    @if (count($section['rows']) === 0)
        <div class="empty">Seçilmiş süzgəclərə uyğun məlumat yoxdur.</div>
    @else
        <table class="data">
            <thead>
            <tr>
                @foreach ($section['headings'] as $index => $heading)
                    <th class="{{ in_array($index, $section['numeric'], true) ? 'num' : '' }}">{{ $heading }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach ($section['rows'] as $row)
                <tr>
                    @foreach ($row as $index => $value)
                        <td class="{{ in_array($index, $section['numeric'], true) ? 'num' : '' }}">{{ $value }}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endforeach

<div class="footer">
    {{ $branding['title'] }} · {{ $title }} · səhifə <span class="page"></span>
</div>

</body>
</html>
