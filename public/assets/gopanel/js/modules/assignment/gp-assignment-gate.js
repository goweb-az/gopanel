// ─────────────────────────────────────────────────────────────────────────
// «Əvvəlcə üzərinizə götürün» qapısı.
// Server tərəfi: App\Support\Gopanel\AssignmentGuard (409 + code NOT_ASSIGNED)
//
// Niyə hər çağırış yerinə ayrıca kod yazılmır: detal səhifəsində bir neçə
// AJAX yolu olur (status, qeyd, yerində redaktə). Cavab jQuery-nin qlobal
// `ajaxError` hadisəsindən tutulur, ona görə yeni əməliyyat əlavə olunanda
// bura toxunmaq lazım gəlmir.
//
// «Üzərinə götür» seçiləndə təyinat yazılır və **həmin sorğu yenidən
// göndərilir** - admin yazdığı mətni ikinci dəfə yazmır.
// ─────────────────────────────────────────────────────────────────────────

(function () {
    'use strict';

    var CODE = 'NOT_ASSIGNED';

    // Eyni anda bir neçə sorğu 409 alsa bir dənə sual açılsın.
    var asking = false;

    function esc(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function assignUrl() {
        var banner = document.querySelector('[data-gp-assignment]');

        return banner ? banner.getAttribute('data-gp-assign-url') : null;
    }

    function token() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function say(type, message) {
        if (window.toastr) {
            toastr[type](message);
        }
    }

    $(document).ajaxError(function (event, xhr, settings) {
        var body = xhr.responseJSON;

        if (!body || body.code !== CODE || asking) {
            return;
        }

        var url = assignUrl();

        if (!url || !window.Swal) {
            // Bu səhifədə təyinat zolağı yoxdursa sual verməyin mənası yoxdur.
            say('error', body.message);

            return;
        }

        asking = true;

        Swal.fire({
            title: 'Bu qeyd sizin üzərinizdə deyil',
            html: esc(body.message)
                + '<div class="mt-2 text-muted font-size-12">Üzərinizə götürsəniz digər adminlər siyahıda sizin adınızı görəcək.</div>',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Üzərimə götürürəm',
            cancelButtonText: 'Baxmağa davam edirəm',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            allowOutsideClick: function () { return !Swal.isLoading(); },
            preConfirm: function () {
                return $.post(url, { _token: token() }).catch(function () {
                    Swal.showValidationMessage('Üzərinizə götürmək alınmadı.');

                    return false;
                });
            }
        }).then(function (result) {
            asking = false;

            if (!result.isConfirmed || !result.value) {
                return;
            }

            say('success', 'Qeyd sizin üzərinizədir.');

            // Təyinat yazıldı - dayandırılmış sorğu eyni parametrlərlə yenidən göndərilir.
            $.ajax(settings).done(function () {
                window.location.reload();
            });
        });
    });
}());
