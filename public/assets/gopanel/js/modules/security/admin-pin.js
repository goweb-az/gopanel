/**
 * Admin PIN-i - həssas əməliyyatın ikinci təsdiqi.
 *
 * Üç iş görür (hər biri yalnız öz elementi səhifədə olanda):
 *  1. `#pin-confirm-modal` - qorunan AJAX sorğusuna server 423 + `pin_required`
 *     qaytaranda modal açılır, təsdiqdən sonra dayandırılmış sorğu EYNİ
 *     parametrlərlə təkrarlanır (istifadəçi əməliyyatı yenidən başlatmır).
 *  2. `#pin-prompt-form` - adi keçid üçün təsdiq səhifəsi. JS-siz də işləyir;
 *     burada AJAX-a çevrilir ki, endirmə başlayanda səhifə forma ilə qalmasın.
 *  3. `[data-pin-toggle]` - profil səhifəsində PIN-i göstər/gizlət.
 *
 * Fayl həm layout-dan (mexanizm açıq olanda), həm də profil/təsdiq
 * səhifəsindən qoşula bilər - ikiqat bağlanmasın deyə qoruyucu bayraq var.
 */
(function ($) {
    if (!$ || window.__gopanelAdminPinLoaded) {
        return;
    }
    window.__gopanelAdminPinLoaded = true;

    var DEFAULT_MESSAGE = 'Bu əməliyyat üçün PIN təsdiqi lazımdır.';

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : null;
    }

    function alertMessage(message, type) {
        if (typeof basicAlert === 'function') {
            basicAlert(message, type);
        }
    }

    function errorMessage(xhr) {
        if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
            return xhr.responseJSON.message;
        }
        return 'PIN səhvdir.';
    }

    $(function () {
        // ── 1. Modal (AJAX sorğuları) ─────────────────────────────────────
        var $modal = $('#pin-confirm-modal');
        var pending = null;

        if ($modal.length) {
            $(document).ajaxError(function (event, xhr, settings) {
                if (!xhr || xhr.status !== 423 || !xhr.responseJSON || !xhr.responseJSON.pin_required) {
                    return;
                }

                pending = settings;
                $modal.find('[data-pin-confirm-message]').text(xhr.responseJSON.message || DEFAULT_MESSAGE);
                $modal.find('#pin-confirm-input').val('');
                $modal.modal('show');
                setTimeout(function () {
                    $modal.find('#pin-confirm-input').trigger('focus');
                }, 300);
            });

            $('#pin-confirm-form').on('submit', function (e) {
                e.preventDefault();

                var $btn = $(this).find('button[type="submit"]');
                $btn.prop('disabled', true);

                $.ajax({
                    url: $modal.data('url'),
                    type: 'POST',
                    dataType: 'json',
                    data: { _token: csrf(), pin: $modal.find('#pin-confirm-input').val() },
                    success: function () {
                        $modal.modal('hide');

                        if (pending) {
                            var retry = pending;
                            pending = null;
                            $.ajax(retry);
                        }
                    },
                    error: function (xhr) {
                        alertMessage(errorMessage(xhr), 'error');
                    },
                    complete: function () {
                        $btn.prop('disabled', false);
                    }
                });
            });

            $modal.on('hidden.bs.modal', function () {
                pending = null;
                $modal.find('#pin-confirm-input').val('');
            });
        }

        // ── 2. Təsdiq səhifəsi (adi keçidlər) ────────────────────────────
        var $prompt = $('#pin-prompt-form');

        if ($prompt.length) {
            $prompt.on('submit', function (e) {
                e.preventDefault();

                var $btn = $prompt.find('button[type="submit"]');
                $btn.prop('disabled', true);

                $.ajax({
                    url: $prompt.attr('action'),
                    type: 'POST',
                    dataType: 'json',
                    data: $prompt.serialize(),
                    success: function (response) {
                        $prompt.addClass('d-none');
                        $('#pin-prompt-done').removeClass('d-none');

                        if (response && response.redirect) {
                            window.location.href = response.redirect;
                        }
                    },
                    error: function (xhr) {
                        alertMessage(errorMessage(xhr), 'error');
                        $prompt.find('input[name="pin"]').val('').trigger('focus');
                    },
                    complete: function () {
                        $btn.prop('disabled', false);
                    }
                });
            });
        }

        // ── 3. Göstər / gizlət ───────────────────────────────────────────
        $(document).on('click', '[data-pin-toggle]', function () {
            var $input = $($(this).data('pin-toggle'));
            var $icon = $(this).find('i');
            var hidden = $input.attr('type') === 'password';

            $input.attr('type', hidden ? 'text' : 'password');
            $icon.toggleClass('fa-eye', !hidden).toggleClass('fa-eye-slash', hidden);
        });
    });
})(window.jQuery);
