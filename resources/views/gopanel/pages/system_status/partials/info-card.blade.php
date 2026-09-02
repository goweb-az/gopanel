{{-- «Etiket → dəyər» kartı. Sətirlər servisdə hazırlanır, burada yalnız çap olunur. --}}
<div class="card h-100 mb-0">
    <div class="card-body">
        <h5 class="card-title mb-3">
            <i class="{{ $card['icon'] }} text-primary me-1"></i> {{ $card['title'] }}
        </h5>

        <ul class="list-unstyled gp-sys-meta mb-0">
            @foreach ($card['rows'] as $row)
                <li>
                    <span>{{ $row['label'] }}</span>
                    @if (!empty($row['tone']))
                        <b class="text-{{ $row['tone'] }}">{{ $row['value'] }}</b>
                    @else
                        <b>{{ $row['value'] }}</b>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
