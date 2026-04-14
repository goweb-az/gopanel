<form method="POST" action="{{route("gopanel.seo.seo-analytics.save.form", $item)}}" id="static-form">

    <div class="row">


        @foreach($fields as $field => $label)
        <div class="col-12 mb-2">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="{{ $field }}" class="form-label mb-0">{{ $label }}</label>
                <button type="button" class="btn btn-sm btn-outline-secondary fullscreen-toggle-btn" data-target="{{ $field }}">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
            <textarea name="{{ $field }}" id="{{ $field }}" class="form-control" rows="10">{{ $item->{$field} }}</textarea>
        </div>
        @endforeach

    </div>
    <div class="row">
        <div class="col-12 text-right mt-3">
            <button type="submit" class="btn btn-primary pull-right">
                <i class="fas fa-save"></i> Yadda Saxla
            </button>
        </div>
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

@push('scripts')
<script src="/assets/gopanel/js/modules/seo.js?={{time()}}"></script>
@endpush