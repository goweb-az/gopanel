{{-- Qrafikin altındakı təfərrüat sətirləri.
     Ayrıca partial-dır: səhifə yenilənəndə yalnız bu hissə əvəz olunur,
     ApexCharts obyekti isə yerində qalır (yenidən qurulsa animasiya
     hər dəfə sıfırdan başlayardı). --}}
<ul class="list-unstyled gp-sys-meta mb-0">
    @foreach ($metric['rows'] as $row)
        <li>
            <span>{{ $row['label'] }}</span>
            <b>{{ $row['value'] }}</b>
        </li>
    @endforeach
</ul>

@if (!empty($metric['note']))
    <p class="text-muted small mb-0 mt-2">{{ $metric['note'] }}</p>
@endif
