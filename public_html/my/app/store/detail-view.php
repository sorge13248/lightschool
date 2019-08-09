<?php
$database->setTableDotField(false);
$app = $database->query(["SELECT unique_name, version, category, icon, system, preview, IF(name_it IS NOT NULL, name_it, name_en) AS name, IF(detail_it IS NOT NULL, detail_it, detail_en) AS detail FROM app_catalog WHERE unique_name = :name LIMIT 1", "SELECT timestamp FROM app_purchase WHERE user = :user AND app = :app LIMIT 1"], [
    [
        [
            "name" => "name",
            "value" => $_GET["name"],
            "type" => \PDO::PARAM_STR,
        ],
    ],
    [
        [
            "name" => "user",
            "value" => $this->getVariables("currentUser")->id,
            "type" => \PDO::PARAM_INT,
        ],
        [
            "name" => "app",
            "value" => $_GET["name"],
            "type" => \PDO::PARAM_STR,
        ],
    ],
], "fetchAll");

$exists = isset($app[0]);
$acquired = isset($app[1][0]);

if ($exists) { ?>
    <style type="text/css">
        .image-gallery {
            width: 100%;
            padding: 20px;
            overflow-x: auto;
            overflow-y: hidden;
            white-space: nowrap;
        }

        .image-gallery img {
            display: inline-block;
            margin: 0 10px;
            width: 256px;
            height: 128px;
        }
    </style>

    <script type="text/javascript">
        $(document).on("click", ".image-gallery > img", function (e) {
            e.preventDefault();
            const windowID = "screen-" + $(this).attr("key");

            if (!FraWindows.windowExists(windowID)) {
                const screen = new FraWindows(windowID, "Screenshot", "Caricamento...");
                screen.setTitlebarPadding(10, 0, 10, 0);
                screen.setContentPadding(20);
                screen.setSize("100%");
                screen.setControl("close");
                screen.setProperty("width", "80%");
                screen.setProperty("height", "80vh");
                screen.setDraggable();
                screen.setContentPadding();
                const appContent = $(".grab .screen-window").wrap('<p/>').parent().html();
                screen.setContent(appContent);
                const current = $(".fra-windows[fra-window-id='fra-windows-" + windowID + "']");
                current.find(".screen").css("background-image", "url(\"" + $(this).attr("src") + "\")");
                screen.setPosition();
                screen.show("fadeIn", 300);
            } else {
                FraWindows.getWindow(windowID).bringToFront();
            }
        });

        <?php if(($app[0][0]["category"] !== "themes" && !$acquired && !$app[0][0]["system"]) || $app[0][0]["category"] === "themes") { ?>
        $(document).on("submit", ".get-app", function (e) {
            e.preventDefault();

            const form = new FraForm($(this));
            form.lock();

            const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), "app=<?php echo($_GET["name"]); ?>");

            ajax.execute(function (result) {
                const notification = new FraNotifications(FraBasic.generateGUID(), result["text"]);
                notification.setAutoClose(2000);

                if (result["response"] === "success") {
                    notification.show();
                    location.reload();
                } else {
                    notification.setType("error");
                    notification.show();
                    form.unlock();
                }
            });
        });
        <?php } ?>
    </script>

    <div style="display: none" class="grab">
        <div class="screen-window">
            <div class="screen"
                 style="background-size: contain; background-repeat: no-repeat; background-position: center; width: 100%; height: 80vh"></div>
        </div>
    </div>

<?php } ?>

<div class="container content-my store category">
    <div style="margin: 0 auto; width: 100%; max-width: 1200px">
        <?php if ($exists) { ?>
            <?php if ($app[0][0]["icon"]) { ?>
                <img src="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/<?php echo($_GET["name"]); ?>/icon/black/icon.png"
                     style="float: left; width: 64px; height: 64px; margin-right: 20px; margin-top: 15px"/>
            <?php } ?>
            <h1><?php echo(htmlspecialchars($app[0][0]['name'])); ?></h1>
            <p style="margin-top: 0; padding-top: 0">Versione <?php echo($app[0][0]["version"]); ?></p>
            <div style="clear: both; margin-bottom: 20px"></div>
            <?php if ($app[0][0]["preview"]) { ?>
                <div class="alert alert-warning">
                    <p>Quest'app &egrave; una versione sperimentale. Potrebbero verificarsi errori, perdita di dati,
                        malfunzionamenti e violazioni della sicurezza. Questi problemi non sono limitati all'app ma
                        potrebbero estendersi alle altre app e dati del tuo account.</p>
                    <p><b>Non ci assumiamo nessuna responsabilit&agrave;.</b></p>
                </div>
            <?php } ?>
            <?php if ($app[0][0]["system"] && $app[0][0]["category"] !== "themes") { ?>
                <div class="alert alert-success">
                    <p>Quest'app &egrave; inclusa di base in LightSchool. Puoi iniziare subito ad usarla senza doverla
                        prima ottenere.</p>
                </div>
            <?php } ?>
            <?php if ($app[0][0]["category"] !== "themes" && ($acquired || $app[0][0]["system"])) { ?>
                <a href="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/<?php echo($_GET["name"]); ?>/"
                   class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker">Apri</a>
                <?php if (!$app[0][0]["system"]) { ?>
                    <span>App acquisita il <?php echo(\FrancescoSorge\PHP\Basic::timestampToHuman($app[1][0]["timestamp"])); ?></span>
                <?php } ?>
            <?php } else if ($app[0][0]["category"] === "themes") { ?>
                <form method="post" action="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/store/controller?type=theme"
                      class="get-app">
                    <input type="submit" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                           value="Applica"/>
                </form>
            <?php } else { ?>
                <form method="post" action="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/store/controller?type=get"
                      class="get-app">
                    <input type="submit" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                           value="Ottieni"/>
                </form>
            <?php } ?>
            <br/>
            <?php
            $path = __DIR__ . "/../../../upload/store/{$app[0][0]["unique_name"]}/";
            if (file_exists($path)) {
                ?>
                <div class="image-gallery">
                    <?php
                    foreach (glob($path . '*.png') as $key => $filename) {
                        ?>
                        <img src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/store/<?php echo($app[0][0]["unique_name"]); ?>/<?php echo(basename($filename)); ?>"
                             key="<?php echo($key); ?>"/>
                        <?php
                    }
                    ?>
                </div>
                <?php
            } else {
                echo("<br/>");
            }
            ?>
            <?php echo($app[0][0]['detail'] !== null ? $app[0][0]['detail'] : "<p><i>Nessuna descrizione fornita per l'app</i></p>"); ?>
        <?php } else { ?>
            <p class="alert alert-danger">App non trovata.</p>
        <?php } ?>
    </div>
</div>