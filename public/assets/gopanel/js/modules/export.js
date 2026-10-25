/**
 * İxrac düymələri (`gopanel.exports.buttons`) - endirmə linkinə cari
 * süzgəcləri qoşur və «Mailə göndər» pəncərəsini açır.
 *
 * Səhifəyə JS yazılmır: partial `data-*` atributları qoyur, qalanı buradadır.
 * Hansı bölməyə ixrac əlavə olunursa, süzgəc və mail özü ilə gəlir.
 *
 * Süzgəclər iki yerdən yığılır:
 *   1. `data-gp-export-mail-params` / linkdəki sorğu sətri - sabit olanlar;
 *   2. `data-gp-export-filters` seçicisindəki sahələr - `name` və ya
 *      `data-export-param` atributu olan input/select-lər (klik anında oxunur,
 *      ona görə fayl həmişə ekranda görünəni verir).
 */
(function () {
    'use strict';

    var DEFAULT_FORMATS = [{ value: 'excel', label: 'Excel', icon: 'bx bx-spreadsheet text-success' }];

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /** Konteynerdəki süzgəc sahələri → {açar: dəyər}; boşlar atılır. */
    function filterValues(selector) {
        var values = {};

        if (!selector) {
            return values;
        }

        var container = document.querySelector(selector);

        if (!container) {
            return values;
        }

        container.querySelectorAll('[name], [data-export-param]').forEach(function (field) {
            var key = field.getAttribute('data-export-param') || field.getAttribute('name');

            if (!key || key === '_token' || field.disabled) {
                return;
            }

            if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) {
                return;
            }

            var value = field.value;

            if (value !== null && value !== undefined && String(value).trim() !== '') {
                values[key] = String(value).trim();
            }
        });

        return values;
    }

    /** Linkin ünvanına cari süzgəcləri yazır (köhnə dəyərləri üstələyir). */
    function applyFilters(link) {
        var url = new URL(link.href, window.location.origin);
        var values = filterValues(link.getAttribute('data-gp-export-filters'));

        Object.keys(values).forEach(function (key) {
            url.searchParams.set(key, values[key]);
        });

        link.href = url.toString();
    }

    /** Mail düyməsinin dropdown-undakı endirmə linki - süzgəclərin mənbəyi. */
    function exportLink(button) {
        var group = button.closest('.gp-export') || button.closest('.btn-group');

        return group ? group.querySelector('a[data-gp-export]') : null;
    }

    /** @return {Object} açar → dəyər (`key` və `format` daxil edilmir) */
    function collectParams(button) {
        var params = {};
        var staticParams = button.getAttribute('data-gp-export-mail-params');

        if (staticParams) {
            try {
                var parsed = JSON.parse(staticParams);
                Object.keys(parsed).forEach(function (key) {
                    if (parsed[key] !== null && parsed[key] !== '') {
                        params[key] = String(parsed[key]);
                    }
                });
            } catch (e) {
                // Pozuq JSON göndərməni dayandırmamalıdır - süzgəclərlə davam edilir.
            }
        }

        var link = exportLink(button);

        if (link) {
            applyFilters(link);
            new URL(link.href, window.location.origin).searchParams.forEach(function (value, key) {
                if (key !== 'format' && key !== 'key' && value !== '') {
                    params[key] = value;
                }
            });
        }

        return params;
    }

    function formats(button) {
        try {
            var list = JSON.parse(button.getAttribute('data-gp-export-mail-formats') || '[]');
            return list.length ? list : DEFAULT_FORMATS;
        } catch (e) {
            return DEFAULT_FORMATS;
        }
    }

    function formatCard(format, checked) {
        return ''
            + '<label class="gp-export-mail__format' + (checked ? ' is-active' : '') + '">'
            + '<input type="radio" name="gp-export-format" value="' + escapeHtml(format.value) + '"' + (checked ? ' checked' : '') + '>'
            + '<i class="' + escapeHtml(format.icon) + '"></i>'
            + '<span>' + escapeHtml(format.label) + '</span>'
            + '</label>';
    }

    function dialogHtml(button) {
        var to = button.getAttribute('data-gp-export-mail-to') || '';

        return ''
            + '<input type="email" id="gp-export-mail-email" class="form-control gp-export-mail__input"'
            + ' placeholder="nümunə@mail.az" value="' + escapeHtml(to) + '">'
            + '<div class="gp-export-mail__formats">'
            + formats(button).map(function (format, index) { return formatCard(format, index === 0); }).join('')
            + '</div>'
            + '<textarea id="gp-export-mail-note" class="form-control gp-export-mail__note" rows="2" maxlength="500"'
            + ' placeholder="Məktuba qeyd (istəyə bağlı)"></textarea>';
    }

    function selectedFormat() {
        var checked = document.querySelector('input[name="gp-export-format"]:checked');

        return checked ? checked.value : 'excel';
    }

    function send(button, email, format, note) {
        return $.ajax({
            url: button.getAttribute('data-gp-export-mail-url'),
            method: 'POST',
            data: {
                _token: csrfToken(),
                key: button.getAttribute('data-gp-export-mail'),
                format: format,
                email: email,
                note: note,
                params: collectParams(button)
            }
        });
    }

    function errorMessage(xhr) {
        var json = xhr && xhr.responseJSON;

        if (json && json.errors) {
            var first = Object.keys(json.errors)[0];
            if (first && json.errors[first].length) {
                return json.errors[first][0];
            }
        }

        return (json && json.message) || 'Göndərmə alınmadı.';
    }

    function open(button) {
        if (typeof Swal === 'undefined') {
            return;
        }

        Swal.fire({
            title: escapeHtml(button.getAttribute('data-gp-export-mail-title') || 'Sənədi') + ' - mailə göndər',
            html: dialogHtml(button),
            showCancelButton: true,
            confirmButtonText: 'Göndər',
            cancelButtonText: 'İmtina',
            customClass: { popup: 'gp-export-mail' },
            focusConfirm: false,
            // Fayl böyük ola bilər - admin ikinci dəfə basmasın.
            showLoaderOnConfirm: true,
            allowOutsideClick: function () { return !Swal.isLoading(); },
            didOpen: function () {
                var input = document.getElementById('gp-export-mail-email');

                if (input) {
                    input.focus();
                    input.select();
                }

                // Seçilən kartın çərçivəsi dəyişsin - radio dairəsi kiçikdir.
                document.querySelectorAll('.gp-export-mail__format input').forEach(function (radio) {
                    radio.addEventListener('change', function () {
                        document.querySelectorAll('.gp-export-mail__format').forEach(function (card) {
                            card.classList.remove('is-active');
                        });
                        radio.closest('.gp-export-mail__format').classList.add('is-active');
                    });
                });
            },
            preConfirm: function () {
                var input = document.getElementById('gp-export-mail-email');
                var noteField = document.getElementById('gp-export-mail-note');
                var email = input ? input.value.trim() : '';

                if (!email || email.indexOf('@') < 1) {
                    Swal.showValidationMessage('Düzgün e-poçt ünvanı yazın.');
                    return false;
                }

                return send(button, email, selectedFormat(), noteField ? noteField.value.trim() : '')
                    .then(function (response) { return response; })
                    .catch(function (xhr) {
                        Swal.showValidationMessage(errorMessage(xhr));
                        return false;
                    });
            }
        }).then(function (result) {
            if (!result.isConfirmed || !result.value) {
                return;
            }

            if (typeof toastr !== 'undefined') {
                toastr.success(result.value.message || 'Sənəd göndərilir.');
            }
        });
    }

    // Hadisələr sənədə bağlanır: düymələr AJAX ilə gələn modalların içində də ola bilər.
    document.addEventListener('click', function (event) {
        var mailButton = event.target.closest('[data-gp-export-mail]');

        if (mailButton) {
            event.preventDefault();
            open(mailButton);
            return;
        }

        var link = event.target.closest('a[data-gp-export]');

        if (link) {
            applyFilters(link);
        }
    });
})();
