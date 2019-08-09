<title><?php echo(strip_tags($this->getVariables("pageTitle"))); ?> - <?php echo(CONFIG_SITE["title"]); ?></title>
<link rel="stylesheet" href="<?php echo(CONFIG_SITE["baseURL"] . "/css/lightschool-my.css"); ?>"/>

<?php require_once __DIR__ . "/accent.php"; ?>

<?php
if ((!isset($_GET["min"]) || $_GET["min"] !== 1) && $this->getVariables("currentUser")->theme !== null && file_exists(__DIR__ . "/../../../css/theme/" . urlencode($this->getVariables("currentUser")->theme["unique_name"]) . ".css")) {
    ?>
    <link rel="stylesheet"
          href="<?php echo(CONFIG_SITE["baseURL"]); ?>/css/theme/<?php echo(urlencode($this->getVariables("currentUser")->theme["unique_name"])); ?>.css"/>
    <?php
}
?>

<style type="text/css">
    .wallpaper {
        background-size: cover;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        z-index: -2;
    }

    .wallpaper-opacity {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        z-index: -1;
    }
</style>
<?php if ($this->getVariables("currentUser")->wallpaper !== null) { ?>
    <style type="text/css" id="background-styles">
        html, body, .content-my {
            background-color: transparent;
        }

        .wallpaper {
            background-image: url("<?php echo(CONFIG_SITE["baseURL"]); ?>/controller/provide-file.php?id=<?php echo($this->getVariables("currentUser")->wallpaper["id"]); ?>");
        }

        .wallpaper-opacity {
            background-color: rgba(<?php echo($this->getVariables("currentUser")->wallpaper["color"]); ?>, <?php echo($this->getVariables("currentUser")->wallpaper["opacity"]); ?>);
        }
    </style>
<?php } ?>

<?php if (!isset($hideWallpaper) || !$hideWallpaper) { ?>
    <div class="wallpaper"></div>
    <div class="wallpaper-opacity"></div>
<?php } ?>

<?php require_once __DIR__ . "/school-emergency.php"; ?>

<script type="text/javascript">
    const currentApp = window.location.href.split("/app/")[1] ? window.location.href.split("/app/")[1].split('/')[0] : "index";
    let ctrlPressed = false;

    const base64Encode = (str) => {
        // first we use encodeURIComponent to get percent-encoded UTF-8,
        // then we convert the percent encodings into raw bytes which
        // can be fed into btoa.
        return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g,
            function toSolidBytes(match, p1) {
                return String.fromCharCode('0x' + p1);
            }));
    };

    const timestampToHuman = (date, format = null) => {
        if (format === null) format = "d/m/Y H:i";
        // Convert 'yyyy-mm-dd hh:mm:ss' to 'mm/dd/yyyy hh:mm:ss'
        if (format === "d/m/Y H:i") {
            date = date.substring(0, 16);
            return date.replace(/^(\d{4})-(\d{2})-(\d{2})/, '$3/$2/$1');
        } else if (format === "d/m/Y H:i:s") {
            return date.replace(/^(\d{4})-(\d{2})-(\d{2})/, '$3/$2/$1');
        } else if (format === "d/m/Y") {
            date = date.substring(0, 10);
            return date.replace(/^(\d{4})-(\d{2})-(\d{2})/, '$3/$2/$1');
        }
    };

    const cancelSelection = () => {
        $(".selected").blur().removeClass("selected").find("img").attr("src").replace("/white/", "/black/");
    };

    const recalculateIcons = () => {
        <?php
        if (isset($this->getVariables("currentUser")->theme["icon"]) && $this->getVariables("currentUser")->theme["icon"] === "white") {
        ?>
        $("img").each(function () {
            if (!$(this).hasClass("keep-black") && $(this).is("[src]")) {
                $(this).attr("src", $(this).attr("src").replace("/black/", "/white/"));
                if ($(this).attr("original-src")) {
                    $(this).attr("original-src", $(this).attr("original-src").replace("/black/", "/white/"));
                }
            }
        });
        <?php } ?>
    };

    $(document).on("click", "*[disabled]", function (e) {
        e.preventDefault();
    });

    $(document).keydown(function (event) {
        if (event.which === 17) {
            ctrlPressed = true;
        } else if (event.which === 67 && $('input:focus, textarea:focus, select:focus').length === 0) { // 'c' cancel selections only if not inside any input
            cancelSelection();
        } else if (event.which === 13 && $('input:focus, textarea:focus, select:focus, *[contenteditable]:focus').length === 0) { // 'enter' opens selections only if not inside any input
            $(".content-my .selected").each(function () {
                $(this).removeClass("selected").trigger("click");
            });
            $("*:focus").blur();
        } else if (event.which === 27) { // 'esc'
            if ($(".application-launcher").is(":visible")) { // closes application launcher
                ApplicationLauncher.buttonClicked();
            } else if ($('.property-panel').is(":visible")) { // closes property panel
                if (typeof PropertyPanel !== "undefined") PropertyPanel.close();
            } else if ($('input:focus, textarea:focus, select:focus').length !== 0) { // blurs focused field
                $("input, textarea, select").blur();
            }
        } else if (event.which === 78 && $('input:focus, textarea:focus, select:focus').length === 0 && (typeof currentApp === "undefined" || currentApp !== "writer")) { // 'n' activates action-button action only if not inside any input
            $(".action-button.add").click();
            return false;
        }
    });

    $(document).click(function (e) {
        if ($(e.target).closest('.selected').length === 0) {
            //cancelSelection();
        }
    });

    $(document).keyup(function () {
        ctrlPressed = false;
    });

    $(document).on("click", ".selectable", function (e) {
        e.preventDefault();
        if (ctrlPressed === false) {
            $(".content-my .icon").removeClass("selected");
        }
        $(this).addClass("selected");
    });

    <?php
    if ($this->getVariables("currentUser")->theme === null || (isset($this->getVariables("currentUser")->theme["icon"]) && $this->getVariables("currentUser")->theme["icon"] === "black")) {
    ?>
    $(document).on("mouseenter focus", ".img-change-to-white", function () {
        $(this).find("img").each(function () {
            if ($(this).hasClass("change-this-img") && $(this).attr("src").indexOf("/black/") >= 0) {
                $(this).attr("src", $(this).attr("src").replace("/black/", "/white/"));
            }
        });
    });

    $(document).on("mouseleave focusout", ".img-change-to-white", function () {
        $(this).find("img").each(function () {
            if ($(this).hasClass("change-this-img") && $(this).attr("src").indexOf("/white/") >= 0 && !$(this).parent().is(":focus") && !$(this).parent().hasClass("selected")) {
                $(this).attr("src", $(this).attr("src").replace("/white/", "/black/"));
            }
        });
    });

    $(document).click(function () {
        $(".img-change-to-white img").each(function () {
            if ($(this).hasClass("change-this-img") && $(this).attr("src").indexOf("/white/") >= 0 && !$(this).parent().hasClass("selected")) {
                $(this).attr("src", $(this).attr("src").replace("/white/", "/black/"));
            }
        });
    });

    <?php } else if (isset($this->getVariables("currentUser")->theme["icon"]) && $this->getVariables("currentUser")->theme["icon"] === "white") { ?>
    $(document).ready(function () {
        recalculateIcons();
    });
    <?php } ?>
</script>
