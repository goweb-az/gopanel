/**
 * Global Icon Picker
 * ------------------
 * Renders icons exposed by `IconPickerHelper::all()` (served via the route
 * stored on the modal's `data-icon-list-url` attribute) into the modal
 * defined in `gopanel.component.icon-picker-modal`. Supports four providers
 * (Font Awesome, Boxicons, Material Design, Dripicons), live token-based
 * search, and chunked rendering so the MDI tab (~6k icons) doesn't freeze
 * the browser on first paint.
 */
(function ($) {
    'use strict';

    if ($('#globalIconPickerStyles').length === 0) {
        $('head').append(
            '<style id="globalIconPickerStyles">' +
            '.global-icon-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:8px;}' +
            '.global-icon-picker-item{position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:10px 6px;border:1px solid #e9ecef;background:#fff;border-radius:6px;cursor:pointer;transition:all .15s;text-align:center;min-height:74px;overflow:hidden;}' +
            '.global-icon-picker-item:hover{background:#eef4ff;border-color:#556ee6;transform:translateY(-1px);box-shadow:0 4px 10px rgba(85,110,230,0.12);}' +
            '.global-icon-picker-item.is-selected{background:#e8f0fe;border-color:#556ee6;box-shadow:0 0 0 2px rgba(85,110,230,0.25);}' +
            '.global-icon-picker-item > i{font-size:22px;color:#495057;line-height:1;}' +
            '.global-icon-picker-item > .gip-name{font-size:10px;color:#6c757d;font-family:Menlo,Consolas,monospace;line-height:1.2;word-break:break-all;max-width:100%;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}' +
            '#globalIconPickerModal .nav-tabs-custom .nav-link{font-size:13px;padding:8px 14px;}' +
            '#globalIconPickerModal .modal-body{padding:0;}' +
            '#globalIconPickerCurrent{font-size:12px;}' +
            '.global-icon-empty{padding:30px 10px;text-align:center;color:#6c757d;font-size:13px;}' +
            '.global-icon-loading{padding:30px 10px;text-align:center;color:#6c757d;font-size:13px;}' +
            '</style>'
        );
    }

    // --- Configuration -----------------------------------------------------
    var INITIAL_CHUNK = 240;
    var NEXT_CHUNK    = 240;

    // --- State -------------------------------------------------------------
    var providerState  = null;
    var loadPromise    = null;
    var targetSelector = null;
    var previewSelector = null;
    var currentValue   = '';

    function buildSearchableList(list) {
        var out = new Array(list.length);
        for (var i = 0; i < list.length; i++) {
            out[i] = { cls: list[i], search: list[i].toLowerCase() };
        }
        return out;
    }

    function ensureLoaded() {
        if (providerState) {
            return $.Deferred().resolve(providerState).promise();
        }
        if (loadPromise) {
            return loadPromise;
        }

        var url = $('#globalIconPickerModal').data('icon-list-url');
        if (!url) {
            return $.Deferred().reject(new Error('Icon picker URL not configured')).promise();
        }

        $('.global-icon-grid').html('<div class="global-icon-loading">Yüklənir...</div>');

        loadPromise = $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            cache: true
        }).then(function (data) {
            providerState = {};
            ['fa', 'bx', 'mdi', 'drp'].forEach(function (provider) {
                var raw = (data && data[provider]) || [];
                providerState[provider] = {
                    all: buildSearchableList(raw),
                    filtered: null,
                    rendered: 0,
                    query: ''
                };
                $('#globalIconPickerModal [data-count-for="' + provider + '"]').text(raw.length);
            });
            return providerState;
        });

        return loadPromise;
    }

    // --- Rendering ---------------------------------------------------------
    function getActiveList(provider) {
        var st = providerState[provider];
        return st.filtered || st.all;
    }

    function buildItemHtml(entry, currentValueLower) {
        var safe = entry.cls.replace(/"/g, '&quot;');
        var displayName = entry.cls.split(' ').slice(-1)[0];
        var selectedCls = entry.search === currentValueLower ? ' is-selected' : '';
        return (
            '<button type="button" class="global-icon-picker-item' + selectedCls + '" ' +
                'data-icon-class="' + safe + '" data-search="' + entry.search + '" title="' + safe + '">' +
                '<i class="' + safe + '"></i>' +
                '<span class="gip-name">' + displayName + '</span>' +
            '</button>'
        );
    }

    function renderChunk(provider, append) {
        var st = providerState[provider];
        var list = getActiveList(provider);
        var $grid = $('.global-icon-grid[data-provider="' + provider + '"]');
        var $loadMoreWrap = $('.global-icon-loadmore-wrap[data-provider="' + provider + '"]');

        if (!append) {
            $grid.empty();
            st.rendered = 0;
        }

        var start = st.rendered;
        var end   = Math.min(list.length, start + (start === 0 ? INITIAL_CHUNK : NEXT_CHUNK));

        if (start === 0 && list.length === 0) {
            $grid.html('<div class="global-icon-empty">Heç bir ikon tapılmadı.</div>');
            $loadMoreWrap.hide();
            updateCounters(provider, 0, 0);
            return;
        }

        var lcCurrent = (currentValue || '').toLowerCase();
        var html = '';
        for (var i = start; i < end; i++) {
            html += buildItemHtml(list[i], lcCurrent);
        }
        $grid.append(html);
        st.rendered = end;

        if (end < list.length) {
            $loadMoreWrap.show().find('.global-icon-loadmore').text(
                'Daha çox göstər (' + (list.length - end) + ' ikon qaldı)'
            );
        } else {
            $loadMoreWrap.hide();
        }

        updateCounters(provider, st.rendered, list.length);
    }

    function updateCounters(provider, visible, total) {
        var $tab = $('#globalIconPickerModal .nav-link[data-provider="' + provider + '"]');
        if (!$tab.hasClass('active')) {
            return;
        }
        $('#globalIconPickerVisible').text(visible);
        $('#globalIconPickerTotal').text(total);
    }

    function applyFilter(provider, query) {
        var st = providerState[provider];
        var trimmed = (query || '').trim().toLowerCase();
        st.query = trimmed;

        if (!trimmed) {
            st.filtered = null;
        } else {
            // Split query into tokens so "fa home" and "home fa" both match.
            var tokens = trimmed.split(/\s+/).filter(Boolean);
            st.filtered = st.all.filter(function (entry) {
                for (var i = 0; i < tokens.length; i++) {
                    if (entry.search.indexOf(tokens[i]) === -1) {
                        return false;
                    }
                }
                return true;
            });
        }

        renderChunk(provider, false);
    }

    function getActiveProvider() {
        return $('#globalIconPickerModal .nav-link.active').data('provider') || 'fa';
    }

    function renderActiveProviderInitial() {
        var provider = getActiveProvider();
        var st = providerState[provider];
        if (st.rendered === 0) {
            renderChunk(provider, false);
        } else {
            updateCounters(provider, st.rendered, getActiveList(provider).length);
        }
    }

    // --- Wire-up -----------------------------------------------------------
    $(document).on('click', '[data-icon-picker-target]', function () {
        targetSelector  = $(this).data('icon-picker-target');
        previewSelector = $(this).data('icon-picker-preview') || null;

        var $target = $(targetSelector);
        currentValue = $target.length ? ($target.val() || '') : '';
        $('#globalIconPickerCurrent').text(currentValue || '—');
        $('#globalIconPickerSearch').val('');

        $('#globalIconPickerModal').modal('show');

        ensureLoaded().then(function () {
            // Reset render counters & filters so the highlighted "is-selected"
            // item is recomputed against the fresh currentValue.
            Object.keys(providerState).forEach(function (provider) {
                providerState[provider].filtered = null;
                providerState[provider].query    = '';
                providerState[provider].rendered = 0;
            });
            renderActiveProviderInitial();
        }).fail(function () {
            $('.global-icon-grid').html('<div class="global-icon-empty">İkon siyahısı yüklənə bilmədi.</div>');
        });
    });

    $(document).on('shown.bs.tab', '#globalIconPickerModal .nav-link', function () {
        if (!providerState) {
            return;
        }
        var provider = $(this).data('provider');
        var query = $('#globalIconPickerSearch').val();
        if (providerState[provider].query !== (query || '').trim().toLowerCase()) {
            applyFilter(provider, query);
        } else {
            if (providerState[provider].rendered === 0) {
                renderChunk(provider, false);
            } else {
                updateCounters(provider, providerState[provider].rendered, getActiveList(provider).length);
            }
        }
    });

    $(document).on('input', '#globalIconPickerSearch', function () {
        if (!providerState) {
            return;
        }
        applyFilter(getActiveProvider(), $(this).val());
    });

    $(document).on('click', '.global-icon-loadmore', function () {
        if (!providerState) {
            return;
        }
        renderChunk($(this).data('provider'), true);
    });

    $(document).on('click', '.global-icon-picker-item', function () {
        var iconClass = $(this).data('icon-class');
        var $target   = $(targetSelector);

        if ($target.length) {
            $target.val(iconClass).trigger('change');
            if (previewSelector) {
                $(previewSelector).html('<i class="' + iconClass + '"></i>');
            }
        }

        currentValue = iconClass;
        $('#globalIconPickerModal').modal('hide');
    });
})(jQuery);
