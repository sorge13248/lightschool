<style type="text/css">
    input[type=radio], input[type=checkbox] {
        border: 1px solid<?php echo($this->getVariables("currentUser")->accent["base"]); ?>;
    }

    input[type=radio]:checked, input[type=checkbox]:checked {
        background-color: <?php echo($this->getVariables("currentUser")->accent["base"]); ?>;
    }

    .icon.image:hover, .icon.image:focus {
        opacity: 0.8;
    }

    .accent-bkg {
        background-color: <?php echo($this->getVariables("currentUser")->accent["base"]); ?> !important;
    }

    .accent-bkg-hover:hover {
        background-color: <?php echo($this->getVariables("currentUser")->accent["base"]); ?> !important
    }

    .accent-bkg-darker {
        background-color: <?php echo($this->getVariables("currentUser")->accent["darker"]); ?> !important;
    }

    .accent-bkg-darker-hover:hover {
        background-color: <?php echo($this->getVariables("currentUser")->accent["darker"]); ?> !important;
    }

    .accent-fore {
        color: <?php echo($this->getVariables("currentUser")->accent["base"]); ?> !important;
    }

    .accent-fore-darker-hover:hover, .accent-fore-darker-all:hover {
        color: <?php echo($this->getVariables("currentUser")->accent["darker"]); ?> !important;
    }

    .accent-fore-darker-focus:focus, .accent-fore-darker-all:focus {
        color: <?php echo($this->getVariables("currentUser")->accent["darker"]); ?> !important;
    }

    .accent-fore-darker.selected, .accent-fore-darker-all.selected {
        color: <?php echo($this->getVariables("currentUser")->accent["darker"]); ?> !important;
    }

    .accent-bkg-gradient-lighter, .fra-windows .titlebar.accent-frawindows-titlebar {
        background-image: linear-gradient(to right, rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["lighter2"]))); ?>, 0.9), rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["lighter2"]))); ?>, 0.9)) !important;
    }

    .accent-bkg-gradient, .fra-windows.active .titlebar.accent-frawindows-titlebar {
        background-image: linear-gradient(to right, rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 0.9), rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["lighter"]))); ?>, 0.9)) !important;
    }

    .accent-bkg-gradient-hover:hover, .accent-all:hover {
        background-image: linear-gradient(to right, rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 0.8), rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["lighter"]))); ?>, 0.8)) !important;
    }

    .accent-bkg-gradient-focus:focus, .accent-all:focus {
        background-image: linear-gradient(to right, rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 0.8), rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["lighter"]))); ?>, 0.8)) !important;
    }

    .accent-bkg-gradient.selected, .accent-all.selected {
        background-image: linear-gradient(to right, rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 0.8), rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["lighter"]))); ?>, 0.8)) !important;
    }

    .accent-bkg-gradient-darker {
        background-image: linear-gradient(to right, rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["darker"]))); ?>, 0.8), rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["lighter"]))); ?>, 0.8)) !important;
    }

    .accent-bkg-gradient-darker-hover:hover, .accent-bkg-all-darker:hover {
        background-image: linear-gradient(to right, rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["darker"]))); ?>, 0.8), rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["lighter"]))); ?>, 0.8)) !important;
    }

    .accent-bkg-gradient-darker-focus:focus, .accent-bkg-all-darker:focus {
        background-image: linear-gradient(to right, rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["darker"]))); ?>, 0.8), rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["lighter"]))); ?>, 0.8)) !important;
    }

    .accent-bkg-gradient-darker.selected, .accent-bkg-all-darker.selected {
        background-image: linear-gradient(to right, rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["darker"]))); ?>, 0.8), rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["lighter"]))); ?>, 0.8)) !important;
    }

    /* Box shadows */
    /* 1 */
    .accent-box-shadow-1-hover:hover, .box-shadow-1-all:hover {
        -webkit-box-shadow: 0px 0px 37px -8px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 1) !important;
        -moz-box-shadow: 0px 0px 37px -8px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 1) !important;
        box-shadow: 0px 0px 37px -8px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 1) !important;
    }

    .accent-box-shadow-1-focus:focus, .box-shadow-1-all:focus {
        -webkit-box-shadow: 0px 0px 37px -8px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 1) !important;
        -moz-box-shadow: 0px 0px 37px -8px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 1) !important;
        box-shadow: 0px 0px 37px -8px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 1) !important;
    }

    .accent-box-shadow-1, .box-shadow-1-all.selected {
        -webkit-box-shadow: 0px 0px 37px -8px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 1) !important;
        -moz-box-shadow: 0px 0px 37px -8px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 1) !important;
        box-shadow: 0px 0px 37px -8px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 1) !important;
    }

    .accent-box-shadow-1-darker-hover:hover, .box-shadow-1-darker-all:hover {
        -webkit-box-shadow: 0px 0px 37px -8px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["darker"]))); ?>, 1) !important;
        -moz-box-shadow: 0px 0px 37px -8px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["darker"]))); ?>, 1) !important;
        box-shadow: 0px 0px 37px -8px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["darker"]))); ?>, 1) !important;
    }

    .accent-box-shadow-1-darker-focus:focus, .box-shadow-1-darker-all:focus {
        -webkit-box-shadow: 0px 0px 37px -8px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["darker"]))); ?>, 1) !important;
        -moz-box-shadow: 0px 0px 37px -8px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["darker"]))); ?>, 1) !important;
        box-shadow: 0px 0px 37px -8px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["darker"]))); ?>, 1) !important;
    }

    .accent-box-shadow-1-darker, .box-shadow-1-darker-all.selected {
        -webkit-box-shadow: 0px 0px 37px -8px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["darker"]))); ?>, 1) !important;
        -moz-box-shadow: 0px 0px 37px -8px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["darker"]))); ?>, 1) !important;
        box-shadow: 0px 0px 37px -8px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["darker"]))); ?>, 1) !important;
    }

    /* 2 */
    .accent-box-shadow-2-hover:hover, .box-shadow-2-all:hover {
        -webkit-box-shadow: 0px 3px 16px 0px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 1) !important;
        -moz-box-shadow: 0px 3px 16px 0px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 1) !important;
        box-shadow: 0px 3px 16px 0px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 1) !important;
    }

    .accent-box-shadow-2-focus:focus, .box-shadow-2-all:focus {
        -webkit-box-shadow: 0px 3px 16px 0px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 1) !important;
        -moz-box-shadow: 0px 3px 16px 0px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 1) !important;
        box-shadow: 0px 3px 16px 0px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 1) !important;
    }

    .accent-box-shadow-2, .box-shadow-2-all.selected {
        -webkit-box-shadow: 0px 3px 16px 0px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 1) !important;
        -moz-box-shadow: 0px 3px 16px 0px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 1) !important;
        box-shadow: 0px 3px 16px 0px rgba(<?php echo(implode(", ", $this->getVariables("FraBasic")::hexToRgb($this->getVariables("currentUser")->accent["base"]))); ?>, 1) !important;
    }

    .white-text-hover:hover, .white-text-hover:hover * {
        color: white !important;
    }

    .black-text-hover:hover, .black-text-hover:hover * {
        color: black !important;
    }
</style>