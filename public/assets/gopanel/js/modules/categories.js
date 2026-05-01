$(function () {
    if (typeof initDatatableUiElements === 'function') {
        initDatatableUiElements();
    }

    function sendSort($list) {
        var data = $list.sortable('serialize');
        var key = $list.attr('data-key');
        var row = $list.attr('data-row');
        var route = $list.attr('data-url');

        if (!data || !key || !row || !route) {
            return;
        }

        $.ajax({
            url: route,
            data: {data: data, key: key, row: row},
            type: 'POST',
            success: function (response) {
                if (response.status !== 'success') {
                    basicAlert(response.message, response.status);
                }
            },
            error: function (xhr) {
                showError(xhr);
            }
        });
    }

    $('#parent-sortable').sortable({
        handle: '.parent-drag-handle',
        items: '> .card',
        placeholder: 'card mb-3 border border-primary bg-light',
        tolerance: 'pointer',
        update: function () {
            sendSort($(this));
        }
    });

    $(document).on('change', '.category-icon-type', function () {
        var $form = $(this).closest('form');
        var isImage = $(this).val() === 'image';

        $form.find('.category-font-icon-wrap').toggle(!isImage);
        $form.find('.category-image-icon-wrap').toggle(isImage);
    });

    $('.category-sortable').sortable({
        connectWith: '.category-sortable',
        placeholder: 'bg-light',
        tolerance: 'pointer',
        receive: function (event, ui) {
            var moveUrl = $('#parent-sortable').attr('data-move-url');
            var newParentId = $(this).attr('data-parent-id');
            var itemId = ui.item.attr('id').replace('item_', '');

            if (!moveUrl || !itemId) {
                return;
            }

            $.ajax({
                url: moveUrl,
                data: {id: itemId, parent_id: newParentId},
                type: 'POST',
                success: function (response) {
                    basicAlert(response.message, response.status);
                },
                error: function (xhr) {
                    showError(xhr);
                }
            });
        },
        update: function (event, ui) {
            if (this === ui.item.parent()[0]) {
                sendSort($(this));
            }
        }
    });
});
