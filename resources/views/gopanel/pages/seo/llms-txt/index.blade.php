@extends('gopanel.layouts.main')
@section('content') 
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">LLMs.txt</h4>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">LLM Məlumat Faylı</h5>
                        <p class="text-muted mb-0 mt-1">Bu fayl AI/LLM botlarına saytınız haqqında məlumat verir. <code>/llms.txt</code> ünvanında əlçatan olacaq.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{route('gopanel.seo.llms-txt.save.form', $item)}}" id="static-form">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="content" class="form-label mb-0">Məzmun</label>
                                    <button type="button" class="btn btn-sm btn-outline-secondary fullscreen-toggle-btn" data-target="content">
                                        <i class="fas fa-expand"></i>
                                    </button>
                                </div>
                                <textarea class="form-control" id="content" name="content" rows="20" style="font-family: monospace; font-size: 13px;">{{ $item->content }}</textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Yadda Saxla
                                </button>
                            </div>
                        </form>

                        <!-- Fullscreen overlay -->
                        <div id="fullscreen-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:99999; background:#1a1d21;">
                            <div style="display:flex; flex-direction:column; height:100%; padding:16px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                                    <h5 id="fullscreen-label" style="color:#e2e8f0; margin:0; font-weight:600;"></h5>
                                    <button type="button" id="fullscreen-close-btn" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-compress"></i> Bağla
                                    </button>
                                </div>
                                <textarea id="fullscreen-textarea" style="flex:1; width:100%; resize:none; background:#0d1117; color:#c9d1d9; border:1px solid #30363d; border-radius:8px; padding:16px; font-family:'Fira Code',monospace; font-size:14px; line-height:1.6; outline:none;"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!--end col-->
        </div>
        
    </div> <!-- container-fluid -->
</div>
<!-- End Page-content -->
@endsection
@push('scripts')
<script src="/assets/gopanel/js/modules/seo.js?={{time()}}"></script>
@endpush
