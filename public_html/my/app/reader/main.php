<?php
$database = new \FrancescoSorge\PHP\Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

if ($_GET["id"] !== null) {
    $owner = null;

    require_once __DIR__ . "/../file-manager/model.php";
    require_once __DIR__ . "/../whiteboard/model.php";

    if (\FrancescoSorge\PHP\LightSchool\WhiteBoard::isFileProjecting((int)$_GET["id"], \FrancescoSorge\PHP\Cookie::get("whiteboard_code"))) {
        $owner = \FrancescoSorge\PHP\LightSchool\FileManager::getOwner((int)$_GET["id"]);
    }

    if ($owner === null) {
        if ($_GET["type"] === "contact" || (new \FrancescoSorge\PHP\LightSchool\FileManager())->checkOwnership((int)$_GET["id"])) {
            $owner = $this->getVariables("currentUser")->id;
        } else {
            require_once __DIR__ . "/../share/model.php";
            $owner = (new \FrancescoSorge\PHP\LightSchool\Share())->authorized((int)$_GET["id"]);
        }
    }

    if ($_GET["type"] === "notebook") {
        require_once CONTROLLER . "/notebook.php";
        $file = (new \FrancescoSorge\PHP\LightSchool\Notebook())->getDetails($_GET["id"], $owner, false);
        if ($file["response"] === "success") {
            $file = $file["notebook"];
        } else {
            $file = null;
        }
    } else if ($_GET["type"] === "file") {
        require_once CONTROLLER . "/file.php";
        require_once __DIR__ . "/../share/model.php";
        $file = (new \FrancescoSorge\PHP\LightSchool\File())->getDetails($_GET["id"], $owner);
        if ($file["response"] === "success") {
            $file = $file["file"];
        } else {
            $file = null;
        }
        $googleDocs = \FrancescoSorge\PHP\LightSchool\User::get(["privacy_ms_office"])["privacy_ms_office"];
        $allowOnce = $this->getVariables("allowOnce") !== null ? $this->getVariables("allowOnce") : false;
        $canPreview = in_array($file["file_type"], ["application/msword", "application/vnd.openxmlformats-officedocument.wordprocessingml.document", "application/vnd.openxmlformats-officedocument.wordprocessingml.template", "application/vnd.ms-word.document.macroEnabled.12", "application/vnd.ms-word.template.macroEnabled.12", "application/vnd.ms-excel", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "application/vnd.openxmlformats-officedocument.spreadsheetml.template", "application/vnd.ms-excel.sheet.macroEnabled.12", "application/vnd.ms-excel.template.macroEnabled.12", "application/vnd.ms-excel.addin.macroEnabled.12", "application/vnd.ms-excel.sheet.binary.macroEnabled.12", "application/vnd.ms-powerpoint", "application/vnd.openxmlformats-officedocument.presentationml.presentation", "application/vnd.openxmlformats-officedocument.presentationml.template", "application/vnd.openxmlformats-officedocument.presentationml.slideshow", "application/vnd.ms-powerpoint.addin.macroEnabled.12", "application/vnd.ms-powerpoint.presentation.macroEnabled.12", "application/vnd.ms-powerpoint.template.macroEnabled.12", "application/vnd.ms-powerpoint.slideshow.macroEnabled.12", "application/vnd.oasis.opendocument.text", "application/vnd.oasis.opendocument.spreadsheet", "application/vnd.oasis.opendocument.presentation"]);
        if (($googleDocs == 2 || $allowOnce) && $canPreview) {
            \FrancescoSorge\PHP\LightSchool\FileManager::setBypass((int)$_GET["id"]);
        }
    } else if ($_GET["type"] === "diary") {
        require_once __DIR__ . "/../diary/model.php";
        $file = (new \FrancescoSorge\PHP\LightSchool\Diary())->getDetails($_GET["id"], $owner);
        if ($file["response"] === "success") {
            if (isset($file["error"])) {
                $error = $file["error"];
            }
            $file = $file["event"];
        } else {
            $file = null;
        }
    } else if ($_GET["type"] === "contact") {
        require_once __DIR__ . "/../../app/contact/model.php";
        $file = (new \FrancescoSorge\PHP\LightSchool\Contact())->getDetails($_GET["id"], $owner);
        if ($file["response"] === "success") {
            $file = $file["contact"];
        } else {
            $file = null;
        }
    }

    if (isset($file) && $file !== null) {
        $file["type"] = (isset($file["type"]) ? $file["type"] : $_GET["type"]);

        if ($file["type"] === "contact") {
            $file['users_expanded.profile_picture'] = ($file['users_expanded.profile_picture'] === null ? CONFIG_SITE["baseURL"] . "/upload/mono/black/user.png" : $file['users_expanded.profile_picture']);
        }
    }
    if ($file["type"] === "notebook") { ?>
        <script src="<?php echo(CONFIG_SITE["baseURL"]); ?>/js/quill.min.js"></script>
    <?php } ?>
    <script type="text/javascript">
        let response = null;

        $(document).ready(function () {
            <?php if ($file !== null) { ?>
            <?php if ($file["type"] === "notebook") { ?>
            $(".menu-my.top .commands a:not('.notebook')").remove();

            const loadingWindow = new FraWindows("loading", "Lettore", "<p style='text-align: center'><span style='font-size: 1.2em'>Caricamento quaderno in corso</span><br/>Attendere prego...</p>");
            loadingWindow.setOverlay();
            loadingWindow.setTitlebarPadding(10, 0, 10, 0);
            loadingWindow.setContentPadding(20);
            loadingWindow.setSize("100%");
            loadingWindow.setProperty("max-width", "450px");
            loadingWindow.setProperty("max-height", "100vh");
            loadingWindow.setDraggable();
            loadingWindow.setPosition();
            loadingWindow.show("fadeIn", 300);

            setTimeout(() => {
                response = new FraJson(ConfigSite.baseURL + "/my/app/file-manager/controller?type=details&id=<?php echo($_GET["id"]); ?>&fields=name,type,html,n_ver,folder").getAll();

                if (response.response === "error") {
                    loadingWindow.setContent(response.text);
                } else if (response.file) {
                    loadingWindow.close();

                    $("title").text($("title").text().replace("Lettore", response.file.name));
                    $(".menu-my.top h5").html(response.file.name);
                    $(".menu-my.top .back-button").attr("href", $(".menu-my.top .back-button").attr("href") + response.file.folder);

                    if (parseInt(response.file.n_ver) === 2) {
                        const quill = new Quill('#notebook', {
                            placeholder: 'Il quaderno &egrave; vuoto',
                            modules: {
                                toolbar: false
                            },
                            readOnly: true,
                            theme: 'snow'
                        });
                        quill.setContents(JSON.parse(response.file.html));
                    } else {
                        $("#notebook").html(response.file.html);
                    }
                }
            }, 300);
            <?php } else if ($file["type"] === "file") { ?>
            $("title").text($("title").text().replace("Lettore", "<?php echo(htmlspecialchars($file["name"])); ?>"));
            $(".menu-my.top h5").html("<?php echo(htmlspecialchars($file["name"])); ?>");
            $(".menu-my.top .back-button").attr("href", $(".menu-my.top .back-button").attr("href") + "<?php echo($file["folder"]); ?>");
            $(".menu-my.top .commands a:not('.file')").remove();
            $(".menu-my.top .file.download").attr("href", $(".menu-my.top .file.download").attr("href") + "<?php echo($file["id"]); ?>");
            <?php } else if ($file["type"] === "diary") { ?>
            const diary_string = "<?php echo(htmlspecialchars($file["diary_type"]) . " di " . htmlspecialchars($file["name"]) . " il " . htmlspecialchars($this->getVariables("FraBasic")::timestampToHuman($file['diary_date'], "d/m/Y"))); ?>";
            $("title").text($("title").text().replace("Lettore", diary_string));
            $(".menu-my.top .back-button").attr("href", "../../diary");
            $(".menu-my.top h5").html(diary_string);
            $(".menu-my.top .commands a:not('.diary')").remove();
            <?php } else if ($file["type"] === "contact") { ?>
            const contact_string = "<?php echo(htmlspecialchars($file["contact.name"])); ?> <?php echo(htmlspecialchars($file["contact.surname"])); ?>";
            $("title").text($("title").text().replace("Lettore", contact_string));
            $(".menu-my.top h5").html("&nbsp;");
            $(".menu-my.top .commands a:not('.contact')").remove();
            <?php } ?>

            <?php if (isset($error)) {
            if ($error === "decryption") $error = "Errore nella fase di decriptazione";
            ?>
            const errorNotification = new FraNotifications("error", "<?php echo($error); ?>");
            errorNotification.setType("error");
            errorNotification.show();
            <?php } ?>
            <?php } else { ?>
            $(".menu-my.top .pc-md a").remove();
            <?php } ?>
        });
    </script>
    <?php
}
?>

<div class="container content-my reader"
     style="<?php echo(isset($file["file_type"]) && strpos($file["file_type"], 'pdf') !== false ? "padding-top: 0" : ""); ?>">
    <div class="main">
        <?php if ($_GET["id"] === null) { ?>
            <div style="text-align: center;">
                <div class="file">
                    <p>Per aprire un elemento, sceglilo dalla sua app.</p>
                </div>
            </div>
        <?php } else {
        if (isset($file) && $file !== null) {
        if ($file["type"] === "notebook") { ?>
            <script type="text/javascript">
                const max_pages = 100;
                let page_count = 0;

                function snipMe() {
                    page_count++;
                    if (page_count > max_pages) {
                        return;
                    }
                    let long = $(this)[0].scrollHeight - Math.ceil($(this).innerHeight());
                    const children = $(this).children().toArray();
                    for (let c in children) {
                        let test = (children[c].innerHTML).split(/<br>|<br\/>/);
                    }
                    if (children.length > 1) {
                        let removed = [];
                        while (long > 0 && children.length > 0) {
                            const child = children.pop();
                            $(child).detach();
                            removed.unshift(child);
                            long = $(this)[0].scrollHeight - Math.ceil($(this).innerHeight());
                        }
                        if (removed.length > 0) {
                            var a4 = $('<div class="A4"></div>');
                            a4.append(removed);
                            $(this).after(a4);
                            snipMe.call(a4[0]);
                        }
                    } else {
                        unsupportedNotebook();
                        $(this).css("height", "auto");
                    }
                }

                $(document).ready(function () {
                    /*$('div.A4').each(function () {
                        snipMe.call(this);
                    });*/
                });

                function unsupportedNotebook() {
                    const unsupportedWindow = new FraWindows("unsupported-notebook", "Quaderno parzialmente supportato", "Caricamento...");
                    unsupportedWindow.setTitlebarPadding(10, 0, 10, 0);
                    unsupportedWindow.setContentPadding(20);
                    unsupportedWindow.setSize("100%");
                    unsupportedWindow.setProperty("max-width", "522px");
                    unsupportedWindow.setProperty("max-height", "100vh");
                    unsupportedWindow.setControl("close");
                    unsupportedWindow.setDraggable();
                    unsupportedWindow.setOverlay();
                    unsupportedWindow.setContent("Questo quaderno non supporta la suddivisione in pagine poiché è stato creato utilizzando una versione vecchia di LightSchool Writer.<br/><br/><a href='#' class='button unsupported-not-show-again' style='float: right'>Non visualizzare pi&ugrave;</a>");
                    unsupportedWindow.setPosition();
                    unsupportedWindow.show("fadeIn", 300);
                }
            </script>
            <div class="A4" id="notebook"></div>
        <?php } else if ($file["type"] === "file") { ?>
        <?php if (file_exists(CONFIG_SITE["uploadDIR"] . "/" . $file["file_url"]) && $file["file_url"] !== null) { ?>
        <?php if (strpos($file["file_type"], 'image/') !== false) { ?>
            <div style="text-align: center">
                <div style="text-align: center; background-color: white; box-shadow: 0 0 0.5cm rgba(0,0,0,0.5); display: inline-block">
                    <img src="<?php echo(CONFIG_SITE['baseURL']); ?>/controller/provide-file.php?id=<?php echo($file["id"]); ?>"
                         style="max-width: 100%; max-height: calc(100vh - 100px)"/>
                </div>
            </div>
        <?php } else { ?>
            <div style="text-align: center;">
                <?php if (strpos($file["name"], '.txt') !== false) { ?>
                    <div class="file" style="text-align: left; max-width: 1300px">
                        <?php
                        $fh = fopen(CONFIG_SITE["uploadDIR"] . "/" . $file["file_url"], 'r');
                        while ($line = fgets($fh)) {
                            echo(htmlentities($line, ENT_IGNORE, "ISO-8859-1") . "<br/>");
                        }
                        fclose($fh);
                        ?>
                    </div>
                <?php } else if (strpos($file["file_type"], 'pdf') !== false) { ?>
                    <style type="text/css">
                        html {
                            overflow-y: hidden;
                        }

                        .container {
                            padding-right: 0;
                            padding-left: 0;
                        }

                        .content-my {
                            padding-top: 45px;
                        }
                    </style>
                    <embed allowTransparency="true"
                           src="<?php echo(CONFIG_SITE['baseURL']); ?>/my/app/reader/pdf-reader?file=../../../controller/provide-file/<?php echo(urlencode($file["id"])); ?>"
                           border="0" style="width: 100%; height: calc(100vh - 0px)"></embed>
                <?php } else if ((isset($googleDocs) && ($googleDocs == 2) && $canPreview) || ($googleDocs == 1 && $allowOnce)) { ?>
                    <style type="text/css">
                        html {
                            overflow-y: hidden;
                        }

                        .container {
                            padding-right: 0;
                            padding-left: 0;
                        }

                        .content-my {
                            padding-top: 45px;
                        }
                    </style>
                    <iframe src='https://view.officeapps.live.com/op/view.aspx?src=<?php echo(urlencode(CONFIG_SITE["baseURL"] . "/controller/provide-file/" . $_GET["id"])); ?>'
                            style="width: 100%; height: calc(100vh - 40px)" frameborder='0'></iframe>
                <?php } else {
                if (isset($googleDocs) && $googleDocs == 1 && $canPreview) { ?>
                    <script type="text/javascript">
                        let askWindow = null;

                        $(document).ready(() => {
                            const windowID = "askWindow";
                            askWindow = new FraWindows(windowID, "Autorizzazione", $(".grab .form-ask").wrap('<p/>').parent().html());
                            askWindow.setTitlebarPadding(10, 0, 10, 0);
                            askWindow.setContentPadding(20);
                            askWindow.setSize("100%");
                            askWindow.setProperty("max-width", "450px");
                            askWindow.setProperty("max-height", "100vh");
                            askWindow.setControl("close");
                            askWindow.setDraggable();
                            askWindow.setPosition();
                            askWindow.setOverlay();

                            askWindow.show("fadeIn", 300);
                        });

                        $(document).on("click", ".fra-windows .form-ask a", function (e) {
                            e.preventDefault();

                            if ($(this).hasClass("allow") === true) {
                                FraCookie.set("temp_google_documents", true);
                                location.reload();
                                askWindow.close();
                            } else {
                                askWindow.close();
                            }
                        });
                    </script>

                    <div style="display: none" class="grab">
                        <div class="form-ask">
                            <p>Questo documento potrebbe essere visibile in anteprima tramite il servizio esterno
                                Microsoft Office Online. Cliccando "Autorizza", accetti che il file venga caricato sui
                                server di Microsoft per poter essere visualizzato.</p>
                            <p class="small">Si applicano le <a
                                        href="https://www.microsoft.com/it-IT/servicesagreement/" target="_blank">condizioni</a>
                                e l'<a href="https://privacy.microsoft.com/it-it/privacystatement" target="_blank">Informativa
                                    sulla privacy</a> di Microsoft.</p>
                            <a href="#" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker deny"
                               style="float: left">Nega</a> <a href="#"
                                                               class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker allow"
                                                               style="float: right">Autorizza</a>
                        </div>
                    </div>
                <?php } ?>
                    <div class="file">
                        <p>Anteprima file non disponibile.</p>
                        <p>
                            <a href="<?php echo(CONFIG_SITE['baseURL']); ?>/controller/provide-file.php?id=<?php echo($file["id"]); ?>"
                               class="button">Scarica</a></p>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
        <?php } else { ?>
            <script type="text/javascript">
                $(document).ready(function () {
                    $(".menu-my.top .file.download").remove();
                });
            </script>
            <div style="text-align: center;">
                <div style="margin: 0 auto; text-align: center; background-color: white; color: white; background-image: linear-gradient(to right, #c91127, #ec2032); box-shadow: 0 0 0.5cm rgba(0,0,0,0.5); display: inline-block; border-radius: 10px; padding: 20px 30px">
                    <p><b>Errore fatale:</b> impossibile trovare il file sul server. Contattare il supporto
                        tecnico.</p>
                </div>
            </div>
        <?php } ?>
        <?php } else if ($file["type"] === "diary") { ?>
            <div style="text-align: center;">
                <div class="diary">
                    <h1><?php echo($file["diary_type"]); ?> di <?php echo($file["name"]); ?></h1>
                    <p style="text-align: right">
                        <b>il <?php echo($this->getVariables("FraBasic")::timestampToHuman($file['diary_date'], "d/m/Y")); ?></b>
                    </p>
                    <div style="text-align: left; word-break: break-all">
                        <?php echo($file["html"]); ?>
                    </div>
                </div>
            </div>
        <?php } else if ($file["type"] === "contact") { ?>
            <style type="text/css">
                h3 img {
                    box-shadow: none !important;
                }

                .icon {
                    width: 100%;
                    max-width: 100%;
                    text-align: left;
                }

                .icon img {
                    float: left;
                    width: 24px;
                    height: 24px;
                    margin-right: 10px;
                }
            </style>
            <div style="text-align: center; color: black">
                <div class="contact">
                    <h3><img src="<?php echo(htmlspecialchars($file['users_expanded.profile_picture'])); ?>"
                             style="width: 64px; height: 64px; border-radius: 50%; float: left; margin-right: 20px; margin-top: 5px"
                             class="profile_picture"/><?php echo(htmlspecialchars($file["contact.name"])); ?> <?php echo(htmlspecialchars($file["contact.surname"])); ?>
                    </h3>
                    <p style="word-wrap: break-word"><b>Nome
                            ufficiale:</b> <?php echo(htmlspecialchars($file['users_expanded.name'])); ?> <?php echo(htmlspecialchars($file['users_expanded.surname'])); ?>
                        &bull; <b>Nome utente:</b> <?php echo(htmlspecialchars($file['users.username'])); ?></p>
                    <br/>
                    <div class="row">
                        <div class="col-sm-6">
                            <a href="#" class="icon img-change-to-white"><img
                                        src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/black/message.png"/>Invia
                                messaggio</a>
                        </div>
                        <div class="col-sm-6">
                            <a href="#" class="icon img-change-to-white"><img
                                        src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/black/share.png"/>Condividi
                                contatto</a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <a href="#" class="icon img-change-to-white"><img
                                        src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/black/cross.png"/>Blocca
                                contatto</a>
                        </div>
                        <div class="col-sm-6">
                            <a href="#" class="icon img-change-to-white"><img
                                        src="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/trash/icon/black/icon.png"/>Elimina
                                contatto</a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <a href="#" class="icon img-change-to-white"><img
                                        src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/black/fav.png"/><?php echo($file["contact.fav"] === 0 ? "Aggiungi al" : "Rimuovi dal"); ?>
                                Desktop</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php }
        } else { ?>
            <div style="text-align: center;">
                <div style="text-align: center; background-color: white; color: white; background-image: linear-gradient(to right, #c91127, #ec2032); box-shadow: 0 0 0.5cm rgba(0,0,0,0.5); display: inline-block; border-radius: 10px; padding: 20px 30px">
                    <p><b>Errore:</b> impossibile trovare questo file.</p>
                </div>
            </div>
        <?php }
        }
        ?>
    </div>
</div>