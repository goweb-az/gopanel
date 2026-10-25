// ─────────────────────────────────────────────────────────────────────────
// «Bu qeydlə kim məşğuldur» - götür / burax / üzərinə götür.
// Server tərəfi: App\Http\Controllers\Gopanel\Concerns\HandlesAssignment
//
// İki giriş nöqtəsi var:
//
//  1. Detal səhifəsindəki zolaq (`component.assignment-banner`):
//       <div data-gp-assignment data-gp-assign-url="…" data-gp-release-url="…"
//            data-gp-assign-state="free|mine|other" …>
//     Səhifə açılanda vəziyyətə görə bir dəfə xəbərdarlıq göstərilir.
//
//  2. Tək düymə (`AuthorCell::claimButton()`, cədvəl sətri):
//       <button data-gp-assign-action data-gp-assign-url="…" …>
//     Hadisə SƏNƏDƏ bağlanır - düymə datatable sətrində və AJAX modalında
//     sonradan yaranır; birbaşa bağlama işləməzdi.
//
// Hər iki halda əməliyyatdan əvvəl təsdiq soruşulur və nə baş verəcəyi
// izah olunur: təyinat digər adminlərin gördüyü bir işarədir.
// ─────────────────────────────────────────────────────────────────────────

(function () {
    'use strict';

    // Eyni qeyd üçün xəbərdarlığın təkrarlanmayacağı müddət (dəqiqə).
    var GREET_SILENCE_MINUTES = 30;

    /** Admin adı Swal-ın `html`-inə düşür - HTML kimi işlənməsin. */
    function esc(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function post(url, data) {
        return $.ajax({
            url: url,
            type: 'POST',
            data: $.extend({ _token: $('meta[name="csrf-token"]').attr('content') }, data || {})
        });
    }

    function reload() {
        window.location.reload();
    }

    /**
     * Cədvəl varsa bütün səhifəni yeniləmək artıqdır - datatable özü yenilənir,
     * açıq modal bağlanır. Cədvəl yoxdursa (detal səhifəsi) səhifə yenilənir.
     */
    function refresh() {
        var tables = ($.fn.dataTable && $.fn.dataTable.tables) ? $.fn.dataTable.tables() : [];

        if (!tables.length) {
            reload();

            return;
        }

        $('.modal.show').modal('hide');

        tables.forEach(function (table) {
            $(table).DataTable().ajax.reload(null, false);
        });
    }

    function fail(response) {
        var message = 'Əməliyyat alınmadı.';

        if (response && response.responseJSON && response.responseJSON.message) {
            message = response.responseJSON.message;
        }

        if (window.toastr) {
            toastr.error(message);
        } else {
            alert(message);
        }
    }

    function done(response) {
        if (window.toastr && response && response.message) {
            toastr.success(response.message);
        }
    }

    /**
     * Təsdiq pəncərəsi. `Swal` yoxdursa əməliyyat davam edir - təsdiq
     * faydalıdır, amma onsuz işi dayandırmaq düzgün deyil.
     */
    function confirm(options) {
        if (!window.Swal) {
            return Promise.resolve(true);
        }

        return Swal.fire({
            title: options.title,
            html: options.html,
            icon: options.icon || 'question',
            showCancelButton: true,
            confirmButtonText: options.confirmText,
            cancelButtonText: 'İmtina'
        }).then(function (result) {
            return result.isConfirmed === true;
        });
    }

    /** «Üzərinə götürmək nə deməkdir» izahı - hər iki axında eynidir. */
    function claimExplanation(label) {
        return 'Bu ' + esc(label) + ' <b>sizin üzərinizə</b> keçəcək:'
            + '<ul class="gp-assign-explain">'
            + '<li>digər adminlər siyahıda sizin adınızı görəcək;</li>'
            + '<li>bu qeydə görə məsul siz sayılacaqsınız;</li>'
            + '<li>işi bitirəndə «İşi burax» ilə geri qaytara bilərsiniz.</li>'
            + '</ul>'
            + '<div class="text-muted gp-assign-note">Səhifə bloklanmır - təyinat yalnız işarədir.</div>';
    }

    function takeoverExplanation(label, owner, stale) {
        return 'Bu ' + esc(label) + ' hazırda <b>' + esc(owner) + '</b> adlı adminin üzərindədir.'
            + (stale ? ' Uzun müddətdir toxunulmayıb.' : '')
            + '<div class="gp-assign-note">Davam etsəniz məsul <b>siz</b> olacaqsınız, '
            + 'əvvəlki qeyd isə tarixçədə qalacaq.</div>';
    }

    function claimDialog(takeover, label, owner, stale) {
        return takeover
            ? {
                title: 'Üzərinizə götürəsiniz?',
                html: takeoverExplanation(label, owner, stale),
                icon: 'warning',
                confirmText: 'Bəli, üzərimə götürürəm'
            }
            : {
                title: 'Üzərinizə götürəsiniz?',
                html: claimExplanation(label),
                confirmText: 'Bəli, götürürəm'
            };
    }

    // ── 1. Detal səhifəsindəki zolaq ────────────────────────────────────

    function bind(root) {
        var assignUrl = root.dataset.gpAssignUrl || '';
        var releaseUrl = root.dataset.gpReleaseUrl || '';
        var label = root.dataset.gpAssignLabel || 'qeyd';
        var owner = root.dataset.gpAssignOwner || '';
        var stale = root.dataset.gpAssignStale === '1';

        $(root).on('click', '[data-gp-assign]', function () {
            var takeover = this.hasAttribute('data-gp-takeover');

            confirm(claimDialog(takeover, label, owner, stale)).then(function (ok) {
                if (ok) {
                    post(assignUrl, takeover ? { takeover: 1 } : {}).done(reload).fail(fail);
                }
            });
        });

        $(root).on('click', '[data-gp-release]', function () {
            confirm({
                title: 'İşi buraxasınız?',
                html: 'Bu ' + esc(label) + ' yenidən <b>sahibsiz</b> olacaq və istənilən admin '
                    + 'onu öz üzərinə götürə biləcək.<div class="text-muted gp-assign-note">'
                    + 'Qeyd silinmir - tarixçədə qalır.</div>',
                icon: 'warning',
                confirmText: 'Bəli, buraxıram'
            }).then(function (ok) {
                if (ok) {
                    post(releaseUrl).done(reload).fail(fail);
                }
            });
        });

        greet(root, assignUrl, label);
    }

    /**
     * Səhifə açılanda xəbərdarlıq.
     *
     * Ard-arda yenilənəndə hər dəfə çıxmasın deyə `sessionStorage`-a işarə
     * qoyulur, amma işarənin **vaxtı var**: həmişəlik susdurulsa admin səhər
     * açdığı qeydə axşam qayıdanda soruşulmazdı.
     */
    function greet(root, assignUrl, label) {
        var state = root.dataset.gpAssignState || 'free';
        var owner = root.dataset.gpAssignOwner || '';
        var stale = root.dataset.gpAssignStale === '1';
        var canTakeover = root.dataset.gpCanTakeover === '1';

        if (state === 'mine' || !window.Swal) {
            return;
        }

        var key = 'gp-assign-seen:' + assignUrl;

        try {
            var seenAt = parseInt(sessionStorage.getItem(key) || '0', 10);

            if (seenAt && (Date.now() - seenAt) < GREET_SILENCE_MINUTES * 60000) {
                return;
            }

            sessionStorage.setItem(key, String(Date.now()));
        } catch (e) {
            // Şəxsi rejimdə sessionStorage bağlı ola bilər - xəbərdarlıq
            // yenə göstərilir, sadəcə təkrarlanmasının qarşısı alınmır.
        }

        if (state === 'free') {
            Swal.fire({
                title: 'Bu qeydə baxırsınız',
                html: claimExplanation(label),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Öz üzərimə götürürəm',
                cancelButtonText: 'Sadəcə baxıram'
            }).then(function (result) {
                if (result.isConfirmed) {
                    post(assignUrl).done(reload).fail(fail);
                }
            });

            return;
        }

        var canTake = canTakeover || stale;

        Swal.fire({
            title: 'Bu qeydlə başqası məşğuldur',
            html: '<b>' + esc(owner) + '</b> bu ' + esc(label) + ' ilə məşğuldur.'
                + (stale
                    ? '<br><span class="text-muted">Uzun müddətdir toxunulmayıb.</span>'
                    : '<br><span class="text-muted">Zəng etməzdən əvvəl onunla dəqiqləşdirin.</span>'),
            icon: 'warning',
            showCancelButton: canTake,
            confirmButtonText: 'Anladım, yalnız baxıram',
            cancelButtonText: 'Üzərimə götürürəm'
        }).then(function (result) {
            if (canTake && result.dismiss === Swal.DismissReason.cancel) {
                post(assignUrl, { takeover: 1 }).done(reload).fail(fail);
            }
        });
    }

    // ── 2. Tək düymə (cədvəl sətri, sürətli baxış) ──────────────────────

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-gp-assign-action]');

        if (!button) {
            return;
        }

        event.preventDefault();

        var takeover = button.hasAttribute('data-gp-takeover');
        var label = button.dataset.gpAssignLabel || 'qeyd';
        var owner = button.dataset.gpAssignOwner || '';
        var stale = button.dataset.gpAssignStale === '1';
        var url = button.dataset.gpAssignUrl;

        confirm(claimDialog(takeover, label, owner, stale)).then(function (ok) {
            if (!ok) {
                return;
            }

            post(url, takeover ? { takeover: 1 } : {})
                .done(function (response) {
                    done(response);
                    refresh();
                })
                .fail(fail);
        });
    });

    $(function () {
        document.querySelectorAll('[data-gp-assignment]').forEach(bind);
    });
})();
