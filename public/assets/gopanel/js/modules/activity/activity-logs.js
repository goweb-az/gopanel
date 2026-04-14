$(document).ready(function () {

    var datatable = $('#histories');

    // ─── Select2 User (serverside) ───────────────────────────────────────
    $('#filter-user').select2({
        placeholder: 'İstifadəçi axtar...',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: '/gopanel/activity/activity-logs/users',
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data.results };
            },
            cache: true
        }
    });

    // ─── Filtrləmə ──────────────────────────────────────────────────────
    $('#apply-filters').on('click', function () {
        reloadDatatable();
    });

    $('#clear-filters').on('click', function () {
        $('#filter-log-name').val('');
        $('#filter-event').val('');
        $('#filter-user').val(null).trigger('change');
        $('#filter-date-from').val('');
        $('#filter-date-to').val('');
        reloadDatatable();
    });

    function reloadDatatable() {
        var params = {
            log_name: $('#filter-log-name').val(),
            event: $('#filter-event').val(),
            causer_id: $('#filter-user').val(),
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val(),
        };

        // URL parametrlərini əlavə et
        var url = new URL(window.location.href);
        Object.keys(params).forEach(function (key) {
            if (params[key]) {
                url.searchParams.set(key, params[key]);
            } else {
                url.searchParams.delete(key);
            }
        });
        window.history.replaceState({}, '', url);

        // Datatable-ın AJAX URL-ni yenilə
        var dtUrlStr = datatable.DataTable().ajax.url();
        if (!dtUrlStr && typeof dTableSourceRoute !== 'undefined') {
            dtUrlStr = dTableSourceRoute;
        }
        var dtUrl = new URL(dtUrlStr, window.location.origin);
        Object.keys(params).forEach(function (key) {
            if (params[key]) {
                dtUrl.searchParams.set(key, params[key]);
            } else {
                dtUrl.searchParams.delete(key);
            }
        });

        // Datatable-ı yenidən yüklə
        datatable.DataTable().ajax.url(dtUrl.toString()).load();
    }

    // ─── View Offcanvas ─────────────────────────────────────────────────
    $(document).on('click', '.view-btn', function (e) {
        e.preventDefault();
        var url = $(this).attr('href');

        $('#viewLogOffcanvasBody').html('<div class="text-center py-5"><div class="spinner-border" role="status"></div></div>');
        var offcanvas = new bootstrap.Offcanvas(document.getElementById('viewLogOffcanvas'));
        offcanvas.show();

        $.get(url, function (html) {
            $('#viewLogOffcanvasBody').html(html);

            // JSON datalarını render etmək
            var oldData = window.activityOldData || {};
            var newData = window.activityNewData || {};

            if (Object.keys(oldData).length > 0) {
                $('#json-old').jsonViewer(oldData, { collapsed: false, withQuotes: true, withLinks: true });
            } else {
                $('#json-old').html('<span class="text-muted">Boş</span>');
            }

            if (Object.keys(newData).length > 0) {
                $('#json-new').jsonViewer(newData, { collapsed: false, withQuotes: true, withLinks: true });
            } else {
                $('#json-new').html('<span class="text-muted">Boş</span>');
            }

        }).fail(function () {
            $('#viewLogOffcanvasBody').html('<div class="alert alert-danger">Yüklənmə xətası.</div>');
        });
    });

    // ─── JSON Kopyalama Funksiyası ──────────────────────────────────────
    window.copyJsonData = function(type) {
        var data = type === 'old' ? window.activityOldData : window.activityNewData;
        var text = JSON.stringify(data || {}, null, 2);

        navigator.clipboard.writeText(text).then(function() {
            toastr.success('JSON kopyalandı!');
        }).catch(function() {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            toastr.success('JSON kopyalandı!');
        });
    };

    // ─── Delete ─────────────────────────────────────────────────────────
    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        var url = $(this).data('url');

        Swal.fire({
            title: 'Silmək istəyirsiniz?',
            text: 'Bu əməliyyat logu silinəcək.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Bəli, sil',
            cancelButtonText: 'Ləğv et',
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'POST',
                    success: function (res) {
                        toastr.success(res.message || 'Silindi');
                        datatable.DataTable().ajax.reload(null, false);
                    },
                    error: function () {
                        toastr.error('Xəta baş verdi');
                    }
                });
            }
        });
    });

    // ─── Cleanup ────────────────────────────────────────────────────────
    $('#cleanup-btn').on('click', function () {
        var cleanupUrl = $(this).data('url');

        Swal.fire({
            title: 'Əməliyyat Loglarını Təmizlə',
            html: '<div class="text-start">' +
                '<p class="mb-3">Hazır seçimlərdən birini seçin və ya tarix aralığı daxil edin:</p>' +
                '<div class="d-flex flex-wrap gap-2 mb-3">' +
                '<button type="button" class="btn btn-sm btn-outline-primary cleanup-preset" data-days="7">Son 7 gündən köhnə</button>' +
                '<button type="button" class="btn btn-sm btn-outline-primary cleanup-preset" data-days="15">Son 15 gündən köhnə</button>' +
                '<button type="button" class="btn btn-sm btn-outline-primary cleanup-preset" data-days="30">Son 30 gündən köhnə</button>' +
                '<button type="button" class="btn btn-sm btn-outline-danger cleanup-preset" data-days="0">Hamısını sil</button>' +
                '</div>' +
                '<hr>' +
                '<div class="row g-2">' +
                '<div class="col-6">' +
                '<label class="form-label small">Başlanğıc tarix</label>' +
                '<input type="date" class="form-control form-control-sm" id="swal-date-from">' +
                '</div>' +
                '<div class="col-6">' +
                '<label class="form-label small">Son tarix</label>' +
                '<input type="date" class="form-control form-control-sm" id="swal-date-to">' +
                '</div>' +
                '</div>' +
                '</div>',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-broom"></i> Seçilmiş tarixə görə təmizlə',
            cancelButtonText: 'Ləğv et',
            confirmButtonColor: '#d33',
            didOpen: function () {
                // Hazır seçim düymələri
                $('.cleanup-preset').on('click', function () {
                    var days = $(this).data('days');
                    executeCleanup(cleanupUrl, { days: days });
                });
            },
            preConfirm: function () {
                var dateFrom = $('#swal-date-from').val();
                var dateTo = $('#swal-date-to').val();

                if (!dateFrom) {
                    Swal.showValidationMessage('Başlanğıc tarix lazımdır');
                    return false;
                }

                return { date_from: dateFrom, date_to: dateTo };
            }
        }).then(function (result) {
            if (result.isConfirmed && result.value) {
                executeCleanup(cleanupUrl, result.value);
            }
        });
    });

    function executeCleanup(url, data) {
        Swal.fire({
            title: 'Silinir...',
            text: 'Zəhmət olmasa gözləyin',
            allowOutsideClick: false,
            didOpen: function () { Swal.showLoading(); }
        });

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            success: function (res) {
                Swal.fire({
                    icon: res.status ? 'success' : 'info',
                    title: res.status ? 'Tamamlandı' : 'Məlumat',
                    text: res.message,
                });
                datatable.DataTable().ajax.reload(null, false);
            },
            error: function (xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Xəta baş verdi';
                Swal.fire({ icon: 'error', title: 'Xəta', text: msg });
            }
        });
    }
});
