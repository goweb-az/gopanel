
<footer class="bg-dark text-light py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-3">
                <h5>{{ config('app.name', 'Gopanel') }}</h5>
                @if($contactInfo)
                    <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i>{{ $contactInfo->address ?? '' }}</p>
                    <p class="mb-1"><i class="fas fa-phone me-2"></i>{{ $contactInfo->phone ?? '' }}</p>
                    <p class="mb-1"><i class="fas fa-envelope me-2"></i>{{ $contactInfo->email ?? '' }}</p>
                @endif
            </div>
            <div class="col-md-4 mb-3">
                <h5>Sosial şəbəkələr</h5>
                <div class="d-flex gap-3 fs-4">
                    @foreach($socials as $social)
                        @if($social->is_active)
                            <a href="{{ $social->url }}" class="text-light" @if($social->target_blank) target="_blank" @endif title="{{ $social->name }}">
                                @if($social->icon_type === 'font' || $social->icon_type === 'svg')
                                    {!! $social->icon !!}
                                @else
                                    {{ $social->name }}
                                @endif
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <h5>Dillər</h5>
                @foreach($languages as $lang)
                    <a href="{{ $lang->switchLanguage() }}" class="btn btn-sm {{ $currentLocale === $lang->code ? 'btn-light' : 'btn-outline-light' }} me-1">
                        {{ $lang->upper_code }}
                    </a>
                @endforeach
            </div>
        </div>
        <hr class="border-secondary">
        <p class="text-center text-muted mb-0">&copy; {{ date('Y') }} {{ config('app.name', 'Gopanel') }}. Bütün hüquqlar qorunur.</p>
    </div>
</footer>
