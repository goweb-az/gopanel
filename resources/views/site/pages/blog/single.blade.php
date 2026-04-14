@extends('site.layouts.main')
@section('content')

<article class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                {{-- Blog image --}}
                @if($blog->image)
                    <img src="{{ asset($blog->image) }}" class="img-fluid rounded mb-4 w-100" alt="{{ $blog->title }}" style="max-height:400px; object-fit:cover;">
                @endif

                {{-- Title --}}
                <h1 class="mb-3">{{ $blog->title }}</h1>

                {{-- Meta info --}}
                <div class="d-flex gap-3 text-muted mb-4">
                    @if($blog->formatted_date_time)
                        <span><i class="fas fa-calendar-alt me-1"></i>{{ $blog->formatted_date_time }}</span>
                    @endif
                    <span><i class="fas fa-eye me-1"></i>{{ $blog->views ?? 0 }} baxış</span>
                </div>

                {{-- Content --}}
                <div class="blog-content">
                    {!! $blog->description !!}
                </div>

                {{-- Back --}}
                <div class="mt-4 pt-3 border-top">
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Geri
                    </a>
                </div>

            </div>
        </div>
    </div>
</article>

@endsection
