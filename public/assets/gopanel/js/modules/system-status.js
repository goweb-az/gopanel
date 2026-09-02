/**
 * Sistem vəziyyəti (monitor) — qrafiklər və avtomatik yenilənmə.
 *
 * Blade tərəfdə lazımdır (bax: gopanel/pages/system_status/index.blade.php):
 *   #system-status[data-url][data-refresh][data-history][data-gauges]
 *   #gauge-cpu  #gauge-memory  #gauge-disk  #chart-live
 *   #meta-cpu   #meta-memory   #meta-disk   #system-live
 *   #system-checked-at  #system-autorefresh  #system-refresh
 *
 * Bu fayl HEÇ NƏ formatlamır: bütün mətn serverdə blade ilə hazırlanıb hazır
 * HTML kimi gəlir (01-umumi.md § 3). Burada yalnız qrafik rəqəmləri və
 * blokların yerinə qoyulması var.
 */
(function () {
    "use strict";

    // Ton adı → Skote rəngi. Hansı tonun seçilməsi serverdə həll olunur
    // (config/gopanel/system_status.php → thresholds).
    var TONE_COLORS = {
        success: "#34c38f",
        warning: "#f1b44c",
        danger: "#f46a6a",
        secondary: "#adb5bd"
    };

    document.addEventListener("DOMContentLoaded", function () {
        var root = document.getElementById("system-status");

        if (!root || typeof ApexCharts === "undefined") {
            return;
        }

        var url = root.dataset.url;
        var refreshMs = parseInt(root.dataset.refresh, 10) || 5000;
        var historyMax = parseInt(root.dataset.history, 10) || 60;
        var gauges = {};
        var timer = null;
        var busy = false;

        var history = { cpu: [], memory: [], labels: [] };

        try {
            gauges = JSON.parse(root.dataset.gauges || "{}");
        } catch (error) {
            gauges = {};
        }

        /** Bir dairəvi göstərici qurur. */
        function buildGauge(elementId, label, gauge) {
            var element = document.getElementById(elementId);

            if (!element) {
                return null;
            }

            var chart = new ApexCharts(element, {
                chart: { type: "radialBar", height: 200, sparkline: { enabled: true } },
                series: [value(gauge)],
                colors: [color(gauge)],
                plotOptions: {
                    radialBar: {
                        hollow: { size: "60%" },
                        track: { background: "#f1f1f1" },
                        dataLabels: {
                            name: { offsetY: 18, fontSize: "12px", color: "#74788d" },
                            value: { offsetY: -18, fontSize: "20px", fontWeight: 600 }
                        }
                    }
                },
                labels: [label]
            });

            chart.render();

            return chart;
        }

        function value(gauge) {
            return gauge && gauge.value !== null && gauge.value !== undefined
                ? Number(gauge.value)
                : 0;
        }

        function color(gauge) {
            return TONE_COLORS[(gauge && gauge.tone) || "secondary"] || TONE_COLORS.secondary;
        }

        var cpuChart = buildGauge("gauge-cpu", "İstifadə", gauges.cpu);
        var memoryChart = buildGauge("gauge-memory", "Dolu", gauges.memory);
        var diskChart = buildGauge("gauge-disk", "Dolu", gauges.disk);

        var liveElement = document.getElementById("chart-live");
        var liveChart = liveElement
            ? new ApexCharts(liveElement, {
                  chart: {
                      type: "line",
                      height: 200,
                      animations: { enabled: false },
                      toolbar: { show: false }
                  },
                  stroke: { width: 2, curve: "smooth" },
                  colors: [TONE_COLORS.danger, TONE_COLORS.success],
                  series: [
                      { name: "CPU %", data: [] },
                      { name: "RAM %", data: [] }
                  ],
                  xaxis: { categories: [], labels: { show: false }, tooltip: { enabled: false } },
                  yaxis: { min: 0, max: 100, tickAmount: 4 },
                  legend: { position: "bottom", fontSize: "11px" },
                  grid: { borderColor: "#f1f1f1" }
              })
            : null;

        if (liveChart) {
            liveChart.render();
        }

        /** Qrafikləri yeni ölçmə ilə yeniləyir. */
        function updateCharts(data) {
            if (!data) {
                return;
            }

            if (cpuChart && data.cpu) {
                cpuChart.updateOptions({ series: [value(data.cpu)], colors: [color(data.cpu)] }, false, false);
            }

            if (memoryChart && data.memory) {
                memoryChart.updateOptions({ series: [value(data.memory)], colors: [color(data.memory)] }, false, false);
            }

            if (diskChart && data.disk) {
                diskChart.updateOptions({ series: [value(data.disk)], colors: [color(data.disk)] }, false, false);
            }

            if (!liveChart) {
                return;
            }

            history.cpu.push(value(data.cpu));
            history.memory.push(value(data.memory));
            history.labels.push("");

            // Sürüşən pəncərə — köhnə nöqtələr atılır, yaddaş şişmir
            while (history.cpu.length > historyMax) {
                history.cpu.shift();
                history.memory.shift();
                history.labels.shift();
            }

            liveChart.updateOptions(
                {
                    series: [
                        { name: "CPU %", data: history.cpu },
                        { name: "RAM %", data: history.memory }
                    ],
                    xaxis: { categories: history.labels }
                },
                false,
                false
            );
        }

        /** Bloku hazır HTML ilə əvəz edir. */
        function replace(elementId, html) {
            var element = document.getElementById(elementId);

            if (element && typeof html === "string") {
                element.innerHTML = html;
            }
        }

        function refresh() {
            if (busy) {
                return;
            }

            busy = true;

            fetch(url, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
                credentials: "same-origin"
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (json) {
                    // `status` sətirdir: uğurda "success", xətada "error" —
                    // ikisi də truthy-dir, ona görə dəyər müqayisə olunur
                    if (!json || json.status !== "success" || !json.data) {
                        return;
                    }

                    var data = json.data;

                    updateCharts(data.gauges);

                    if (data.html) {
                        replace("meta-cpu", data.html.cpu);
                        replace("meta-memory", data.html.memory);
                        replace("meta-disk", data.html.disk);
                        replace("system-live", data.html.live);
                    }

                    var stamp = document.getElementById("system-checked-at");

                    if (stamp && data.checked_at) {
                        stamp.textContent = data.checked_at;
                    }
                })
                .catch(function () {
                    // Şəbəkə xətası səhifəni dayandırmır — növbəti cəhd gözlənilir
                })
                .finally(function () {
                    busy = false;
                });
        }

        function start() {
            if (timer) {
                return;
            }

            timer = setInterval(function () {
                // Səhifə arxa fonda olanda serveri boş yerə yükləmirik
                if (!document.hidden) {
                    refresh();
                }
            }, refreshMs);
        }

        function stop() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        var toggle = document.getElementById("system-autorefresh");

        if (toggle) {
            toggle.addEventListener("change", function () {
                if (toggle.checked) {
                    refresh();
                    start();
                } else {
                    stop();
                }
            });
        }

        var button = document.getElementById("system-refresh");

        if (button) {
            button.addEventListener("click", refresh);
        }

        // İlk ölçmə qrafikdə dərhal görünsün
        updateCharts(gauges);
        start();
    });
})();
