// =====================================================
// Analytics Dashboard — chart bridge layer
// All data comes from Livewire (server-side computed),
// this file owns the chart instances + dateRangePicker.
// =====================================================

(function () {
    var leafletMap = null;
    var leafletMarkers = null;
    var charts = {};
    var sparklinesRendered = false;

    function chartHeight(key, isFullscreen) {
        if (isFullscreen) return Math.max(560, window.innerHeight - 130);
        if (key === 'cities' || key === 'languages' || key === 'os') return 430;
        return 340;
    }

    function smallAreaChart(selector) {
        if (!document.querySelector(selector)) return;
        new ApexCharts(document.querySelector(selector), {
            chart: { type: 'area', height: 60, sparkline: { enabled: true } },
            stroke: { curve: 'smooth', width: 2 },
            fill: { opacity: 0.2 },
            colors: ['#556ee6'],
            series: [{ data: Array.from({ length: 12 }, function() { return Math.floor(Math.random() * 40) + 10; }) }]
        }).render();
    }

    function renderTopHits(data) {
        function setSummary(key, d) {
            var el = document.getElementById('summary-' + key);
            if (el) el.textContent = d.current.toLocaleString();
            var ch = document.getElementById('summary-' + key + '-change');
            if (ch) ch.textContent = Math.abs(d.change) + '%';
            var badge = document.getElementById('summary-' + key + '-badge');
            if (badge) badge.className = 'badge ' + (d.trend === 'increase' ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger');
            var trend = document.getElementById('summary-' + key + '-trend');
            if (trend) trend.textContent = (d.trend === 'increase' ? 'artım' : 'azalma') + ' əvvəlki dövrlə';
        }
        setSummary('total-hits', data.total);
        setSummary('countries', data.countries);
        setSummary('cities', data.cities);
        setSummary('adclicks', data.adclicks);

        if (!sparklinesRendered) {
            smallAreaChart('#chart-total-hits');
            smallAreaChart('#chart-countries');
            smallAreaChart('#chart-cities');
            smallAreaChart('#chart-adclicks');
            sparklinesRendered = true;
        }
    }

    function renderCountriesMap(data) {
        var mapEl = document.getElementById('leaflet-map');
        if (!mapEl) return;

        if (!leafletMap) {
            leafletMap = L.map('leaflet-map', { scrollWheelZoom: false, zoomControl: true }).setView([35, 35], 2);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; <a href="https://carto.com/">CARTO</a>',
                subdomains: 'abcd', maxZoom: 19,
            }).addTo(leafletMap);
            leafletMarkers = L.layerGroup().addTo(leafletMap);
        }

        leafletMarkers.clearLayers();
        if (!data || data.length === 0) return;

        data.forEach(function (item, index) {
            if (!item.lat || !item.lng) return;
            var markerHtml = '<div class="analytics-marker-drop" style="animation-delay:' + (index * 120) + 'ms"><div class="leaflet-marker-pin"><span>' + item.hits + '</span></div></div>';
            var icon = L.divIcon({ className: 'analytics-marker', html: markerHtml, iconSize: [36, 36], iconAnchor: [18, 36], popupAnchor: [0, -38] });
            var flagHtml = item.flag ? '<img src="' + item.flag + '" width="20" style="vertical-align:middle;margin-right:6px;border-radius:2px;">' : '';
            var popup =
                '<div class="analytics-popup"><h6>' + flagHtml + item.name + '</h6>' +
                '<div class="popup-row"><i class="bx bx-mouse-alt"></i> Keçid: <strong>' + item.hits + '</strong></div>' +
                '<div class="popup-row"><i class="bx bx-buildings"></i> Şəhər: <strong>' + item.city_count + '</strong></div>' +
                '<div class="popup-row"><i class="bx bx-map-pin"></i> Top şəhər: <strong>' + item.top_city + '</strong></div>' +
                '<div class="popup-row"><i class="bx bx-time-five"></i> Son giriş: ' + item.last_visit + '</div>' +
                '<div class="popup-progress"><div class="popup-progress-bar" style="width:' + item.percent + '%"></div></div>' +
                '<div class="popup-percent">Trafik payı: ' + item.percent + '%</div></div>';
            L.marker([item.lat, item.lng], { icon: icon })
                .bindPopup(popup, { maxWidth: 260, autoPan: true, keepInView: true })
                .addTo(leafletMarkers);
        });

        if (leafletMarkers.getLayers().length > 0) {
            var group = new L.featureGroup(leafletMarkers.getLayers());
            leafletMap.fitBounds(group.getBounds().pad(0.2));
        }
    }

    function renderCitiesBar(res) {
        var el = document.querySelector('#cities-bar-chart');
        if (!el) return;
        if (charts.cities) charts.cities.destroy();
        var topValue = Math.max.apply(null, res.hits || [0]);

        charts.cities = new ApexCharts(el, {
            chart: { type: 'bar', height: chartHeight('cities', document.getElementById('analyticsCitiesCard')?.classList.contains('is-fullscreen')), toolbar: { show: false }, animations: { enabled: true, easing: 'easeinout', speed: 650 } },
            plotOptions: { bar: { horizontal: true, borderRadius: 6, distributed: true, barHeight: '64%' } },
            colors: ['#556ee6', '#34c38f', '#50a5f1', '#f1b44c', '#f46a6a', '#6f42c1', '#2ab57d', '#fd7e14'],
            series: [{ name: 'Klik sayı', data: res.hits }],
            xaxis: {
                categories: res.labels,
                max: topValue ? Math.ceil(topValue * 1.15) : undefined,
                labels: { style: { fontSize: '12px', colors: '#74788d' } },
                axisBorder: { show: false }, axisTicks: { show: false }
            },
            yaxis: { labels: { style: { fontSize: '13px', colors: '#343a40' } } },
            grid: { borderColor: '#edf1f5', strokeDashArray: 4 },
            dataLabels: { enabled: true, textAnchor: 'start', offsetX: 8, style: { colors: ['#343a40'], fontSize: '12px', fontWeight: 600 }, formatter: function (v) { return v.toLocaleString(); } },
            legend: { show: false },
            tooltip: { y: { formatter: function (v) { return v.toLocaleString() + ' keçid'; } } }
        });
        charts.cities.render();
    }

    function renderLanguages(res) {
        var canvas = document.getElementById('languagesLineChart');
        if (!canvas) return;
        if (charts.languages) charts.languages.destroy();

        var wrap = canvas.closest('.analytics-chart-canvas-wrap');
        if (wrap) wrap.style.height = chartHeight('languages', document.getElementById('analyticsLanguagesCard')?.classList.contains('is-fullscreen')) + 'px';

        var ctx = canvas.getContext('2d');
        var gradient = ctx.createLinearGradient(0, 0, 0, 430);
        gradient.addColorStop(0, 'rgba(85,110,230,0.28)');
        gradient.addColorStop(1, 'rgba(85,110,230,0.02)');

        charts.languages = new Chart(ctx, {
            type: 'line',
            data: {
                labels: res.labels.map(function (code, i) { return code + ' – ' + res.name[i]; }),
                datasets: [{
                    label: 'Dil üzrə keçid', data: res.hits,
                    borderColor: '#556ee6', backgroundColor: gradient,
                    fill: true, borderWidth: 3, tension: 0.42,
                    pointBackgroundColor: '#fff', pointBorderColor: '#556ee6', pointBorderWidth: 2,
                    pointRadius: 4, pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: '#edf1f5' }, ticks: { color: '#74788d', precision: 0 } },
                    x: { grid: { display: false }, ticks: { color: '#74788d' } }
                },
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return c.parsed.y.toLocaleString() + ' kecid'; } } } }
            }
        });
    }

    function renderOs(res) {
        var canvas = document.getElementById('osBarChart');
        if (!canvas) return;
        if (charts.os) charts.os.destroy();

        var wrap = canvas.closest('.analytics-chart-canvas-wrap');
        if (wrap) wrap.style.height = chartHeight('os', document.getElementById('analyticsOperatingSystemsCard')?.classList.contains('is-fullscreen')) + 'px';

        charts.os = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: res.labels,
                datasets: [{
                    label: 'Keçid sayı', data: res.hits,
                    backgroundColor: ['rgba(85,110,230,0.72)', 'rgba(52,195,143,0.72)', 'rgba(80,165,241,0.72)', 'rgba(241,180,76,0.72)', 'rgba(244,106,106,0.72)', 'rgba(111,66,193,0.72)'],
                    borderColor: ['#556ee6', '#34c38f', '#50a5f1', '#f1b44c', '#f46a6a', '#6f42c1'],
                    borderWidth: 1, borderRadius: 8, barPercentage: 0.72, categoryPercentage: 0.72
                }]
            },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                scales: {
                    x: { beginAtZero: true, grid: { color: '#edf1f5' }, ticks: { color: '#74788d', precision: 0 } },
                    y: { grid: { display: false }, ticks: { color: '#343a40' } }
                },
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return c.parsed.x.toLocaleString() + ' kecid'; } } } }
            }
        });
    }

    function renderDevicesDonut(labels, hits) {
        var el = document.querySelector('#device-chart');
        if (!el) return;
        if (charts.devices) charts.devices.destroy();
        var total = (hits || []).reduce(function (s, v) { return s + Number(v || 0); }, 0);
        charts.devices = new ApexCharts(el, donutConfig(labels, hits, total, ['#556ee6', '#34c38f', '#f46a6a', '#f1b44c', '#50a5f1'], 'devices'));
        charts.devices.render();
    }

    function renderBrowsersDonut(labels, hits) {
        var el = document.querySelector('#browser-chart');
        if (!el) return;
        if (charts.browsers) charts.browsers.destroy();
        var total = (hits || []).reduce(function (s, v) { return s + Number(v || 0); }, 0);
        charts.browsers = new ApexCharts(el, donutConfig(labels, hits, total, ['#556ee6', '#34c38f', '#f46a6a', '#50a5f1', '#f1b44c', '#6f42c1'], 'browsers'));
        charts.browsers.render();
    }

    function donutConfig(labels, hits, total, colors, key) {
        return {
            chart: { type: 'donut', height: chartHeight(key, false), animations: { enabled: true, easing: 'easeinout', speed: 650 } },
            labels: labels, series: hits,
            legend: { position: 'bottom', fontSize: '13px', markers: { radius: 8 } },
            stroke: { width: 3, colors: ['#fff'] },
            plotOptions: { pie: { donut: { size: '68%', labels: { show: true,
                name: { show: true, fontSize: '13px', color: '#74788d' },
                value: { show: true, fontSize: '24px', fontWeight: 700, formatter: function (v) { return Number(v || 0).toLocaleString(); } },
                total: { show: true, label: 'Umumi', fontSize: '12px', color: '#74788d', formatter: function () { return total.toLocaleString(); } }
            } } } },
            dataLabels: { enabled: true, formatter: function (v) { return Math.round(v) + '%'; } },
            tooltip: { y: { formatter: function (v) { return Number(v || 0).toLocaleString() + ' giris'; } } },
            colors: colors
        };
    }

    function renderAll(data) {
        renderTopHits(data.topHits);
        renderCountriesMap(data.countriesMap);
        renderCitiesBar(data.cities);
        renderLanguages(data.languages);
        renderOs(data.os);
    }

    function initDateRangePicker(component, boot) {
        var el = document.getElementById('analyticsDateRange');
        if (!el || typeof jQuery === 'undefined' || !jQuery.fn.daterangepicker) return;
        var startDate = moment(boot.dateFrom, 'YYYY-MM-DD');
        var endDate   = moment(boot.dateTo, 'YYYY-MM-DD');
        if (!startDate.isValid()) startDate = moment().subtract(6, 'days');
        if (!endDate.isValid())   endDate   = moment();
        jQuery(el).daterangepicker({
            startDate: startDate,
            endDate: endDate,
            locale: {
                format: 'DD/MM/YYYY', applyLabel: 'Tətbiq et', cancelLabel: 'Ləğv et',
                customRangeLabel: 'Öz ilə seçim',
                daysOfWeek: ['B.e','Ç.a','Ç','C.a','C','Ş','Hz'],
                monthNames: ['Yanvar','Fevral','Mart','Aprel','May','İyun','İyul','Avqust','Sentyabr','Oktyabr','Noyabr','Dekabr'],
                firstDay: 1
            },
            ranges: {
                'Bu gün':      [moment(), moment()],
                'Dünən':       [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Son 7 Gün':   [moment().subtract(6, 'days'), moment()],
                'Son 30 Gün':  [moment().subtract(29, 'days'), moment()],
                'Bu Ay':       [moment().startOf('month'), moment().endOf('month')],
                'Keçən Ay':    [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            alwaysShowCalendars: true, opens: 'left'
        }, function (start, end) {
            document.getElementById('dateRangeLabel').textContent = start.format('DD/MM/YYYY') + ' – ' + end.format('DD/MM/YYYY');
            component.applyDateRange(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
        });
    }

    function init() {
        var boot = window.gpAnalyticsBoot;
        if (!boot) return;

        renderDevicesDonut(boot.devices.labels, boot.devices.hits);
        renderBrowsersDonut(boot.browsers.labels, boot.browsers.hits);
        renderAll({
            topHits: boot.topHits, countriesMap: boot.countriesMap,
            cities: boot.cities, languages: boot.languages, os: boot.os
        });

        if (window.Livewire) {
            var component = Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
            initDateRangePicker(component, boot);

            Livewire.on('analytics-data-updated', function (payload) {
                var data = payload[0]?.data ?? payload.data ?? payload;
                renderAll(data);
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    document.addEventListener('livewire:navigated', init);
})();
