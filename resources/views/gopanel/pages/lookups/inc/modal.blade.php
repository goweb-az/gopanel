{{--
    Modal id-si `cerate-modal`-dır: bu, crud.js-in müqaviləsidir (#open-create-modal,
    .edit və #save-form-btn məhz bu id-ni açır/bağlayır). Yazılışı qəsdən
    düzəldilmir - düzəltmək bütün panelin modallarını sındırar.
--}}
<div id="cerate-modal" class="modal fade" tabindex="-1" aria-labelledby="lookup-modal-title" aria-hidden="true" data-bs-keyboard="false">
    <div class="modal-dialog modal-right-side">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lookup-modal-title">{{ $definition->title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Bağla"></button>
            </div>
            <div class="modal-body" id="form-wrap"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">İmtina et</button>
                <button type="button" class="btn btn-primary waves-effect waves-light" id="save-form-btn">Yadda saxla</button>
            </div>
        </div>
    </div>
</div>
