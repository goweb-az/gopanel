@extends('site.layouts.main')
@section('content')

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="mb-4">Əlaqə</h1>

                @if($contactInfo)
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="row g-3">
                                @if($contactInfo->address)
                                    <div class="col-12">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-map-marker-alt text-primary fs-4 me-3 mt-1"></i>
                                            <div>
                                                <h6 class="mb-0">Ünvan</h6>
                                                <p class="text-muted mb-0">{{ $contactInfo->address }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if($contactInfo->phone)
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-phone text-primary fs-4 me-3 mt-1"></i>
                                            <div>
                                                <h6 class="mb-0">Telefon</h6>
                                                <a href="tel:{{ $contactInfo->phone }}" class="text-muted text-decoration-none">{{ $contactInfo->phone }}</a>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if($contactInfo->email)
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-envelope text-primary fs-4 me-3 mt-1"></i>
                                            <div>
                                                <h6 class="mb-0">E-poçt</h6>
                                                <a href="mailto:{{ $contactInfo->email }}" class="text-muted text-decoration-none">{{ $contactInfo->email }}</a>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Sosial --}}
                @if($socials->count())
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Sosial şəbəkələrdə bizi izləyin</h5>
                            <div class="d-flex gap-3 fs-3">
                                @foreach($socials as $social)
                                    @if($social->is_active)
                                        <a href="{{ $social->url }}" class="text-primary" @if($social->target_blank) target="_blank" @endif title="{{ $social->name }}">
                                            {!! $social->icon !!}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</section>

@endsection
