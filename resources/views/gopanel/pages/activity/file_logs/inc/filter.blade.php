<!-- Filter Panel -->
<div class="collapse mb-3" id="filterPanel">
    <div class="card card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Level</label>
                <select class="form-select" id="filter-level">
                    <option value="">Hamısı</option>
                    @foreach ($levels as $levelItem)
                        <option value="{{$levelItem}}" @selected($levelItem == $level)>{{ucfirst($levelItem)}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Kanal</label>
                <select class="form-select" id="filter-channel">
                    <option value="">Hamısı</option>
                    @foreach ($channels as $key => $channelItem)
                        <option value="{{$key}}" @selected($channel == $key)>{{ $channelItem['show_name'] ?? ucfirst($key) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">İstifadəçi</label>
                <select class="form-select" id="filter-user" style="width: 100%;">
                    <option value="">Hamısı</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Tarix (başlanğıc)</label>
                <input type="date" class="form-control" id="filter-date-from">
            </div>
            <div class="col-md-2">
                <label class="form-label">Tarix (son)</label>
                <input type="date" class="form-control" id="filter-date-to">
            </div>
            <div class="col-auto d-flex align-items-end gap-2">
                <button type="button" class="btn btn-primary" id="apply-filters">
                    <i class="fas fa-search"></i> Filtrlə
                </button>
                <button type="button" class="btn btn-light" id="clear-filters">Sıfırla</button>
            </div>
        </div>
    </div>
</div>