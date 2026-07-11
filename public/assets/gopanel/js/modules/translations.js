$(function () {
    var $root = $('#translation-page');
    if (!$root.length) {
        return;
    }

    var pages = {};
    try {
        pages = JSON.parse($root.attr('data-pages') || '{}');
    } catch (e) {
        pages = {};
    }

    function escapeHtml(text) {
        return $('<div>').text(text == null ? '' : String(text)).html();
    }

    function pagesFor(platform) {
        return pages[platform] || { general: 'Ümumi' };
    }

    // Rebuild a page <select> for the given platform, preserving the current
    // selection when it still exists in the new platform's page list.
    function rebuildPageSelect($pageSelect, platform) {
        var current = $pageSelect.val();
        var list = pagesFor(platform);
        $pageSelect.empty();
        Object.keys(list).forEach(function (value) {
            var selected = value === current;
            $pageSelect.append(new Option(list[value], value, selected, selected));
        });
        // Refresh select2 rendering if this select is a select2 widget.
        if ($pageSelect.hasClass('select2-hidden-accessible')) {
            $pageSelect.trigger('change.select2');
        }
    }

    function toggleButtonLoading($button, loading) {
        $button.prop('disabled', loading);
        $button.find('.button-label').toggleClass('d-none', loading);
        $button.find('.spinner-border').toggleClass('d-none', !loading);
        $button.attr('aria-busy', loading ? 'true' : 'false');
    }

    // --- Cascade: any platform select rebuilds its sibling page select -------
    $(document).on('change', '.translation-platform-select', function () {
        var platform = $(this).val();
        var $scope = $(this).closest('form').length ? $(this).closest('form') : $(this).closest('.modal, #translation-page');
        var $pageSelect = $scope.find('.translation-page-select').first();
        if ($pageSelect.length) {
            rebuildPageSelect($pageSelect, platform);
        }
    });

    // --- Filter panel (select2 + apply/reset) --------------------------------
    function currentQuery() {
        return new URLSearchParams(window.location.search);
    }

    // Initialise the three filter selects as searchable, clearable select2s.
    // These live on the main page (not inside #form-wrap), so they don't get
    // the "select2" class the shared form initializer looks for — we own them.
    if ($.fn.select2) {
        $('.translation-filter-select').each(function () {
            $(this).select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                placeholder: $(this).data('placeholder') || ''
            });
        });
    }

    // Cascade: choosing a platform rebuilds the page select options.
    $('#filter-platform').on('change', function () {
        var platform = $(this).val();
        var $page = $('#filter-page');
        $page.empty().append(new Option('', ''));
        if (platform) {
            var list = pagesFor(platform);
            Object.keys(list).forEach(function (value) {
                $page.append(new Option(list[value], value));
            });
        }
        $page.val(null).trigger('change.select2');
    });

    // Apply: navigate with the selected filters as query params (the datatable
    // source URL is built from the page query string).
    $('#translation-filter-apply').on('click', function () {
        var params = currentQuery();
        var locale = $('#filter-locale').val();
        var platform = $('#filter-platform').val();
        var page = $('#filter-page').val();

        locale ? params.set('locale', locale) : params.delete('locale');
        platform ? params.set('platform', platform) : params.delete('platform');
        page ? params.set('page', page) : params.delete('page');

        window.location.search = params.toString();
    });

    // --- Bulk modal select2 (searchable, opens on top inside the modal) ------
    // dropdownParent must be the modal so the dropdown layers above the modal
    // content instead of rendering behind it / getting clipped. Init on first
    // open so width is measured while the modal is visible.
    $('#bulk-import-modal').on('shown.bs.modal', function () {
        if (!$.fn.select2) {
            return;
        }
        var $modal = $(this);
        $modal.find('.bulk-select2').each(function () {
            if (!$(this).data('select2')) {
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $modal
                });
            }
        });
    });

    // --- Bulk import ---------------------------------------------------------
    function renderImportResult(data) {
        var $box = $('#bulk-import-result');
        var rows = ''
            + '<div class="alert alert-info mb-2">'
            + '  Ümumi: ' + escapeHtml(data.total_rows)
            + ' · Yaradıldı: ' + escapeHtml(data.created)
            + ' · Yeniləndi: ' + escapeHtml(data.updated)
            + ' · Ötürüldü: ' + escapeHtml(data.skipped)
            + ' · Uğursuz: ' + escapeHtml(data.failed)
            + '</div>';

        if (data.errors && data.errors.length) {
            rows += '<ul class="list-group">';
            data.errors.forEach(function (err) {
                rows += '<li class="list-group-item list-group-item-warning py-1">' + escapeHtml(err) + '</li>';
            });
            rows += '</ul>';
        }
        $box.html(rows);
    }

    $('#bulk-import-submit').on('click', function () {
        var $button = $(this);
        var form = document.getElementById('bulk-import-form');
        var $result = $('#bulk-import-result');
        $result.empty();

        toggleButtonLoading($button, true);

        $.ajax({
            url: $root.attr('data-import-url'),
            type: 'POST',
            data: new FormData(form),
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function (response) {
            if (response.data) {
                renderImportResult(response.data);
            }
            if (typeof basicAlert === 'function') {
                basicAlert(response.message, response.status);
            }
            if (response.status === 'success' && window.dTable) {
                window.dTable.ajax.reload();
            }
        }).fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                var list = '<ul class="list-group">';
                Object.keys(xhr.responseJSON.errors).forEach(function (field) {
                    xhr.responseJSON.errors[field].forEach(function (msg) {
                        list += '<li class="list-group-item list-group-item-danger py-1">' + escapeHtml(msg) + '</li>';
                    });
                });
                list += '</ul>';
                $result.html(list);
            } else if (typeof showError === 'function') {
                showError(xhr);
            }
        }).always(function () {
            toggleButtonLoading($button, false);
        });
    });

    // --- Export --------------------------------------------------------------
    $('#translation-export').on('click', function () {
        var $button = $(this);
        var params = currentQuery();

        toggleButtonLoading($button, true);

        $.ajax({
            url: $root.attr('data-export-url'),
            type: 'POST',
            data: {
                platform: params.get('platform') || '',
                page: params.get('page') || ''
            },
            dataType: 'json'
        }).done(function (response) {
            if (typeof basicAlert === 'function') {
                basicAlert(response.message, response.status);
            }
        }).fail(function (xhr) {
            if (typeof showError === 'function') {
                showError(xhr);
            }
        }).always(function () {
            toggleButtonLoading($button, false);
        });
    });
});
