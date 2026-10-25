<div id="translation-excel-modal" class="modal fade" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tərcümələri Excel-dən yüklə</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Bağla"></button>
            </div>
            <div class="modal-body">
                <form id="translation-excel-form" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="form-label" for="translation-excel-file">Excel faylı (.xlsx)</label>
                            <input type="file" name="file" id="translation-excel-file" class="form-control" accept=".xlsx">
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label" for="translation-excel-mode">Rejim</label>
                            <select name="mode" id="translation-excel-mode" class="form-select">
                                <option value="missing">Yalnız çatışmayanları əlavə et</option>
                                <option value="overwrite">Mövcud mətnləri də yenilə</option>
                            </select>
                        </div>
                    </div>

                    <div class="alert alert-light border small mb-3 translation-excel-format">
                        <div class="fw-semibold mb-1">Format «Excel-ə çıxar» ilə eynidir - yüklədiyiniz faylı dəyişmədən geri yükləyə bilərsiniz.</div>
                        <div>Sütunlar: <code>platform</code> · <code>page</code> · <code>group</code> · <code>key</code> · sonra hər dil üçün bir sütun (<code>az</code>, <code>en</code>, <code>ru</code>...).</div>
                        <div><code>group</code>: qısa mətn üçün <code>title</code>, uzun mətn üçün <code>content</code>.</div>
                        <div>Boş dil xanası həmin dili dəyişmir. Səhifə kataloqda yoxdursa sətir rədd edilir.</div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="fw-semibold">Nümunə fayl:</span>
                        <a href="{{ asset('example-files/translations-example.xlsx') }}" download class="btn btn-outline-success btn-sm">
                            <i class="fas fa-file-excel"></i> translations-example.xlsx
                        </a>
                    </div>

                    <div class="translation-excel-result" id="translation-excel-result"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
                <button type="button" class="btn btn-primary" id="translation-excel-submit">
                    <span class="button-label"><i class="bx bx-import"></i> Yüklə</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>
