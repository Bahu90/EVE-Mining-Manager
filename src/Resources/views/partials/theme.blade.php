{{--
    The EVE-RO look: loaded last in every page's head block, after the
    page's own inline styles, while Settings > Dashboard > Look is EVE-RO
    (the default). With the standard look nothing is added and the pages
    keep the plugin's original styling.
--}}
@if(\MiningManager\Support\Look::current() === \MiningManager\Support\Look::EVE_RO)
<link rel="stylesheet" href="{{ \MiningManager\Support\Asset::url('css/mm-theme.css') }}">
{{-- Generated from the pages' own styles by tools/build-theme-pages.php. --}}
<link rel="stylesheet" href="{{ \MiningManager\Support\Asset::url('css/mm-theme-pages.css') }}">
<script>
// Charts drawn with Chart.js pick up the EVE-RO typeface and colours. Chart.js
// is loaded further down each page, so the defaults are set the moment the
// library defines itself, before any chart is created.
(function () {
    if (window.__mmChartLook) { return; }
    window.__mmChartLook = true;
    var chart = window.Chart;
    function apply(value) {
        try {
            value.defaults.font.family = "'Oxanium', 'Source Sans Pro', sans-serif";
            value.defaults.color = '#a7b0be';
            value.defaults.borderColor = '#1b2029';
        } catch (e) {}
    }
    if (chart) { apply(chart); return; }
    try {
        Object.defineProperty(window, 'Chart', {
            configurable: true,
            enumerable: true,
            get: function () { return chart; },
            set: function (value) { chart = value; apply(value); }
        });
    } catch (e) {}
})();
</script>
@endif
