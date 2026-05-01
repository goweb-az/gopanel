/**
 * Admins — Role permission management
 */
$(document).ready(function(){

    function updateCounter(){
        var count = $('input.permission-checkbox:checked').length;
        $('#permissionCounter').text(count);
    }

    function updateGroupCounters(){
        $('.group-counter').each(function(){
            var groupClass = $(this).data('group');
            var checked = $('.' + groupClass + ':checked').length;
            $(this).find('.group-checked-count').text(checked);

            // Badge rəngini dəyiş
            $(this).removeClass('rpf-badge-primary rpf-badge-success');
            if(checked > 0){
                $(this).addClass('rpf-badge-success');
            } else {
                $(this).addClass('rpf-badge-primary');
            }
        });
    }

    function syncGroupCheckboxes(){
        $('.group-checkbox').each(function(){
            var groupClass = $(this).data('group');
            var all = $('.' + groupClass).length;
            var checked = $('.' + groupClass + ':checked').length;
            $(this).prop('checked', all > 0 && all === checked);
        });
    }

    // Initial sync
    syncGroupCheckboxes();
    updateGroupCounters();
    updateCounter();

    // Search filter
    $('#searchRole').on('input', function () {
        var search = $(this).val().toLowerCase().trim();
        $('.group-item').show();
        $('.group-item-check').each(function () {
            var text = $(this).text().toLowerCase().trim();
            $(this).toggle(search === '' || text.includes(search));
        });
        $('.group-item').each(function () {
            $(this).toggle($(this).find('.group-item-check:visible').length > 0);
        });
    });

    // Group checkbox toggle
    $('.group-checkbox').on('change', function () {
        var groupClass = $(this).data('group');
        $('.' + groupClass).prop('checked', $(this).is(':checked'));
        updateCounter();
        updateGroupCounters();
    });

    // Check all toggle
    $('#checkAllPermissions').on('change', function () {
        var isChecked = $(this).is(':checked');
        $('input.permission-checkbox, .group-checkbox').prop('checked', isChecked);
        updateCounter();
        updateGroupCounters();
    });

    // Group label click
    $('.group-label').on('click', function () {
        var target = $(this).data('target');
        var checkbox = $('input.group-checkbox[data-group="' + target + '"]');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });

    // Individual permission change
    $(document).on('change', 'input.permission-checkbox', function(){
        var classes = $(this).attr('class').split(' ');
        var groupClass = null;
        for(var i = 0; i < classes.length; i++){
            if(classes[i] !== 'form-check-input' && classes[i] !== 'permission-checkbox'){
                groupClass = classes[i];
                break;
            }
        }
        if(groupClass){
            var all = $('.' + groupClass).length;
            var checked = $('.' + groupClass + ':checked').length;
            $('input.group-checkbox[data-group="' + groupClass + '"]').prop('checked', all === checked);
        }
        updateCounter();
        updateGroupCounters();
    });

});

$(function () {
    $('#change-password-form').on('submit', function (e) {
        e.preventDefault();

        var password = $('#new-password').val();
        var confirmation = $('#new-password-confirm').val();

        if (password.length < 6) {
            basicAlert('Şifrə ən az 6 simvol olmalıdır.', 'error');
            return;
        }

        if (password !== confirmation) {
            basicAlert('Şifrə təsdiqi uyğun gəlmir.', 'error');
            return;
        }

        var mainFormAction = $('#data-form').attr('action');
        if (!mainFormAction) {
            basicAlert('Admin məlumatı tapılmadı.', 'error');
            return;
        }

        var $btn = $('#save-password-btn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Gözləyin...');

        $.ajax({
            url: mainFormAction,
            type: 'POST',
            data: {
                password: password,
                password_confirmation: confirmation,
                _change_password_only: true
            },
            success: function (response) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Şifrəni yenilə');
                basicAlert(response.message, response.status);

                if (response.status === 'success') {
                    $('#changePasswordModal').modal('hide');
                    $('#change-password-form')[0].reset();
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Şifrəni yenilə');
                showError(xhr);
            }
        });
    });

    $('#changePasswordModal').on('hidden.bs.modal', function () {
        $('#change-password-form')[0].reset();
    });
});
