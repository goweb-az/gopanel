@extends('gopanel.layouts.main')
@section('content') 
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{__("title.login")}}</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Utility</a></li>
                            <li class="breadcrumb-item active">Starter Page</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

        <!-- Gopanel v1.0.1 Update -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm me-3">
                                <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-24">
                                    <i class="bx bx-rocket"></i>
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-1">Gopanel-ə xoş gəlmisiniz! 🎉</h5>
                                <p class="text-muted mb-0">Bu mesaj v1.0.1 yeniləməsi ilə əlavə edildi. Yeniləmə sistemi uğurla işləyir!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div> <!-- container-fluid -->
</div>
<!-- End Page-content -->
@endsection