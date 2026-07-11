$(function () {
    var $tbody = $('#language-sortable');
    if (!$tbody.length) {
        return;
    }

    // Don't allow drag-reordering while a country filter is applied: the visible
    // rows are a subset, so reordering them would silently interleave hidden rows.
    if ($tbody.attr('data-sortable-enabled') === '0') {
        return;
    }

    var sortUrl = $tbody.attr('data-sort-url');
    var $overlay = $('#language-overlay');
    var previousOrder = null;
    var inFlight = false;

    function readOrder() {
        return $tbody.find('> tr').map(function () {
            return $(this).attr('data-id');
        }).get();
    }

    function restoreOrder(order) {
        if (!order) {
            return;
        }
        $.each(order, function (index, id) {
            var $row = $tbody.find('> tr[data-id="' + id + '"]');
            $tbody.append($row);
        });
    }

    function buildItems() {
        return $tbody.find('> tr').map(function (index) {
            return { id: parseInt($(this).attr('data-id'), 10), sort_order: index };
        }).get();
    }

    function persistOrder() {
        if (inFlight) {
            return;
        }
        inFlight = true;
        $overlay.removeClass('d-none');

        $.ajax({
            url: sortUrl,
            type: 'POST',
            data: { items: buildItems() },
            dataType: 'json'
        }).done(function (response) {
            if (response.status !== 'success') {
                restoreOrder(previousOrder);
                if (typeof basicAlert === 'function') {
                    basicAlert(response.message, response.status);
                }
            } else if (typeof basicAlert === 'function') {
                basicAlert(response.message, 'success');
            }
        }).fail(function (xhr) {
            restoreOrder(previousOrder);
            if (typeof showError === 'function') {
                showError(xhr);
            }
        }).always(function () {
            inFlight = false;
            $overlay.addClass('d-none');
        });
    }

    if (!$.fn.sortable) {
        return;
    }

    $tbody.sortable({
        axis: 'y',
        items: '> tr[data-id]',
        handle: '.language-drag-handle',
        // jQuery UI cancels drags started on input/textarea/button/select by
        // default; clear it so the drag handle (and its icon) always works.
        cancel: '',
        placeholder: 'table-active',
        forcePlaceholderSize: true,
        // Canonical table-row helper: lock each cell width on the real row and
        // drag that row (returning `tr`), so the table layout doesn't collapse.
        helper: function (e, tr) {
            tr.children().each(function () {
                $(this).width($(this).width());
            });
            return tr;
        },
        start: function () {
            previousOrder = readOrder();
        },
        update: function () {
            persistOrder();
        }
    });

    $tbody.disableSelection();
});
