// =====================================================
// Analytics Dashboard JS
// Date: from/to | Leaflet + CartoDB Positron
// =====================================================

var leafletMap = null;
var leafletMarkers = null;
var chartInstances = {};
var ajaxPending = 0;

// ---- Date State ----

function getDateParams() {
    if (typeof analyticsConfig === 'undefined') return '';
    return 'from=' + analyticsConfig.dateFrom + '&to=' + analyticsConfig.dateTo;
}

// ---- Loader (content area only) ----

function showLoader() {
    $('#analyticsLoader').addClass('active');
}
function hideLoader() {
    $('#analyticsLoader').removeClass('active');
}
function trackAjax(promise) {
    ajaxPending++;
    showLoader();
    return promise.always(function() {
        ajaxPending--;
        if (ajaxPending <= 0) {
            ajaxPending = 0;
            hideLoader();
        }
    });
}

// ---- Helpers ----

function smallAreaChart(selector) {
    if (!document.querySelector(selector)) return;
    var options = {
        chart: { type: 'area', height: 60, sparkline: { enabled: true } },
        stroke: { curve: 'smooth', width: 2 },
        fill: { opacity: 0.2 },
        colors: ['#556ee6'],
        series: [{ data: Array.from({ length: 12 }, function() { return Math.floor(Math.random() * 40) + 10; }) }]
    };
    new ApexCharts(document.querySelector(selector), options).render();
}

// ---- Leaflet Map ----

function renderCountryData() {
    var mapEl = document.getElementById('leaflet-map');
    if (!mapEl) return;

    var url = (typeof analyticsConfig !== 'undefined' ? analyticsConfig.routes.countriesMap : '/gopanel/analytics/get/countries-map');

    trackAjax($.get(url + '?' + getDateParams(), function (data) {
        if (!leafletMap) {
            leafletMap = L.map('leaflet-map', {
                scrollWheelZoom: false,
                zoomControl: true,
            }).setView([35, 35], 2);

            // CartoDB Positron — English labels
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; <a href="https://carto.com/">CARTO</a>',
                subdomains: 'abcd',
                maxZoom: 19,
            }).addTo(leafletMap);

            leafletMarkers = L.layerGroup().addTo(leafletMap);
        }

        leafletMarkers.clearLayers();

        if (data.length === 0) return;

        data.forEach(function (item, index) {
            if (!item.lat || !item.lng) return;

            // Pin-shaped marker
            var markerHtml = '<div class="analytics-marker-drop" style="animation-delay:' + (index * 120) + 'ms"><div class="leaflet-marker-pin"><span>' + item.hits + '</span></div></div>';

            var icon = L.divIcon({
                className: 'analytics-marker',
                html: markerHtml,
                iconSize: [36, 36],
                iconAnchor: [18, 36],  // bottom-center of pin
                popupAnchor: [0, -38]  // popup above the pin
            });

            var flagHtml = item.flag ? '<img src="' + item.flag + '" width="20" style="vertical-align:middle;margin-right:6px;border-radius:2px;">' : '';

            var popupContent =
                '<div class="analytics-popup">' +
                    '<h6>' + flagHtml + item.name + '</h6>' +
                    '<div class="popup-row"><i class="bx bx-mouse-alt"></i> Keçid: <strong>' + item.hits + '</strong></div>' +
                    '<div class="popup-row"><i class="bx bx-buildings"></i> Şəhər: <strong>' + item.city_count + '</strong></div>' +
                    '<div class="popup-row"><i class="bx bx-map-pin"></i> Top şəhər: <strong>' + item.top_city + '</strong></div>' +
                    '<div class="popup-row"><i class="bx bx-time-five"></i> Son giriş: ' + item.last_visit + '</div>' +
                    '<div class="popup-progress"><div class="popup-progress-bar" style="width:' + item.percent + '%"></div></div>' +
                    '<div class="popup-percent">Trafik payı: ' + item.percent + '%</div>' +
                '</div>';

            var marker = L.marker([item.lat, item.lng], { icon: icon })
                .bindPopup(popupContent, {
                    maxWidth: 260,
                    offset: [0, 0],
                    autoPan: true,
                    autoPanPaddingTopLeft: [24, 24],
                    autoPanPaddingBottomRight: [24, 24],
                    keepInView: true
                })
                .addTo(leafletMarkers);

        });

        // Fit bounds with padding
        if (leafletMarkers.getLayers().length > 0) {
            var group = new L.featureGroup(leafletMarkers.getLayers());
            leafletMap.fitBounds(group.getBounds().pad(0.2));
        }
    }));
}

// ---- Map Fullscreen ----

function initMapFullscreen() {
    var $btn = $('#mapFullscreenBtn');
    var $card = $('#analyticsCountriesMapCard');
    var isFullscreen = false;

    $btn.on('click', function() {
        if (!isFullscreen) {
            $card.addClass('is-fullscreen');
            $('body').addClass('analytics-map-is-fullscreen');
            $btn.find('i').removeClass('bx-fullscreen').addClass('bx-exit-fullscreen');
            isFullscreen = true;
            setTimeout(function() {
                leafletMap.invalidateSize();
                if (leafletMarkers && leafletMarkers.getLayers().length > 0) {
                    var group = new L.featureGroup(leafletMarkers.getLayers());
                    leafletMap.fitBounds(group.getBounds().pad(0.2));
                }
            }, 200);
        } else {
            exitFullscreen();
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && isFullscreen) exitFullscreen();
    });

    function exitFullscreen() {
        $card.removeClass('is-fullscreen');
        $('body').removeClass('analytics-map-is-fullscreen');
        $btn.find('i').removeClass('bx-exit-fullscreen').addClass('bx-fullscreen');
        isFullscreen = false;
        setTimeout(function() {
            leafletMap.invalidateSize();
            if (leafletMarkers && leafletMarkers.getLayers().length > 0) {
                var group = new L.featureGroup(leafletMarkers.getLayers());
                leafletMap.fitBounds(group.getBounds().pad(0.2));
            }
        }, 200);
    }
}

// ---- Chart Fullscreen ----

function analyticsChartHeight(key, isFullscreen) {
    if (isFullscreen) return Math.max(560, window.innerHeight - 130);
    if (key === 'cities' || key === 'languages' || key === 'os') return 430;
    return 340;
}

function resizeAnalyticsChart(key, isFullscreen) {
    var chart = chartInstances[key];
    if (!chart) return;

    if (typeof chart.updateOptions === 'function') {
        chart.updateOptions({
            chart: { height: analyticsChartHeight(key, isFullscreen) }
        }, false, true);
        return;
    }

    if (chart.canvas) {
        $(chart.canvas).closest('.analytics-chart-canvas-wrap').css('height', analyticsChartHeight(key, isFullscreen) + 'px');
        chart.resize();
    }
}

function initAnalyticsFullscreenCards() {
    $(document).on('click', '.analytics-fullscreen-toggle', function() {
        var $btn = $(this);
        var $card = $($btn.data('target'));
        var chartKey = $btn.data('chart');
        var isOpening = !$card.hasClass('is-fullscreen');

        $('.analytics-chart-card.is-fullscreen').not($card).each(function() {
            var $openCard = $(this);
            var $openBtn = $openCard.find('.analytics-fullscreen-toggle');
            $openCard.removeClass('is-fullscreen');
            $openBtn.find('i').removeClass('bx-exit-fullscreen').addClass('bx-fullscreen');
            resizeAnalyticsChart($openBtn.data('chart'), false);
        });

        $card.toggleClass('is-fullscreen', isOpening);
        $('body').toggleClass('analytics-card-is-fullscreen', isOpening);
        $btn.find('i')
            .toggleClass('bx-fullscreen', !isOpening)
            .toggleClass('bx-exit-fullscreen', isOpening);

        setTimeout(function() {
            resizeAnalyticsChart(chartKey, isOpening);
            window.dispatchEvent(new Event('resize'));
        }, 80);
    });

    $(document).on('keydown', function(e) {
        if (e.key !== 'Escape') return;

        $('.analytics-chart-card.is-fullscreen').each(function() {
            var $card = $(this);
            var $btn = $card.find('.analytics-fullscreen-toggle');
            $card.removeClass('is-fullscreen');
            $btn.find('i').removeClass('bx-exit-fullscreen').addClass('bx-fullscreen');
            resizeAnalyticsChart($btn.data('chart'), false);
        });
        $('body').removeClass('analytics-card-is-fullscreen');
    });
}

// ---- Cities Bar Chart ----

function renderCitiesBarChart() {
    var url = (typeof analyticsConfig !== 'undefined' ? analyticsConfig.routes.citiesChart : '/gopanel/analytics/get/cities-chart');

    trackAjax($.get(url + '?' + getDateParams(), function (res) {
        var el = document.querySelector("#cities-bar-chart");
        if (!el) return;

        if (chartInstances['cities']) chartInstances['cities'].destroy();

        var topValue = Math.max.apply(null, res.hits || [0]);
        var options = {
            chart: {
                type: 'bar',
                height: analyticsChartHeight('cities', $('#analyticsCitiesCard').hasClass('is-fullscreen')),
                toolbar: { show: false },
                animations: { enabled: true, easing: 'easeinout', speed: 650 }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 6,
                    distributed: true,
                    barHeight: '64%'
                }
            },
            colors: ['#556ee6', '#34c38f', '#50a5f1', '#f1b44c', '#f46a6a', '#6f42c1', '#2ab57d', '#fd7e14'],
            series: [{ name: 'Klik sayı', data: res.hits }],
            xaxis: {
                categories: res.labels,
                max: topValue ? Math.ceil(topValue * 1.15) : undefined,
                labels: { style: { fontSize: '12px', colors: '#74788d' } },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: { style: { fontSize: '13px', colors: '#343a40' } }
            },
            grid: { borderColor: '#edf1f5', strokeDashArray: 4 },
            dataLabels: {
                enabled: true,
                textAnchor: 'start',
                offsetX: 8,
                style: { colors: ['#343a40'], fontSize: '12px', fontWeight: 600 },
                formatter: function (v) { return v.toLocaleString(); }
            },
            legend: { show: false },
            tooltip: { y: { formatter: function (v) { return v.toLocaleString() + " keçid"; } } }
        };

        chartInstances['cities'] = new ApexCharts(el, options);
        chartInstances['cities'].render();
    }));
}

// ---- Top Hits Summary ----

function renderTotalHits() {
    var url = (typeof analyticsConfig !== 'undefined' ? analyticsConfig.routes.topHits : '/gopanel/analytics/get/top-hits');

    trackAjax($.get(url + '?' + getDateParams(), function (res) {
        function updateSummary(key, data) {
            $('#summary-' + key).text(data.current.toLocaleString());
            $('#summary-' + key + '-change').text(Math.abs(data.change) + '%');
            $('#summary-' + key + '-badge')
                .removeClass()
                .addClass('badge ' + (data.trend === 'increase'
                    ? 'bg-soft-success text-success'
                    : 'bg-soft-danger text-danger'));
            $('#summary-' + key + '-trend').text(
                (data.trend === 'increase' ? 'artım' : 'azalma') + ' əvvəlki dövrlə'
            );
        }

        updateSummary('total-hits', res.total);
        updateSummary('countries', res.countries);
        updateSummary('cities', res.cities);
        updateSummary('adclicks', res.adclicks);

        if (!chartInstances['sparklines_rendered']) {
            smallAreaChart('#chart-total-hits');
            smallAreaChart('#chart-countries');
            smallAreaChart('#chart-cities');
            smallAreaChart('#chart-adclicks');
            chartInstances['sparklines_rendered'] = true;
        }
    }));
}

// ---- Languages Chart ----

function renderLanguagesChart() {
    var url = (typeof analyticsConfig !== 'undefined' ? analyticsConfig.routes.languagesChart : '/gopanel/analytics/get/languages-chart');

    trackAjax($.get(url + '?' + getDateParams(), function (res) {
        var canvas = document.getElementById('languagesLineChart');
        if (!canvas) return;
        if (chartInstances['languages']) chartInstances['languages'].destroy();

        $(canvas).closest('.analytics-chart-canvas-wrap').css(
            'height',
            analyticsChartHeight('languages', $('#analyticsLanguagesCard').hasClass('is-fullscreen')) + 'px'
        );

        var ctx = canvas.getContext('2d');
        var gradient = ctx.createLinearGradient(0, 0, 0, 430);
        gradient.addColorStop(0, 'rgba(85,110,230,0.28)');
        gradient.addColorStop(1, 'rgba(85,110,230,0.02)');

        chartInstances['languages'] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: res.labels.map(function (code, i) { return code + ' – ' + res.name[i]; }),
                datasets: [{
                    label: "Dil üzrə keçid",
                    data: res.hits,
                    borderColor: "#556ee6",
                    backgroundColor: gradient,
                    fill: true,
                    borderWidth: 3, tension: 0.42,
                    pointBackgroundColor: "#fff",
                    pointBorderColor: "#556ee6",
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#edf1f5' },
                        ticks: { color: '#74788d', precision: 0 }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#74788d' }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y.toLocaleString() + ' kecid';
                            }
                        }
                    }
                }
            }
        });
    }));
}

// ---- OS Chart ----

function renderOperatingSystemsChart() {
    var url = (typeof analyticsConfig !== 'undefined' ? analyticsConfig.routes.osChart : '/gopanel/analytics/get/os-chart');

    trackAjax($.get(url + '?' + getDateParams(), function (res) {
        var canvas = document.getElementById('osBarChart');
        if (!canvas) return;
        if (chartInstances['os']) chartInstances['os'].destroy();

        $(canvas).closest('.analytics-chart-canvas-wrap').css(
            'height',
            analyticsChartHeight('os', $('#analyticsOperatingSystemsCard').hasClass('is-fullscreen')) + 'px'
        );

        chartInstances['os'] = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: res.labels,
                datasets: [{
                    label: "Keçid sayı",
                    data: res.hits,
                    backgroundColor: [
                        "rgba(85,110,230,0.72)",
                        "rgba(52,195,143,0.72)",
                        "rgba(80,165,241,0.72)",
                        "rgba(241,180,76,0.72)",
                        "rgba(244,106,106,0.72)",
                        "rgba(111,66,193,0.72)"
                    ],
                    borderColor: [
                        "#556ee6",
                        "#34c38f",
                        "#50a5f1",
                        "#f1b44c",
                        "#f46a6a",
                        "#6f42c1"
                    ],
                    borderWidth: 1,
                    borderRadius: 8,
                    barPercentage: 0.72,
                    categoryPercentage: 0.72
                }]
            },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: '#edf1f5' },
                        ticks: { color: '#74788d', precision: 0 }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: '#343a40' }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.x.toLocaleString() + ' kecid';
                            }
                        }
                    }
                }
            }
        });
    }));
}

// ---- Global refresh ----

function refreshAllAnalytics() {
    renderTotalHits();
    renderCountryData();
    renderCitiesBarChart();
    renderLanguagesChart();
    renderOperatingSystemsChart();
}

function updateDetailLinks() {
    var params = 'from=' + analyticsConfig.dateFrom + '&to=' + analyticsConfig.dateTo;
    $('a[data-detail-link]').each(function() {
        var base = $(this).data('base-href') || $(this).attr('href').split('?')[0];
        $(this).data('base-href', base);
        $(this).attr('href', base + '?' + params);
    });
}

// ---- DateRangePicker Init ----

function initDateRangePicker() {
    if (typeof analyticsConfig === 'undefined' || !$('#analyticsDateRange').length) return;

    var startMoment = moment(analyticsConfig.dateFrom, 'YYYY-MM-DD');
    var endMoment   = moment(analyticsConfig.dateTo, 'YYYY-MM-DD');

    $('#analyticsDateRange').daterangepicker({
        startDate: startMoment,
        endDate: endMoment,
        locale: {
            format: 'DD/MM/YYYY',
            applyLabel: 'Tətbiq et',
            cancelLabel: 'Ləğv et',
            customRangeLabel: 'Öz ilə seçim',
            daysOfWeek: ['B.e','Ç.a','Ç','C.a','C','Ş','Hz'],
            monthNames: ['Yanvar','Fevral','Mart','Aprel','May','İyun',
                         'İyul','Avqust','Sentyabr','Oktyabr','Noyabr','Dekabr'],
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
        alwaysShowCalendars: true,
        opens: 'left'
    }, function(start, end) {
        $('#dateRangeLabel').text(start.format('DD/MM/YYYY') + ' – ' + end.format('DD/MM/YYYY'));

        analyticsConfig.dateFrom = start.format('YYYY-MM-DD');
        analyticsConfig.dateTo   = end.format('YYYY-MM-DD');

        // Update URL
        var url = new URL(window.location);
        url.searchParams.set('from', analyticsConfig.dateFrom);
        url.searchParams.set('to', analyticsConfig.dateTo);
        history.pushState({}, '', url);

        updateDetailLinks();
        refreshAllAnalytics();
    });
}

// ---- Select2 AJAX Init ----

function initSelect2Filters() {
    if (typeof analyticsConfig === 'undefined') return;

    $('#filter-country').select2({
        placeholder: 'Ölkə axtarın...',
        allowClear: true,
        dropdownParent: $('#analyticsFilterPanel'),
        ajax: {
            url: analyticsConfig.routes.searchCountries,
            dataType: 'json',
            delay: 300,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) {
                return {
                    results: data.results.map(function(item) {
                        return { id: item.id, text: item.text, flag: item.flag };
                    })
                };
            },
            cache: true
        },
        templateResult: function(item) {
            if (!item.id) return item.text;
            var flagImg = item.flag ? '<img src="' + item.flag + '" width="18" style="margin-right:6px;vertical-align:middle;border-radius:2px;">' : '';
            return $('<span>' + flagImg + item.text + '</span>');
        },
        minimumInputLength: 0
    });

    $('#filter-city').select2({
        placeholder: 'Şəhər axtarın...',
        allowClear: true,
        dropdownParent: $('#analyticsFilterPanel'),
        ajax: {
            url: analyticsConfig.routes.searchCities,
            dataType: 'json',
            delay: 300,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) { return { results: data.results }; },
            cache: true
        },
        minimumInputLength: 0
    });
}

// ---- Filter Toggle Text ----

function initFilterToggle() {
    var $btn = $('#toggleFilterBtn');
    var $panel = $('#analyticsFilterPanel');

    $panel.on('show.bs.collapse', function() {
        $btn.html('<i class="fas fa-times"></i> Filteri bağla');
    });
    $panel.on('hide.bs.collapse', function() {
        $btn.html('<i class="fas fa-filter"></i> Filter');
    });
}

// ---- Filter Apply/Clear (query params + page reload) ----

function initFilterButtons() {
    $('#applyAnalyticsFilters').on('click', function() {
        var url = new URL(window.location);
        var country = $('#filter-country').val();
        var city    = $('#filter-city').val();
        var browser = $('#filter-browser').val();
        var device  = $('#filter-device').val();

        if (country) url.searchParams.set('country_id', country); else url.searchParams.delete('country_id');
        if (city)    url.searchParams.set('city_id', city);       else url.searchParams.delete('city_id');
        if (browser) url.searchParams.set('browser_id', browser); else url.searchParams.delete('browser_id');
        if (device)  url.searchParams.set('device_id', device);   else url.searchParams.delete('device_id');

        window.location.href = url.toString();
    });

    $('#clearAnalyticsFilters').on('click', function() {
        var url = new URL(window.location);
        url.searchParams.delete('country_id');
        url.searchParams.delete('city_id');
        url.searchParams.delete('browser_id');
        url.searchParams.delete('device_id');
        window.location.href = url.toString();
    });
}

// ---- Init on DOM ready ----

$(function () {
    if (document.querySelector("#analyticsWrapperDahboard")) {
        initDateRangePicker();
        initSelect2Filters();
        initFilterButtons();
        initFilterToggle();
        initMapFullscreen();
        initAnalyticsFullscreenCards();
        updateDetailLinks();
        refreshAllAnalytics();
    }
});


// ---- Devices donut Chart ----

if (typeof deviceLabels !== 'undefined' && document.querySelector("#device-chart")) {
    var deviceTotal = deviceHits.reduce(function(sum, value) { return sum + Number(value || 0); }, 0);
    chartInstances['devices'] = new ApexCharts(document.querySelector("#device-chart"), {
        chart: {
            type: 'donut',
            height: analyticsChartHeight('devices', $('#analyticsDevicesCard').hasClass('is-fullscreen')),
            animations: { enabled: true, easing: 'easeinout', speed: 650 }
        },
        labels: deviceLabels,
        series: deviceHits,
        legend: { position: 'bottom', fontSize: '13px', markers: { radius: 8 } },
        stroke: { width: 3, colors: ['#fff'] },
        plotOptions: {
            pie: {
                donut: {
                    size: '68%',
                    labels: {
                        show: true,
                        name: { show: true, fontSize: '13px', color: '#74788d' },
                        value: {
                            show: true,
                            fontSize: '24px',
                            fontWeight: 700,
                            formatter: function (val) { return Number(val || 0).toLocaleString(); }
                        },
                        total: {
                            show: true,
                            label: 'Umumi',
                            fontSize: '12px',
                            color: '#74788d',
                            formatter: function () { return deviceTotal.toLocaleString(); }
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) { return Math.round(val) + '%'; }
        },
        tooltip: { y: { formatter: function (v) { return Number(v || 0).toLocaleString() + ' giris'; } } },
        colors: ['#556ee6', '#34c38f', '#f46a6a', '#f1b44c', '#50a5f1'],
    });
    chartInstances['devices'].render();
}


// ---- Browser Pie Chart ----

if (typeof browserLabels !== 'undefined' && document.querySelector("#browser-chart")) {
    var browserTotal = browserHits.reduce(function(sum, value) { return sum + Number(value || 0); }, 0);
    chartInstances['browsers'] = new ApexCharts(document.querySelector("#browser-chart"), {
        chart: {
            height: analyticsChartHeight('browsers', $('#analyticsBrowsersCard').hasClass('is-fullscreen')),
            type: 'donut',
            animations: { enabled: true, easing: 'easeinout', speed: 650 }
        },
        labels: browserLabels,
        series: browserHits,
        legend: { position: 'bottom', fontSize: '13px', markers: { radius: 8 } },
        stroke: { width: 3, colors: ['#fff'] },
        plotOptions: {
            pie: {
                donut: {
                    size: '66%',
                    labels: {
                        show: true,
                        name: { show: true, fontSize: '13px', color: '#74788d' },
                        value: {
                            show: true,
                            fontSize: '24px',
                            fontWeight: 700,
                            formatter: function (val) { return Number(val || 0).toLocaleString(); }
                        },
                        total: {
                            show: true,
                            label: 'Umumi',
                            fontSize: '12px',
                            color: '#74788d',
                            formatter: function () { return browserTotal.toLocaleString(); }
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) { return Math.round(val) + '%'; }
        },
        tooltip: { y: { formatter: function (v) { return Number(v || 0).toLocaleString() + ' giris'; } } },
        colors: ['#556ee6', '#34c38f', '#f46a6a', '#50a5f1', '#f1b44c', '#6f42c1'],
    });
    chartInstances['browsers'].render();
}


// ---- Detail page filter toggle (old pattern compat) ----

$(document).on('click', '#openFilter', function () {
    var wrapper = $("#filterWrapper");
    wrapper.slideToggle(250);
    if (wrapper.is(":visible")) {
        $(this).html('<i class="fas fa-filter"></i> Filteri aç');
    } else {
        $(this).html('<i class="fas fa-times"></i> Filteri bağla');
    }
});
