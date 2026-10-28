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
