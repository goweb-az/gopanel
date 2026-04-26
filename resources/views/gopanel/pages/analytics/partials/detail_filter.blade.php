<!-- Analytics Detail Filter Panel -->
<div class="row" id="filterWrapper" @if(!request()->hasAny(['from','to'])) style="display:none;" @endif>
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body analytics-filter-panel">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-3 col-sm-6">
                        <label class="form-label">Başlanğıc tarix</label>
                        <input type="date" class="form-control" name="from" id="detail-filter-from" value="{{ request('from') }}">
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6">
                        <label class="form-label">Son tarix</label>
                        <input type="date" class="form-control" name="to" id="detail-filter-to" value="{{ request('to') }}">
                    </div>
                    @if(isset($extraFilters))
                        @foreach($extraFilters as $filter)
                            <div class="col-lg-2 col-md-2 col-sm-6">
                                <label class="form-label">{{ $filter['label'] }}</label>
                                <select class="form-select" name="{{ $filter['name'] }}" id="detail-filter-{{ $filter['name'] }}">
                                    <option value="">Hamısı</option>
                                    @foreach($filter['options'] as $opt)
                                        <option value="{{ $opt['value'] }}" @selected(request($filter['name']) == $opt['value'])>{{ $opt['text'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    @endif
                    <div class="col-auto d-flex align-items-end gap-2">
                        <button type="button" class="btn btn-success" id="detail-apply-filters">
                            <i class="fa fa-search"></i> Axtar
                        </button>
                        <button type="button" class="btn btn-light" id="detail-clear-filters">Sıfırla</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function() {
    // Filter apply — update query params + reload datatable (file-logs pattern)
    $('#detail-apply-filters').on('click', function() {
        reloadDetailDatatable();
    });

    // Filter clear
    $('#detail-clear-filters').on('click', function() {
        $('#detail-filter-from').val('');
        $('#detail-filter-to').val('');
        $('[id^="detail-filter-"]').not('#detail-filter-from, #detail-filter-to').val('');
        reloadDetailDatatable();
    });

    function reloadDetailDatatable() {
        var params = {};
        // Collect all filter values
        $('[id^="detail-filter-"]').each(function() {
            var name = $(this).attr('name');
            var val = $(this).val();
            if (name) params[name] = val;
        });

        // Update URL query params
        var url = new URL(window.location.href);
        Object.keys(params).forEach(function(key) {
            if (params[key]) {
                url.searchParams.set(key, params[key]);
            } else {
                url.searchParams.delete(key);
            }
        });
        window.history.replaceState({}, '', url);

        // Find the datatable and reload with new params
        var $dt = $('table.dataTable, table[id]').first();
        if ($dt.length && $.fn.DataTable.isDataTable($dt)) {
            var dtUrlStr = $dt.DataTable().ajax.url();
            if (!dtUrlStr && typeof dTableSourceRoute !== 'undefined') {
                dtUrlStr = dTableSourceRoute;
            }
            if (dtUrlStr) {
                var dtUrl = new URL(dtUrlStr, window.location.origin);
                Object.keys(params).forEach(function(key) {
                    if (params[key]) {
                        dtUrl.searchParams.set(key, params[key]);
                    } else {
                        dtUrl.searchParams.delete(key);
                    }
                });
                $dt.DataTable().ajax.url(dtUrl.toString()).load();
            }
        }
    }
});
</script>
@endpush
