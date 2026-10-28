/*
 * Mining Metrics page: the refinery ring, the daily and trend charts, and
 * the filters, search and sorting on the refinery and player cards.
 * Data comes from window.mmMetrics, written by dashboard/metrics.blade.php.
 * Colours are read from the page's --mmx-* variables, so the charts follow
 * whichever look is on.
 */
(function () {
    'use strict';

    var data = window.mmMetrics || {};

    function cssVar(name, fallback) {
        var value = getComputedStyle(document.documentElement).getPropertyValue(name);
        return (value && value.trim()) || fallback;
    }

    var colours = {
        isk: cssVar('--mmx-blue', '#00aeff'),
        iskDeep: cssVar('--mmx-blue-deep', '#0a6fae'),
        tax: cssVar('--mmx-green', '#3ddc84'),
        volume: cssVar('--mmx-volume', '#8a7a68'),
        idle: cssVar('--mmx-label', '#a7b0be'),
        line: cssVar('--mmx-line', '#1b2029'),
        muted: cssVar('--mmx-muted', '#8b95a5'),
        text: cssVar('--mmx-text', '#eef2f7')
    };

    // "76.98 B", as on the rest of the page.
    function short(value, decimals) {
        var n = Number(value) || 0;
        var abs = Math.abs(n);
        var d = decimals === undefined ? 2 : decimals;
        var steps = [[1e12, 'T'], [1e9, 'B'], [1e6, 'M'], [1e3, 'K']];
        for (var i = 0; i < steps.length; i++) {
            if (abs >= steps[i][0]) {
                return (n / steps[i][0]).toFixed(steps[i][1] === 'K' ? 0 : d) + ' ' + steps[i][1];
            }
        }
        return String(Math.round(n));
    }

    function alpha(hex, a) {
        var m = /^#?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i.exec(hex);
        if (!m) { return hex; }
        return 'rgba(' + parseInt(m[1], 16) + ',' + parseInt(m[2], 16) + ',' + parseInt(m[3], 16) + ',' + a + ')';
    }

    function dayLabel(iso) {
        var d = new Date(iso + 'T00:00:00Z');
        return d.getUTCDate() + ' ' + d.toLocaleString(undefined, { month: 'short', timeZone: 'UTC' });
    }

    function lineSet(label, values, colour, axis, fill) {
        return {
            label: label,
            data: values,
            borderColor: colour,
            backgroundColor: fill ? alpha(colour, 0.12) : colour,
            pointBackgroundColor: colour,
            pointRadius: 3,
            pointHoverRadius: 5,
            borderWidth: 2,
            tension: 0.4,
            fill: !!fill,
            yAxisID: axis
        };
    }

    function chartOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            var unit = ctx.dataset.yAxisID === 'volume' ? ' m³' : ' ISK';
                            return ctx.dataset.label + ': ' + short(ctx.parsed.y) + unit;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: alpha(colours.line, 0.6) },
                    ticks: { color: colours.muted, maxRotation: 0, autoSkip: true }
                },
                isk: {
                    position: 'left',
                    beginAtZero: true,
                    grid: { color: alpha(colours.line, 0.6) },
                    ticks: { color: colours.muted, callback: function (v) { return short(v, 0); } }
                },
                volume: {
                    position: 'right',
                    beginAtZero: true,
                    display: false,
                    grid: { display: false },
                    ticks: { color: colours.volume, callback: function (v) { return short(v, 0); } }
                }
            }
        };
    }

    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); }
    }

    ready(function () {
        if (typeof Chart === 'undefined') { return; }

        // ---- Refinery ring ----
        var ringCanvas = document.getElementById('mmxRing');
        if (ringCanvas && data.ring) {
            new Chart(ringCanvas, {
                type: 'doughnut',
                data: {
                    labels: data.ring.labels,
                    datasets: [{
                        data: [data.ring.ready, data.ring.extracting, data.ring.idle],
                        backgroundColor: [colours.isk, colours.iskDeep, colours.idle],
                        borderColor: cssVar('--mmx-ground', '#050608') === 'transparent' ? colours.line : cssVar('--mmx-ground', '#050608'),
                        borderWidth: 4
                    }]
                },
                options: {
                    cutout: '68%',
                    plugins: { legend: { display: false } },
                    responsive: true,
                    maintainAspectRatio: true
                }
            });
        }

        // ---- Day by day ----
        var dailyCanvas = document.getElementById('mmxDaily');
        if (dailyCanvas && data.daily) {
            new Chart(dailyCanvas, {
                type: 'line',
                data: {
                    labels: data.daily.labels.map(dayLabel),
                    datasets: [
                        lineSet(data.labels.isk, data.daily.isk, colours.isk, 'isk', true),
                        lineSet(data.labels.tax, data.daily.tax, colours.tax, 'isk', true)
                    ]
                },
                // Volume is in m³, not ISK, so it goes in the tooltip rather
                // than as a third line on a scale of its own.
                options: (function () {
                    var o = chartOptions();
                    o.plugins.tooltip.callbacks.footer = function (items) {
                        var i = items.length ? items[0].dataIndex : -1;
                        return i >= 0 ? data.labels.volume + ': ' + short(data.daily.volume[i]) : '';
                    };
                    return o;
                })()
            });
        }

        // ---- Trends, with series toggles ----
        var trendCanvas = document.getElementById('mmxTrend');
        if (trendCanvas && data.trend) {
            var trendChart = new Chart(trendCanvas, {
                type: 'line',
                data: {
                    labels: data.trend.labels,
                    datasets: [
                        lineSet(data.labels.isk, data.trend.isk, colours.isk, 'isk', true),
                        lineSet(data.labels.tax, data.trend.tax, colours.tax, 'isk', true),
                        Object.assign(lineSet(data.labels.volume, data.trend.volume, colours.volume, 'volume', false), { hidden: true })
                    ]
                },
                options: chartOptions()
            });

            var order = { isk: 0, tax: 1, volume: 2 };
            document.querySelectorAll('.mmx-toggle[data-series]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var index = order[button.getAttribute('data-series')];
                    var visible = !trendChart.isDatasetVisible(index);
                    trendChart.setDatasetVisibility(index, visible);
                    button.classList.toggle('active', visible);
                    trendChart.options.scales.volume.display = trendChart.isDatasetVisible(2);
                    trendChart.update();
                });
            });
        }
    });

    // ---- Refinery cards: tier filter and search ----
    ready(function () {
        var section = document.getElementById('mmx-structures');
        if (!section) { return; }

        var cards = Array.prototype.slice.call(section.querySelectorAll('.mmx-card'));
        var search = section.querySelector('[data-search]');
        var count = section.querySelector('[data-count]');
        var idleToggle = section.querySelector('[data-idle-toggle]');
        section.classList.add('mmx-js');
        var tier = 'all';
        var showIdle = false;

        // Idle refineries stay hidden until asked for. The count is the mined
        // ones against every refinery the filters match.
        function apply() {
            var text = (search.value || '').trim().toLowerCase();
            var matching = 0;
            var active = 0;
            cards.forEach(function (card) {
                var isActive = card.getAttribute('data-active') === '1';
                var matches = (tier === 'all' || card.getAttribute('data-tier') === tier)
                    && (!text || card.getAttribute('data-text').indexOf(text) !== -1);
                card.classList.toggle('mmx-hidden', !matches || (!isActive && !showIdle));
                if (matches) {
                    matching++;
                    if (isActive) { active++; }
                }
            });
            count.textContent = active + ' / ' + matching;
        }

        if (idleToggle) {
            idleToggle.addEventListener('click', function () {
                showIdle = !showIdle;
                idleToggle.textContent = idleToggle.getAttribute(showIdle ? 'data-hide' : 'data-show');
                apply();
            });
        }

        section.querySelectorAll('[data-tier]').forEach(function (button) {
            if (button.tagName !== 'BUTTON') { return; }
            button.addEventListener('click', function () {
                tier = button.getAttribute('data-tier');
                section.querySelectorAll('button[data-tier]').forEach(function (b) {
                    b.classList.toggle('active', b === button);
                });
                apply();
            });
        });
        search.addEventListener('input', apply);
        apply();
    });

    // ---- Player cards: sort and search ----
    ready(function () {
        var section = document.getElementById('mmx-players');
        if (!section) { return; }

        var grid = section.querySelector('[data-sortable]');
        var cards = Array.prototype.slice.call(grid.querySelectorAll('.mmx-card'));
        var search = section.querySelector('[data-search]');
        var count = section.querySelector('[data-count]');
        var classes = { volume: 'mmx-vol', isk: 'mmx-isk', tax: 'mmx-taxc' };

        function sortBy(key) {
            cards.sort(function (a, b) {
                return Number(b.getAttribute('data-' + key)) - Number(a.getAttribute('data-' + key));
            });
            cards.forEach(function (card) {
                grid.appendChild(card);
                var big = card.querySelector('[data-big]');
                var text = big.getAttribute('data-' + key + '-text');
                big.textContent = text || big.getAttribute('data-volume-text');
                big.className = 'mmx-card-big ' + (text ? classes[key] : classes.volume);
            });
        }

        section.querySelectorAll('[data-sort]').forEach(function (button) {
            button.addEventListener('click', function () {
                section.querySelectorAll('[data-sort]').forEach(function (b) {
                    b.classList.toggle('active', b === button);
                });
                sortBy(button.getAttribute('data-sort'));
            });
        });

        search.addEventListener('input', function () {
            var text = (search.value || '').trim().toLowerCase();
            var shown = 0;
            cards.forEach(function (card) {
                var visible = !text || card.getAttribute('data-text').indexOf(text) !== -1;
                card.classList.toggle('mmx-hidden', !visible);
                if (visible) { shown++; }
            });
            count.textContent = shown;
        });
    });
})();
