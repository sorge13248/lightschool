@auth
@php
    $accentHex = auth()->user()->expanded->accent ?? '1e6ad3';
    $accentHex = preg_replace('/[^0-9a-fA-F]/', '', $accentHex);
    if (strlen($accentHex) !== 6) { $accentHex = '1e6ad3'; }

    // Parse base RGB
    $r = hexdec(substr($accentHex, 0, 2));
    $g = hexdec(substr($accentHex, 2, 2));
    $b = hexdec(substr($accentHex, 4, 2));

    // Lighter: blend 25% toward white
    $lr  = (int) round($r + (255 - $r) * 0.25);
    $lg  = (int) round($g + (255 - $g) * 0.25);
    $lb  = (int) round($b + (255 - $b) * 0.25);

    // Lighter2: blend 50% toward white
    $l2r = (int) round($r + (255 - $r) * 0.50);
    $l2g = (int) round($g + (255 - $g) * 0.50);
    $l2b = (int) round($b + (255 - $b) * 0.50);

    // Darker: blend 20% toward black
    $dr  = (int) round($r * 0.80);
    $dg  = (int) round($g * 0.80);
    $db  = (int) round($b * 0.80);
@endphp
<style type="text/css" id="accent-styles">
    :root {
        --ac-hex:      #{{ $accentHex }};
        --ac-base:     {{ $r }}, {{ $g }}, {{ $b }};
        --ac-lighter:  {{ $lr }}, {{ $lg }}, {{ $lb }};
        --ac-lighter2: {{ $l2r }}, {{ $l2g }}, {{ $l2b }};
        --ac-darker:   {{ $dr }}, {{ $dg }}, {{ $db }};
    }

    input[type=radio], input[type=checkbox] {
        border: 1px solid var(--ac-hex);
    }

    input[type=radio]:checked, input[type=checkbox]:checked {
        background-color: var(--ac-hex);
    }

    .icon.image:hover, .icon.image:focus {
        opacity: 0.8;
    }

    .accent-bkg {
        background-color: var(--ac-hex) !important;
    }

    .accent-bkg-hover:hover {
        background-color: var(--ac-hex) !important;
    }

    .accent-bkg-darker {
        background-color: rgba(var(--ac-darker), 1) !important;
    }

    .accent-bkg-darker-hover:hover {
        background-color: rgba(var(--ac-darker), 1) !important;
    }

    .accent-fore {
        color: var(--ac-hex) !important;
    }

    .accent-fore-darker-hover:hover, .accent-fore-darker-all:hover {
        color: rgba(var(--ac-darker), 1) !important;
    }

    .accent-fore-darker-focus:focus, .accent-fore-darker-all:focus {
        color: rgba(var(--ac-darker), 1) !important;
    }

    .accent-fore-darker.selected, .accent-fore-darker-all.selected {
        color: rgba(var(--ac-darker), 1) !important;
    }

    .accent-bkg-gradient-lighter, .fra-windows .titlebar.accent-frawindows-titlebar {
        background-image: linear-gradient(to right, rgba(var(--ac-lighter2), 0.9), rgba(var(--ac-lighter2), 0.9)) !important;
    }

    .accent-bkg-gradient, .fra-windows.active .titlebar.accent-frawindows-titlebar {
        background-image: linear-gradient(to right, rgba(var(--ac-base), 0.9), rgba(var(--ac-lighter), 0.9)) !important;
    }

    .accent-bkg-gradient-hover:hover, .accent-all:hover {
        background-image: linear-gradient(to right, rgba(var(--ac-base), 0.8), rgba(var(--ac-lighter), 0.8)) !important;
    }

    .accent-bkg-gradient-focus:focus, .accent-all:focus {
        background-image: linear-gradient(to right, rgba(var(--ac-base), 0.8), rgba(var(--ac-lighter), 0.8)) !important;
    }

    .accent-bkg-gradient.selected, .accent-all.selected {
        background-image: linear-gradient(to right, rgba(var(--ac-base), 0.8), rgba(var(--ac-lighter), 0.8)) !important;
    }

    .accent-bkg-gradient-darker {
        background-image: linear-gradient(to right, rgba(var(--ac-darker), 0.8), rgba(var(--ac-lighter), 0.8)) !important;
    }

    .accent-bkg-gradient-darker-hover:hover, .accent-bkg-all-darker:hover {
        background-image: linear-gradient(to right, rgba(var(--ac-darker), 0.8), rgba(var(--ac-lighter), 0.8)) !important;
    }

    .accent-bkg-gradient-darker-focus:focus, .accent-bkg-all-darker:focus {
        background-image: linear-gradient(to right, rgba(var(--ac-darker), 0.8), rgba(var(--ac-lighter), 0.8)) !important;
    }

    .accent-bkg-gradient-darker.selected, .accent-bkg-all-darker.selected {
        background-image: linear-gradient(to right, rgba(var(--ac-darker), 0.8), rgba(var(--ac-lighter), 0.8)) !important;
    }

    /* Box shadows — style 1 */
    .accent-box-shadow-1-hover:hover, .box-shadow-1-all:hover {
        -webkit-box-shadow: 0px 0px 37px -8px rgba(var(--ac-base), 1) !important;
           -moz-box-shadow: 0px 0px 37px -8px rgba(var(--ac-base), 1) !important;
                box-shadow: 0px 0px 37px -8px rgba(var(--ac-base), 1) !important;
    }

    .accent-box-shadow-1-focus:focus, .box-shadow-1-all:focus {
        -webkit-box-shadow: 0px 0px 37px -8px rgba(var(--ac-base), 1) !important;
           -moz-box-shadow: 0px 0px 37px -8px rgba(var(--ac-base), 1) !important;
                box-shadow: 0px 0px 37px -8px rgba(var(--ac-base), 1) !important;
    }

    .accent-box-shadow-1, .box-shadow-1-all.selected {
        -webkit-box-shadow: 0px 0px 37px -8px rgba(var(--ac-base), 1) !important;
           -moz-box-shadow: 0px 0px 37px -8px rgba(var(--ac-base), 1) !important;
                box-shadow: 0px 0px 37px -8px rgba(var(--ac-base), 1) !important;
    }

    .accent-box-shadow-1-darker-hover:hover, .box-shadow-1-darker-all:hover {
        -webkit-box-shadow: 0px 0px 37px -8px rgba(var(--ac-darker), 1) !important;
           -moz-box-shadow: 0px 0px 37px -8px rgba(var(--ac-darker), 1) !important;
                box-shadow: 0px 0px 37px -8px rgba(var(--ac-darker), 1) !important;
    }

    .accent-box-shadow-1-darker-focus:focus, .box-shadow-1-darker-all:focus {
        -webkit-box-shadow: 0px 0px 37px -8px rgba(var(--ac-darker), 1) !important;
           -moz-box-shadow: 0px 0px 37px -8px rgba(var(--ac-darker), 1) !important;
                box-shadow: 0px 0px 37px -8px rgba(var(--ac-darker), 1) !important;
    }

    .accent-box-shadow-1-darker, .box-shadow-1-darker-all.selected {
        -webkit-box-shadow: 0px 0px 37px -8px rgba(var(--ac-darker), 1) !important;
           -moz-box-shadow: 0px 0px 37px -8px rgba(var(--ac-darker), 1) !important;
                box-shadow: 0px 0px 37px -8px rgba(var(--ac-darker), 1) !important;
    }

    /* Box shadows — style 2 */
    .accent-box-shadow-2-hover:hover, .box-shadow-2-all:hover {
        -webkit-box-shadow: 0px 3px 16px 0px rgba(var(--ac-base), 1) !important;
           -moz-box-shadow: 0px 3px 16px 0px rgba(var(--ac-base), 1) !important;
                box-shadow: 0px 3px 16px 0px rgba(var(--ac-base), 1) !important;
    }

    .accent-box-shadow-2-focus:focus, .box-shadow-2-all:focus {
        -webkit-box-shadow: 0px 3px 16px 0px rgba(var(--ac-base), 1) !important;
           -moz-box-shadow: 0px 3px 16px 0px rgba(var(--ac-base), 1) !important;
                box-shadow: 0px 3px 16px 0px rgba(var(--ac-base), 1) !important;
    }

    .accent-box-shadow-2, .box-shadow-2-all.selected {
        -webkit-box-shadow: 0px 3px 16px 0px rgba(var(--ac-base), 1) !important;
           -moz-box-shadow: 0px 3px 16px 0px rgba(var(--ac-base), 1) !important;
                box-shadow: 0px 3px 16px 0px rgba(var(--ac-base), 1) !important;
    }

    .white-text-hover:hover, .white-text-hover:hover * {
        color: white !important;
    }

    .black-text-hover:hover, .black-text-hover:hover * {
        color: black !important;
    }
</style>
@endauth
