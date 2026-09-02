/**
 * Backup bölməsi — növbəyə salma və vəziyyətin izlənməsi.
 *
 * Blade tərəfdə lazımdır (bax: gopanel/pages/backup/index.blade.php):
 *   #backup-actions[data-start-url][data-status-url][data-in-progress]
 *   button[data-backup-start="database|files"]
 *
 * Ünvanlar JS-də qurulmur — blade `route()` ilə hazır verir (01-umumi.md § 3).
 */
(function () {
    "use strict";

    // Backup işləyərkən cədvəl bu aralıqla yenilənir — gedişat canlı görünsün.
    // Sorğu yalnız işləyən backup varkən gedir, bitəndə dayandırılır.
    var POLL_MS = 2000;

    document.addEventListener("DOMContentLoaded", function () {
        var root = document.getElementById("backup-actions");

        if (!root) {
            return;
        }

        var startUrl = root.dataset.startUrl;
        var statusUrl = root.dataset.statusUrl;
        var token = document.querySelector('meta[name="csrf-token"]');
        var polling = null;

        function csrf() {
            return token ? token.getAttribute("content") : "";
        }

        function notify(icon, text) {
            if (typeof Swal !== "undefined") {
                Swal.fire({ icon: icon, text: text, confirmButtonText: "Bağla" });
            } else {
                alert(text);
            }
        }

        /** Cədvəli yeniləyir — səhifəni tam yükləmədən. */
        function reloadTable() {
            if (window.dTable && window.dTable.ajax) {
                window.dTable.ajax.reload(null, false);
            }
        }

        function setBusy(busy) {
            root.querySelectorAll("[data-backup-start]").forEach(function (btn) {
                btn.disabled = busy;
            });
        }

        /**
         * İşləyən backup varkən vəziyyət soruşulur; bitəndə cədvəl yenilənir
         * və sorğu dayandırılır — boş yerə server yüklənmir.
         */
        function startPolling() {
            if (polling) {
                return;
            }

            setBusy(true);

            polling = setInterval(function () {
                fetch(statusUrl, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                    credentials: "same-origin"
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (json) {
                        var data = json && json.data ? json.data : {};

                        reloadTable();

                        if (!data.in_progress) {
                            clearInterval(polling);
                            polling = null;
                            setBusy(false);
                        }
                    })
                    .catch(function () {
                        clearInterval(polling);
                        polling = null;
                        setBusy(false);
                    });
            }, POLL_MS);
        }

        function start(type, button) {
            var body = new FormData();
            body.append("type", type);
            body.append("_token", csrf());

            button.disabled = true;

            fetch(startUrl, {
                method: "POST",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                credentials: "same-origin",
                body: body
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (json) {
                    // `status` sətirdir: uğurda "success", xətada "error" —
                    // ikisi də truthy-dir, ona görə dəyər müqayisə olunur
                    if (json && json.status === "success") {
                        notify("success", json.message);
                        reloadTable();
                        startPolling();
                    } else {
                        button.disabled = false;
                        notify("error", (json && json.message) || "Backup başladıla bilmədi.");
                    }
                })
                .catch(function () {
                    button.disabled = false;
                    notify("error", "Serverlə əlaqə qurulmadı.");
                });
        }

        root.addEventListener("click", function (event) {
            var button = event.target.closest("[data-backup-start]");

            if (!button) {
                return;
            }

            var type = button.dataset.backupStart;
            var text = type === "files"
                ? "Fayl arxivi çıxarılsın? İlk dəfə uzun çəkə bilər."
                : "Baza arxivi çıxarılsın?";

            if (typeof Swal === "undefined") {
                start(type, button);
                return;
            }

            Swal.fire({
                title: "Təsdiq",
                text: text,
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Bəli, çıxar",
                cancelButtonText: "İmtina"
            }).then(function (result) {
                if (result.isConfirmed) {
                    start(type, button);
                }
            });
        });

        // Səhifə açılanda artıq işləyən backup varsa izləməyə başlanılır
        if (root.dataset.inProgress === "1") {
            startPolling();
        }
    });
})();
