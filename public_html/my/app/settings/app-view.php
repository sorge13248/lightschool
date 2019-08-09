<script type="text/javascript">
    $(document).click(function () {
        if ($(this).hasClass("selected")) {
            $(".selected").removeClass("selected");
        }
    });

    $(document).on("click", ".settings-app .icon", function (e) {
        e.preventDefault();

        if (!ctrlPressed) {
            if (!FraWindows.windowExists($(this).attr("app_id"))) {
                const appWindow = new FraWindows($(this).attr("app_id"), $(this).attr("app_name"), "Loading...");
                appWindow.setTitlebarPadding(10, 0, 10, 0);
                appWindow.setContentPadding(20);
                appWindow.setSize("100%");
                appWindow.setProperty("max-width", (Math.random() * (600 - 500) + 500) + "px");
                appWindow.setProperty("max-height", "100vh");
                appWindow.setControl("close");
                appWindow.setDraggable();
                appWindow.attr("app_id", $(this).attr("app_id"));

                const appContent = $(".grab .app-detail").wrap('<p/>').parent().html();
                appWindow.setContent(appContent);

                const current = $(".fra-windows[fra-window-id='fra-windows-" + $(this).attr("app_id") + "'] .app-detail");
                current.find(".app_id").text($(this).attr("app_id"));
                current.find(".image").attr("src", $(this).find("img").attr("original-src"));
                current.find(".app_name").text($(this).attr("app_name"));
                current.find(".acquired").text($(this).attr("app_acquired"));
                current.find(".open_on_lightstore").attr("href", current.find(".open_on_lightstore").attr("href") + appWindow.attr("app_id") + "/");

                appWindow.setPosition();
                appWindow.show("fadeIn");
            } else {
                FraWindows.getWindow($(this).attr("app_id")).bringToFront();
            }
        }
    });

    $(document).on("click", ".app-to-taskbar", function (e) {
        e.preventDefault();
        const response = (new FraJson(ConfigSite.baseURL + "/my/app/settings/controller?type=app-to-taskbar&app=" + $(this).closest(".fra-windows").attr("app_id"))).getAll();
        const notification = new FraNotifications(FraBasic.generateGUID(), response.text);
        notification.setAutoClose(2000);
        if (response.response === "success") {

        } else {
            notification.setType("error");
        }
        notification.show();
    });

    $(document).on("click", ".erase-app-data", function (e) {
        e.preventDefault();
        const response = (new FraJson(ConfigSite.baseURL + "/my/app/settings/controller?type=erase-app-data&app=" + $(this).closest(".fra-windows").attr("app_id"))).getAll();
        const notification = new FraNotifications(FraBasic.generateGUID(), response.text);
        notification.setAutoClose(2000);
        if (response.response === "success") {

        } else {
            notification.setType("error");
        }
        notification.show();
    });
</script>

<style type="text/css">
    .app-detail .icon {
        width: 100%;
        max-width: 100%;
        text-align: left;
    }

    .app-detail .icon img {
        float: left;
        width: 24px;
        height: 24px;
        margin-right: 10px;
    }
</style>

<div class="container content-my settings-app">
    <div style="display: none" class="grab">
        <div class="app-detail">
            <span class="app_id" style="display: none"></span>
            <h3><img src="" style="width: 32px; height: 32px; float: left; margin-right: 20px; margin-top: 5px"
                     class="image"/><span class="app_name"></span></h3>
            <p style="word-wrap: break-word"><b>Acquisita il</b> <span class="acquired"></span></p>
            <div style="clear: both"></div>
            <br/>
            <div class="row">
                <!--<div class="col-sm-6">
                    <a href="#" class="icon img-change-to-white add_remove_from_application_launcher accent-all box-shadow-1-all"><img src="<?php /*echo(CONFIG_SITE["baseURL"]); */ ?>/upload/mono/black/plus.png" class="change-this-img" />Aggiungi dal menu di avvio</a>
                </div>-->
                <div class="col-md-12">
                    <a href="#"
                       class="icon img-change-to-white add_remove_from_taskbar accent-all box-shadow-1-all app-to-taskbar"><img
                                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/black/plus.png"
                                class="change-this-img"/>Aggiungi/Rimuovi dalla taskbar</a>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <a href="#"
                       class="icon img-change-to-white erase_data accent-all box-shadow-1-all erase-app-data"><img
                                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/black/cross.png"
                                class="change-this-img"/>Cancella dati</a>
                </div>
                <div class="col-md-6">
                    <a href="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/store/d/"
                       class="icon img-change-to-white open_on_lightstore accent-all box-shadow-1-all"><img
                                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/store/icon/black/icon.png"
                                class="change-this-img"/>Apri su LightStore</a>
                </div>
            </div>
        </div>
    </div>

    <?php
    $allApps = $database->query("SELECT app_purchase.app, app_purchase.timestamp, app_catalog.name_it, app_catalog.unique_name FROM app_purchase, app_catalog WHERE app_purchase.user = :user_id AND app_purchase.app = app_catalog.unique_name ORDER BY app_catalog.name_it", [
        [
            "name" => "user_id",
            "value" => $this->getVariables("fraUserManagement")->getCurrentUserInfo(["id"], ["users"])->id,
            "type" => \PDO::PARAM_INT,
        ],
    ], "fetchAll");

    ?>
    <div class="row search-no-result" style="padding: 10px 20px;">
        <div class="col-md-12">
            <p style='color: gray'>Nessun risultato cercando '<span class="searched-text"></span>'.</p>
        </div>
    </div>
    <?php
    if (count($allApps) === 0) {
        echo("<p class='alert alert-warning'>Nessuna app aggiunta al tuo account. Passa a <a href='" . CONFIG_SITE['baseURL'] . "/my/app/store'>LightStore</a> per aggiungerne una.</p>");
    }

    foreach ($allApps as $key => $app) {
        ?>
        <a href="" class="icon img-change-to-white selectable accent-all box-shadow-1-all"
           app_id="<?php echo(htmlspecialchars($app["app_catalog.unique_name"])); ?>"
           app_name="<?php echo(htmlspecialchars($app["app_catalog.name_it"])); ?>"
           app_acquired="<?php echo($this->getVariables("FraBasic")::timestampToHuman($app["app_purchase.timestamp"])); ?>">
            <img original-src="<?php echo(CONFIG_SITE['baseURL']); ?>/my/app/<?php echo($app['app_catalog.unique_name']); ?>/icon/black/icon.png"
                 src="<?php echo(CONFIG_SITE['baseURL']); ?>/my/app/<?php echo($app['app_catalog.unique_name']); ?>/icon/black/icon.png"
                 class="change-this-img" style="width: 16px; height: 16px; margin-right: 10px"/>
            <?php echo($app["app_catalog.name_it"]); ?>
        </a>
        <?php
    }
    ?>
</div>