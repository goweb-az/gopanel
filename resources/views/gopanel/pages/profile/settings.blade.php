@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Şəxsi tənzimləmələr</h4>
                    <div class="page-title-right">
                        <a class="btn btn-light" href="{{ route('gopanel.profile.index') }}">
                            <i class="fas fa-arrow-left me-1"></i> Profilə qayıt
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body p-4">
                        @if (empty($groups))
                            <div class="text-muted text-center py-4">Hələ heç bir şəxsi tənzimləmə təyin edilməyib.</div>
                        @else
                            <form method="POST" action="{{ route('gopanel.profile.settings.update') }}" id="static-form">
                                @csrf
                                @include('gopanel.component.setting-fields', ['groups' => $groups, 'name' => 'settings'])

                                <div class="d-grid d-sm-flex justify-content-sm-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Yadda saxla
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
