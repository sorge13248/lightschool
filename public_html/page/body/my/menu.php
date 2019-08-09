<?php
$database = new \FrancescoSorge\PHP\Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));
$database->setTableDotField();
?>
<script type="text/javascript">
    $(document).on("mouseover focus", ".search-box", function () {
        $(this).css("opacity", "1");
    });

    $(document).on("mouseleave focusout", ".search-box", function () {
        if (!$(this).is(":focus")) {
            $(this).css("opacity", "0.5");
        }
    });

    class ApplicationLauncher {
        static buttonClicked() {
            if (!$(".application-launcher").is(":visible")) {
                $(".application-launcher").fadeIn(200).find("input").attr("disabled", "disabled");
                $("html").css("overflow-y", "hidden");
                if ($(window).outerHeight() > 500) {
                    setTimeout(function () {
                        $(".application-launcher input").focus().removeAttr("disabled").focus();
                    }, 100);
                }
            } else {
                $(".application-launcher").fadeOut(200);
                $("html").css("overflow-y", "auto");
            }
        }

        static isOpen() {
            return $(".application-launcher").is(":visible");
        }
    }

    $(document).on("input", ".search-box", function () {
        $(".icon, .event, .list, .chat-bubble").show();
        $(".search-no-result").hide();

        if ($(this).val().length > 0) {
            if (ApplicationLauncher.isOpen()) {
                $(".application-launcher .quick-links").hide();
                $(".application-launcher .col-md-10").hide();
                $(".application-launcher").addClass("search-mode");
                $(".application-launcher .col-md-2 a").hide();
                $(".application-launcher .col-md-2").removeClass("col-md-2").addClass("col-md-12");
            }

            const self = $(this);
            $(".icon, .event, .list, .chat-bubble").each(function () {
                if (!$(this).find("span").text().toLowerCase().includes(self.val().toLowerCase()) && !$(this).text().toLowerCase().includes(self.val().toLowerCase())) {
                    $(this).hide();
                }
            });

            if ($(".icon:visible, .event:visible, .list:visible, chat-bubble:visible").length == 0) {
                $(".search-no-result").show().find(".searched-text").text(self.val());
            }
        } else {
            if (ApplicationLauncher.isOpen()) {
                $(".application-launcher .col-md-12 a").show();
                $(".application-launcher").removeClass("search-mode");
                $(".application-launcher .col-md-12").removeClass("col-md-12").addClass("col-md-2");
                $(".application-launcher .col-md-10").show();
                $(".application-launcher .quick-links").show();
            }
        }
    });

    $(document).on("click", ".application-launcher-button", function (e) {
        e.preventDefault();
        ApplicationLauncher.buttonClicked();
    });

    $(document).keydown(function (event) {
        if (event.which === 83 && $('input:focus, textarea:focus, select:focus').length === 0) { // 's' cancel selections only if not inside any input
            ApplicationLauncher.buttonClicked();
        }
    });

    $(document).ready(function () {
        $(".menu-my.bottom a[app-name='" + currentApp + "']").addClass("selected");
        $(".application-launcher a[app-name='" + currentApp + "']").addClass("selected");
        $(".menu-my.bottom").css("min-width", $(".menu-my.bottom").outerWidth());
    });
</script>

<?php
$taskbar_size = (int)$this->getVariables("currentUser")->taskbar_size;
$taskbar_class = "";

switch ($taskbar_size) {
    case 2:
        $taskbar_class = "big";
        break;
    case 1:
        $taskbar_class = "small";
        break;
}
?>

<div class="menu-my top fra-windows-margin-top mobile no-print accent-bkg-gradient accent-box-shadow-2">
    <a href="#" class='application-launcher-button mobile' style="float: left; margin-top: 15px; margin-right: 10px;"><img
                src="<?php echo(CONFIG_SITE['baseURL']); ?>/upload/mono/white/menu.png"
                style='margin-top: -5px'></a>
    <h5 style="font-weight: bold; display: inline-block; width: calc(100% - 40px); margin-top: 9px; margin-bottom: 0"
        class="pageTitle text-ellipsis"><?php echo($this->getVariables("pageTitle")); ?></h5>
</div>
<div class="menu-my bottom pc no-print accent-bkg-gradient accent-box-shadow-1 <?php echo($taskbar_class); ?>">
    <a href="#" class='application-launcher-button accent-all'><img
                src="<?php echo($this->getVariables("currentUser")->profile_picture["url"]); ?>"
                class="user-profile-picture" style='border-radius: 50%'></a>
    <?php
    if (is_array($this->getVariables("currentUser")->taskbar["interpreted"])) {
        foreach ($this->getVariables("currentUser")->taskbar["interpreted"] as $app) {
            echo("<a href=\"{$app["link"]}\" class='accent-all' title='" . htmlentities($app["name"]) . "' app-name='{$app["unique-name"]}'><img src=\"{$app["icon"]}\"></a>");
        }
    }
    ?>
</div>

<div class="menu-touch-handler mobile-md"
     style="display: none !important;"></div> <!-- Touch Handler Menu disabilitato temporaneamente -->

<div class="application-launcher accent-bkg-gradient">
    <div class="row">
        <div class="col-md-10 order-last order-md-first" style="text-align: center">
            <div class="apps">
                <?php
                $allApps = $database->query("SELECT app_catalog.unique_name, app_catalog.name_it AS name FROM app_catalog, app_purchase WHERE app_purchase.user = :user_id AND app_purchase.application_launcher = 1 AND app_purchase.app = app_catalog.unique_name ORDER BY name", [
                    [
                        "name" => "user_id",
                        "value" => $this->getVariables("fraUserManagement")->getCurrentUserInfo(["id"], ["users"])->id,
                        "type" => \PDO::PARAM_INT,
                    ],
                ], "fetchAll");

                foreach ($allApps as $key => $app) {
                    echo("<a href=\"" . CONFIG_SITE['baseURL'] . "/my/app/{$app['app_catalog.unique_name']}\" class='icon app accent-all box-shadow-1-darker-all text-ellipsis' app-name='{$app['app_catalog.unique_name']}' title='{$app['app_catalog.name']}'><img src=\"" . CONFIG_SITE['baseURL'] . "/my/app/{$app['app_catalog.unique_name']}/icon/white/icon.png\">{$app['app_catalog.name']}</a>");
                }

                if (count($allApps) === 0) {
                    echo("<p class='alert alert-warning'>Nessuna app aggiunta al tuo account. Passa a <a href='" . CONFIG_SITE['baseURL'] . "/my/app/store'>LightStore</a> per aggiungerne una.</p>");
                }
                ?>
            </div>
        </div>
        <div class="col-md-2 header">
            <div style="width: calc(100% - 20px); padding-left: 10px">
                <input type="text" id="search" name="search" placeholder="Cerca..."
                       class="small search-box box-shadow-1-all"
                       style="padding: 5px 10px; font-size: 11pt; border: none !important; opacity: .5; width: 100%"/>
                <div class="quick-links">
                    <a href="<?php echo(CONFIG_SITE['baseURL']); ?>/my/"
                       style="font-size: 1.2em; padding: 10px"
                       class="list accent-all box-shadow-1-darker-all" title="<?php echo(htmlspecialchars($this->getVariables("currentUser")->name)); ?> <?php echo(htmlspecialchars($this->getVariables("currentUser")->surname)); ?>"><img
                                src="<?php echo($this->getVariables("currentUser")->profile_picture["url"]); ?>"
                                class="user-profile-picture"
                                style='width: 32px; height: 32px; border-radius: 50%'>
                    </a>
                    <a href="<?php echo(CONFIG_SITE['baseURL']); ?>/my/app/store/"
                       style="font-size: 1.2em; padding: 10px"
                       class="list accent-all box-shadow-1-darker-all" title="LightStore"><img
                                src="<?php echo(CONFIG_SITE['baseURL']); ?>/my/app/store/icon/white/icon.png"
                                style='width: 32px'></a>
                    <a href="<?php echo(CONFIG_SITE['baseURL']); ?>/my/app/settings/"
                       style="font-size: 1.2em; padding: 10px"
                       class="list accent-all box-shadow-1-darker-all" title="Impostazioni"><img
                                src="<?php echo(CONFIG_SITE['baseURL']); ?>/my/app/settings/icon/white/icon.png"
                                style='width: 32px'></a>
                    <a href="<?php echo(CONFIG_SITE['baseURL']); ?>/controller/logout"
                       style="font-size: 1.2em; padding: 10px"
                       class="list accent-all box-shadow-1-darker-all" title="Disconnetti"><img
                                src="<?php echo(CONFIG_SITE['baseURL']); ?>/upload/mono/white/logout.png"
                                style='width: 32px'></a>
                </div>
            </div>
        </div>
    </div>

</div>