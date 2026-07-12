@php
    $bulkPlatforms = \App\Enums\Gopanel\TranslationPlatfroms::cases();
    $bulkGroups    = \App\Enums\Gopanel\TranslationGroups::cases();
    $bulkFirstPlatform = $bulkPlatforms[0]->value ?? 'website';
    $bulkFirstPages    = $allPages[$bulkFirstPlatform] ?? ['general' => 'Ümumi'];
@endphp
<div id="bulk-import-modal" class="modal fade" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tərcümələrin toplu idxalı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bulk-import-form" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">İdxal növü</label>
                            <select name="import_type" id="bulk-import-type" class="form-select bulk-select2">
                                <option value="json">JSON</option>
                                <option value="xlsx">XLSX</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rejim</label>
                            <select name="mode" class="form-select bulk-select2">
                                <option value="update">Yenilə (update)</option>
                                <option value="skip">Ötür (skip)</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Dil</label>
                            <select name="locale" class="form-select bulk-select2">
                                @foreach ($languages as $language)
                                    <option value="{{ $language->code }}">{{ $language->code }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Platforma</label>
                            <select name="platform" id="bulk-import-platform" class="form-select bulk-select2 translation-platform-select">
                                @foreach ($bulkPlatforms as $platform)
                                    <option value="{{ $platform->value }}">{{ $platform->value }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Səhifə</label>
                            <select name="page" id="bulk-import-page" class="form-select bulk-select2 translation-page-select">
                                @foreach ($bulkFirstPages as $pageValue => $pageLabel)
                                    <option value="{{ $pageValue }}">{{ $pageLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tərcümə faylı (group)</label>
                            <select name="group" class="form-select bulk-select2">
                                <option value="">— (yoxdur)</option>
                                @foreach ($bulkGroups as $group)
                                    <option value="{{ $group->value }}">{{ $group->getLabel() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fayl</label>
                            <input type="file" name="file" id="bulk-import-file" class="form-control" accept=".json,.xlsx">
                        </div>
                    </div>

                    <div class="alert alert-light border small mb-2">
                        <div><strong>JSON:</strong> düz açar-dəyər obyekti, məs. <code>{ "submit": "Yadda saxla" }</code></div>
                        <div><strong>XLSX:</strong> ilk sətir <code>key | value</code> başlığı olmalıdır.</div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="fw-semibold">Nümunə şablonları yüklə:</span>
                        <a href="{{ asset('example%20files/translations-template.json') }}" download
                           class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download"></i> JSON şablon
                        </a>
                        <a href="{{ asset('example%20files/translations-template.xlsx') }}" download
                           class="btn btn-outline-success btn-sm">
                            <i class="fas fa-download"></i> XLSX şablon
                        </a>
                    </div>

                    <div class="translation-result" id="bulk-import-result"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İmtina et</button>
                <button type="button" class="btn btn-primary" id="bulk-import-submit">
                    <span class="button-label">İdxal et</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>
