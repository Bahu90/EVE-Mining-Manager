{{--
    The plugin's base stylesheets, where every page has always loaded them:
    first in the page's head block, so the page's own inline styles still
    follow them. mining-manager-dashboard.css is the standard look;
    mm-metrics.css styles the Mining Metrics dashboard in either look.

    The EVE-RO look is added separately by partials.theme, at the end of the
    head block, so it comes after those inline styles.
--}}
<link rel="stylesheet" href="{{ \MiningManager\Support\Asset::url('css/mining-manager-dashboard.css') }}">
<link rel="stylesheet" href="{{ \MiningManager\Support\Asset::url('css/mm-metrics.css') }}">
{{-- Short ISK for charts and scripts, written like the PHP IskFormat::short:
     "23.43 B", "325.55 M", "749 K". --}}
<script>
    window.mmIsk = window.mmIsk || function (value, decimals) {
        var v = Number(value) || 0, abs = Math.abs(v), sign = v < 0 ? '-' : '';
        var d = decimals === undefined ? 2 : decimals;
        var steps = [[1e12, 'T'], [1e9, 'B'], [1e6, 'M'], [1e3, 'K']];
        for (var i = 0; i < steps.length; i++) {
            if (abs >= steps[i][0] * 0.9995) {
                return sign + (abs / steps[i][0]).toFixed(d) + ' ' + steps[i][1];
            }
        }
        return sign + Math.round(abs).toString();
    };
</script>
