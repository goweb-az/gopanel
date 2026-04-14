@extends('site.layouts.main')
@section('content')

<section class="py-5">
    <div class="container">
        <h1 class="mb-4">Bloqlar</h1>
        <div class="row g-4">
            @forelse($list as $blog)
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
                                <small class="text-muted">
                                    {{ $blog->formatted_date_time }}
                                    <span class="ms-2"><i class="fas fa-eye"></i> {{ $blog->views ?? 0 }}</span>
                                </small>
                                <a href="{{ $blog->single_url }}" class="btn btn-sm btn-outline-primary">Ətraflı</a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info">Bloq yazısı tapılmadı.</div>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($list->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $list->links() }}
            </div>
        @endif
    </div>
</section>

@endsection
