@if (isset($canonical))
<link rel="canonical" href="{{ $canonical }}" />
@endif
@if (isset($alternates) && is_array($alternates))
@foreach ($alternates as $lang => $url)
<link rel="alternate" hreflang="{{ $lang }}" href="{{ $url }}" />
@endforeach
@endif
