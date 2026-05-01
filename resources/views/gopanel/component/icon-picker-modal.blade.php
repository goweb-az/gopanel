@once
<div class="modal fade" id="globalIconPickerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" data-icon-list-url="{{ route('gopanel.general.icon-picker.list') }}" style="z-index:1070;">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="box-shadow:0 10px 40px rgba(0,0,0,.3);">
            <div class="modal-header">
                <h5 class="modal-title">İkon seçin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="px-3 pt-3">
                    <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#globalIconPickerFa" data-provider="fa" type="button" role="tab">
                                Font Awesome <span class="badge bg-light text-dark ms-1" data-count-for="fa">…</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#globalIconPickerBx" data-provider="bx" type="button" role="tab">
                                Boxicons <span class="badge bg-light text-dark ms-1" data-count-for="bx">…</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#globalIconPickerMdi" data-provider="mdi" type="button" role="tab">
                                Material Design <span class="badge bg-light text-dark ms-1" data-count-for="mdi">…</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#globalIconPickerDrp" data-provider="drp" type="button" role="tab">
                                Dripicons <span class="badge bg-light text-dark ms-1" data-count-for="drp">…</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="px-3 pt-3 pb-2 border-bottom">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" id="globalIconPickerSearch" placeholder="İkon adı və ya açar söz axtar... (məs: home, user, arrow)">
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Seçilmiş ikon: <code id="globalIconPickerCurrent" class="text-primary">—</code></small>
                        <small class="text-muted"><span id="globalIconPickerVisible">0</span> / <span id="globalIconPickerTotal">0</span> ikon</small>
                    </div>
                </div>

                <div class="tab-content" id="globalIconPickerTabContent" style="height:60vh;overflow:auto;">
                    <div class="tab-pane fade show active p-3" id="globalIconPickerFa" role="tabpanel">
                        <div class="global-icon-grid" data-provider="fa"></div>
                        <div class="global-icon-loadmore-wrap text-center pt-3" data-provider="fa" style="display:none;">
                            <button type="button" class="btn btn-sm btn-outline-primary global-icon-loadmore" data-provider="fa">
                                Daha çox göstər
                            </button>
                        </div>
                    </div>
                    <div class="tab-pane fade p-3" id="globalIconPickerBx" role="tabpanel">
                        <div class="global-icon-grid" data-provider="bx"></div>
                        <div class="global-icon-loadmore-wrap text-center pt-3" data-provider="bx" style="display:none;">
                            <button type="button" class="btn btn-sm btn-outline-primary global-icon-loadmore" data-provider="bx">
                                Daha çox göstər
                            </button>
                        </div>
                    </div>
                    <div class="tab-pane fade p-3" id="globalIconPickerMdi" role="tabpanel">
                        <div class="global-icon-grid" data-provider="mdi"></div>
                        <div class="global-icon-loadmore-wrap text-center pt-3" data-provider="mdi" style="display:none;">
                            <button type="button" class="btn btn-sm btn-outline-primary global-icon-loadmore" data-provider="mdi">
                                Daha çox göstər
                            </button>
                        </div>
                    </div>
                    <div class="tab-pane fade p-3" id="globalIconPickerDrp" role="tabpanel">
                        <div class="global-icon-grid" data-provider="drp"></div>
                        <div class="global-icon-loadmore-wrap text-center pt-3" data-provider="drp" style="display:none;">
                            <button type="button" class="btn btn-sm btn-outline-primary global-icon-loadmore" data-provider="drp">
                                Daha çox göstər
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('/assets/gopanel/js/modules/icon-picker.js?v=' . filemtime(public_path('assets/gopanel/js/modules/icon-picker.js'))) }}"></script>
@endpush
@endonce
