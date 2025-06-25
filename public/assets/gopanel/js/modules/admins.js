$('#searchRole').on('input', function () {
    let search = $(this).val().toLowerCase().trim();
    $('.group-item').show();
    $('.group-item-check').each(function () {
        let text = $(this).text().toLowerCase().trim();

        if (search === '') {
            $(this).show();
        } else if (text.includes(search)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });

    $('.group-item').each(function () {
        let visible = $(this).find('.group-item-check:visible').length > 0;
        $(this).toggle(visible);
    });
});

$('.group-checkbox').on('change', function () {
    let groupClass = $(this).data('group');
    $('.' + groupClass).prop('checked', $(this).is(':checked'));
});

$('#checkAllPermissions').on('change', function () {
    let isChecked = $(this).is(':checked');
    $('input.permission-checkbox, .group-checkbox').prop('checked', isChecked);
});

$('.group-label').on('click', function () {
    let target = $(this).data('target');
    let checkbox = $('input.group-checkbox[data-group="' + target + '"]');
    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
});