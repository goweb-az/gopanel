@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Haqqımızda</h4>
                </div>
            </div>
        </div>

        <form method="POST" action="{{$route}}" id="static-form" enctype="multipart/form-data">
            <div class="row">
                <div class="col-xl-8">
                    <div class="card">
                        <div class="card-body">
                            <ul class="nav nav-tabs" role="tablist">
                                @foreach ($languages as $lang)
                                <li class="nav-item">
                                    <a class="nav-link {{$loop->first ? 'active' : ''}}" data-bs-toggle="tab" href="#lang_key_{{$lang->code}}" role="tab">
                                        <span>{{$lang->upper_code}}</span>
                                    </a>
                                </li>
                                @endforeach
                            </ul>

                            <div class="tab-content tab-p text-muted">
                                @foreach ($languages as $lang)
                                <div class="tab-pane {{$loop->first ? 'active' : ''}}" id="lang_key_{{$lang->code}}" role="tabpanel">
                                    <div class="mb-3">
                                        <label class="form-label">Başlıq <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="title[{{$lang->code}}]" value="{{$item->getTranslation('title', $lang->code, true)}}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Məlumat</label>
                                        <textarea class="form-control ckeditor" rows="8" name="description[{{$lang->code}}]">{{$item->getTranslation('description', $lang->code, true)}}</textarea>
                                    </div>
                                    @include("gopanel.component.meta", ['lang' => $lang->code])
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header bg-transparent border-bottom">
                            <h5 class="card-title mb-0">Şəkil</h5>
                        </div>
                        <div class="card-body">
                            @if ($item->image_url ?? false)
                            <div class="mb-3 text-center">
                                <img src="{{ $item->image_url }}" alt="Haqqımızda" class="img-fluid rounded" style="max-height:220px;object-fit:cover;">
                            </div>
                            @endif
                            <div class="input-group">
                                <input type="file" class="form-control" id="aboutImage" name="image" accept="image/*" aria-describedby="aboutImageBtn">
                                <a class="btn btn-primary" href="{{$item->image_url ?? 'javascript:void(0)'}}" target="_blank" id="aboutImageBtn">
                                    <i class="fas fa-eye"></i> Bax
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body text-end">
                            @can('gopanel.about-us.edit')
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-1"></i> Yadda saxla
                            </button>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
