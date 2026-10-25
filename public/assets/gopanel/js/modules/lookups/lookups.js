/**
 * Status və Tiplər (lookup) siyahısı.
 *
 * Yaratma/redaktə modalı burada YOXDUR - crud.js-in mövcud axını işlədilir
 * (#open-create-modal, .edit, #save-form-btn). Bu fayl yalnız lookup-a xas
 * üç işi görür:
 *
 *   1) sürüklə-burax sıralama - bütün sıra TƏK PATCH sorğusu ilə gedir
 *      ({orders: [uid, ...]}); sorğu gedərkən sıralama bloklanır, xəta olarsa
 *      sətirlər əvvəlki yerinə qaytarılır (server tranzaksiyası da geri alır);
 *   2) silmə təsdiqi və DELETE sorğusu - server 422 ilə səbəbi qaytarır
 *      (default sətir, istifadədə olan sətir);
 *   3) formadakı rəng seçicisini mətn sahəsi ilə sinxron saxlamaq.
 *
 * Bütün URL-lər blade-dən data-* atributu ilə gəlir.
 */
$(function () {
    'use strict';

    var $list = $('#lookup-sortable');

    // --- 1. Sıralama -------------------------------------------------------
    if ($list.length && $.fn.sortable && $list.find('.lookup-drag-handle').length) {
        var previousOrder = [];
        var busy = false;

        var readOrder = function () {
            return $list.find('> tr[data-id]').map(function () {
                return String($(this).attr('data-id'));
            }).get();
        };

        var restoreOrder = function (order) {
            $.each(order, function (index, id) {
                $list.append($list.find('> tr[data-id="' + id + '"]'));
            });
        };

        var setBusy = function (state) {
            busy = state;
            $list.sortable(state ? 'disable' : 'enable');
            $list.closest('.lookup-table-wrap').toggleClass('is-busy', state);
        };

        $list.sortable({
            items: '> tr[data-id]',
            axis: 'y',
            handle: '.lookup-drag-handle',
            // jQuery UI button üzərində başlayan drag-ı standart olaraq ləğv edir;
            // tutacaq məhz <button>-dur (klaviatura ilə fokuslansın deyə).
            cancel: '',
            placeholder: 'lookup-sort-placeholder',
            forcePlaceholderSize: true,
            helper: function (event, tr) {
                tr.children().each(function () {
                    $(this).width($(this).width());
                });
                return tr;
            },
            start: function () {
                previousOrder = readOrder();
            },
            update: function () {
                if (busy) {
                    return;
                }

                setBusy(true);

                $.ajax({
                    url: $list.data('url'),
                    type: 'PATCH',
                    data: {orders: readOrder()},
                    dataType: 'json'
                }).done(function (response) {
                    if (response.status === 'success') {
                        toastr.success(response.message);
                    } else {
                        restoreOrder(previousOrder);
                        basicAlert(response.message, response.status);
                    }
                }).fail(function (xhr) {
                    restoreOrder(previousOrder);
                    showError(xhr);
                }).always(function () {
                    setBusy(false);
                });
            }
        });
    }

    // --- Keçid düyməsi xətası ----------------------------------------------
    // main.js keçidi göndərir, amma server rədd edəndə (məs. default sətri
    // söndürmək) düymə səhv vəziyyətdə qalır. Lookup toggle URL-i üçün xəta
    // olarsa səhifə yenilənir ki, real vəziyyət görünsün.
    var toggleUrl = $list.data('toggle-url');
    if (toggleUrl) {
        $(document).ajaxError(function (event, xhr, settings) {
            if (settings.url === toggleUrl) {
                setTimeout(function () {
                    window.location.reload();
                }, 1500);
            }
        });
    }

    // --- 2. Silmə -----------------------------------------------------------
    $('body').on('click', '.lookup-delete', function (e) {
        e.preventDefault();

        var $button = $(this);
        var url = $button.data('url');

        if (!url) {
            return;
        }

        Swal.fire({
            title: 'Silmək istədiyinizə əminsiniz?',
            text: 'İstifadə olunan sətri silmək əvəzinə deaktiv etmək tövsiyə olunur.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Bəli, sil',
            cancelButtonText: 'İmtina'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $button.prop('disabled', true);

            $.ajax({
                url: url,
                type: 'DELETE',
                dataType: 'json'
            }).done(function (response) {
                if (response.status === 'success') {
                    toastr.success(response.message);
                    $button.closest('tr').fadeOut(200, function () {
                        $(this).remove();
                    });
                } else {
                    basicAlert(response.message, response.status);
                }
            }).fail(function (xhr) {
                showError(xhr);
            }).always(function () {
                $button.prop('disabled', false);
            });
        });
    });

    // --- 3. Rəng seçicisi ---------------------------------------------------
    // Forma modala AJAX ilə gəlir, ona görə delegated hadisə.
    $('body').on('input', '.lookup-color-picker', function () {
        $($(this).data('target')).val($(this).val());
    });

    $('body').on('input', '#lookup-color', function () {
        var value = String($(this).val());
        if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
            $('.lookup-color-picker').val(value);
        }
    });
});
