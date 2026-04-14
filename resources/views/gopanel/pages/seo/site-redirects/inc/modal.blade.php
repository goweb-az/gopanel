<div id="cerate-modal" class="modal fade" data-bs-keyboard="false" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-right-side">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel">Link Yönləndirmələri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="form-wrap">
                
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">
                    İmtina et
                </button>
                <button type="button" class="btn btn-primary waves-effect waves-light" id="save-form-btn">
                    Yadda saxl
                </button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
@push('js_stack')
    <script>
        function toggleRegexFlags(select) {
            var wrap = document.getElementById('regex_flags_wrap');
            wrap.style.display = (select.value === 'regex') ? '' : 'none';
        }

        // Səhifə yüklənəndə də yoxlayaq
        document.addEventListener('DOMContentLoaded', function () {
            toggleRegexFlags(document.getElementById('match_type'));
        });
    </script>
@endpush