@extends('site.layouts.main')
@section('content')

{{-- Hero --}}
<section class="bg-primary text-white py-5">
    <div class="container text-center">
        <h1 class="display-4 fw-bold">{{ $site_title ?? config('app.name') }}</h1>
        <p class="lead">{{ $meta_description ?? 'Xoş gəlmisiniz!' }}</p>
    </div>
</section>

{{-- Son Bloqlar --}}
<section class="py-5">
    <div class="container">
        <h2 class="mb-4">Son yazılar</h2>
        <div class="row g-4">
            @forelse($blogs->take(6) as $blog)
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        @if($blog->image)
                            <img src="{{ asset($blog->image) }}" class="card-img-top" alt="{{ $blog->title }}" style="height:200px; object-fit:cover;">
                        @else
                            <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height:200px;">
                                <i class="fas fa-newspaper text-white fa-3x"></i>
                            </div>
                        @endif
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">{{ $blog->title }}</h5>
                            <p class="card-text text-muted flex-grow-1">{{ $blog->short_description_site }}</p>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="text-muted">{{ $blog->formatted_date_time }}</small>
                                <a href="{{ $blog->single_url }}" class="btn btn-sm btn-outline-primary">Oxu</a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info">Hələ heç bir bloq yazısı yoxdur.</div>
                </div>
            @endforelse
        </div>
    </div>
</section>

@endsection
