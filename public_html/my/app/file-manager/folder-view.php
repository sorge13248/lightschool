<?php
$_GET["folder"] = (isset($_GET["folder"]) && $_GET["folder"] !== "" ? $_GET["folder"] : null);

$database = new \FrancescoSorge\PHP\Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

$owner = null;
if ($_GET["folder"] !== null && $_GET["folder"] !== "desktop") {
    require_once __DIR__ . "/../../app/file-manager/model.php";

    if ((new \FrancescoSorge\PHP\LightSchool\FileManager())->checkOwnership((int)$_GET["folder"])) {
        $owner = $this->getVariables("currentUser")->id;
    } else {
        require_once __DIR__ . "/../share/model.php";
        $owner = (new \FrancescoSorge\PHP\LightSchool\Share())->authorized((int)$_GET["folder"]);
    }

    $currentFolder = $database->query("SELECT id, name, icon, folder, trash FROM file WHERE id = :folder AND user_id = :user_id AND type = 'folder' LIMIT 1", [
        [
            "name" => "user_id",
            "value" => $owner,
            "type" => \PDO::PARAM_INT,
        ],
        [
            "name" => "folder",
            "value" => $_GET["folder"],
            "type" => \PDO::PARAM_INT,
        ],
    ], "fetchAll");

    $currentFolder = (isset($currentFolder[0]) ? $currentFolder[0] : null);

    if ($currentFolder !== null) {
        if ($currentFolder["icon"] === null) {
            $currentFolder["icon"] = CONFIG_SITE['baseURL'] . "/upload/mono/black/folder.png";
        } else {
            $currentFolder["icon"] = CONFIG_SITE['baseURL'] . "/upload/color/" . $currentFolder["icon"];
        }

        $tree = [];
        $folder = $currentFolder["folder"];
        while ($folder !== null) {
            $folder = $database->query("SELECT id, name, icon, folder FROM file WHERE id = :folder AND type = 'folder' LIMIT 1", [
                [
                    "name" => "folder",
                    "value" => $folder,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll")[0];

            if ($folder["icon"] === null) {
                $folder["icon"] = CONFIG_SITE['baseURL'] . "/upload/mono/black/folder.png";
            } else {
                $folder["icon"] = CONFIG_SITE['baseURL'] . "/upload/color/" . $folder["icon"];
            }

            array_push($tree, ["name" => $folder["name"], "id" => $folder["id"], "icon" => $folder["icon"]]);
            $folder = $folder["folder"];
        }
    }
}
?>

<?php if (isset($currentFolder) && $currentFolder !== null) { ?>
    <script type="text/javascript">
        $(document).ready(function () {
            $("title").text($("title").text().replace("File Manager", "<?php echo(htmlspecialchars($currentFolder["name"])); ?>"));
        });
    </script>
<?php } ?>

<div class="container content-my file-manager">
    <?php if ($_GET["folder"] !== "desktop") { ?>
        <?php if ((!isset($currentFolder["trash"]) || (isset($currentFolder["trash"]) && $currentFolder["trash"] !== "1")) && ($this->getVariables("currentUser")->id === $owner || $_GET["folder"] === null)) { ?>
    <link href="<?php echo(CONFIG_SITE["baseURL"]); ?>/css/uppy.min.css" rel="stylesheet">
        <script src="<?php echo(CONFIG_SITE["baseURL"]); ?>/js/uppy.min.js"></script>
        <script type="text/javascript">
            $(document).ready(() => {
                checkPastingFile();
            });

            $(document).on("click", ".action-button.add", function (e) {
                e.preventDefault();
                if (!FraWindows.windowExists("new-select")) {
                    const newFolderWindow = new FraWindows("new-select", "Nuovo elemento", "Caricamento...");
                    newFolderWindow.setTitlebarPadding(10, 0, 10, 0);
                    newFolderWindow.setContentPadding(20);
                    newFolderWindow.setSize("100%");
                    newFolderWindow.setProperty("max-width", "522px");
                    newFolderWindow.setProperty("max-height", "100vh");
                    newFolderWindow.setControl("close");
                    newFolderWindow.setDraggable();

                    const appContent = $(".grab .form-new-select").wrap('<p/>').parent().html();
                    newFolderWindow.setContent(appContent);

                    newFolderWindow.setPosition();
                    newFolderWindow.show("fadeIn", 300);
                } else {
                    FraWindows.getWindow("new-select").bringToFront();
                }
            });

            $(document).on("click", ".form-new-select .new-folder", function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (FraWindows.windowExists("new-select")) {
                    FraWindows.getWindow("new-select").close();
                }

                if (!FraWindows.windowExists("new-folder")) {
                    const newFolderWindow = new FraWindows("new-folder", "Nuova cartella", "Caricamento...");
                    newFolderWindow.setTitlebarPadding(10, 0, 10, 0);
                    newFolderWindow.setContentPadding(20);
                    newFolderWindow.setSize("100%");
                    newFolderWindow.setProperty("max-width", "522px");
                    newFolderWindow.setProperty("max-height", "100vh");
                    newFolderWindow.setControl("close");
                    newFolderWindow.setDraggable();

                    const appContent = $(".grab .form-new-folder").wrap('<p/>').parent().html();
                    newFolderWindow.setContent(appContent);

                    newFolderWindow.setPosition();
                    newFolderWindow.show("fadeIn", 300);

                    setTimeout(function () {
                        $("div.fra-windows-content form.form-new-folder #name").removeAttr("disabled").focus();
                    }, 10);
                } else {
                    FraWindows.getWindow("new-folder").bringToFront();
                }
            });


            $(document).on("submit", ".fra-windows .form-new-folder", function (e) {
                e.preventDefault();
                const form = new FraForm($(this));
                form.lock();

                const data = form.getDomElements("input", "string");
                const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), data);

                ajax.execute(function (result) {
                    if (result["response"] === "success") {
                        FraWindows.getWindow("new-folder").close();
                        const deleteNotification = new FraNotifications("new-folder", result["text"]);
                        deleteNotification.show();
                        deleteNotification.setAutoClose(2000);
                    } else {
                        $(".fra-windows .form-new-folder .response").show().html(result["text"]).addClass("alert alert-danger");
                        form.unlock();
                        $(".fra-windows form.form-new-folder #name").focus();
                    }
                });
            });

            <?php
            $diskSpace = $database->query("SELECT plan.disk_space, users.id FROM users, (SELECT disk_space FROM users_expanded, plan WHERE users_expanded.id = :user_id AND users_expanded.plan = plan.id LIMIT 1) AS plan WHERE users.id = :user_id LIMIT 1", [
                [
                    "name" => "user_id",
                    "value" => $this->getVariables("currentUser")->id,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll")[0]["disk_space"];
            ?>

            $(document).on("click", ".form-new-select .upload-file", function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (FraWindows.windowExists("new-select")) {
                    FraWindows.getWindow("new-select").close();
                }

                if (!FraWindows.windowExists("upload-file")) {
                    const newFolderWindow = new FraWindows("upload-file", "Carica file", "Caricamento...");
                    newFolderWindow.setTitlebarPadding(10, 0, 10, 0);
                    newFolderWindow.setContentPadding(20);
                    newFolderWindow.setSize("100%");
                    newFolderWindow.setProperty("max-width", "522px");
                    newFolderWindow.setProperty("max-height", "100vh");
                    newFolderWindow.setControl("close");
                    newFolderWindow.setDraggable();

                    const appContent = $(".grab .form-upload-file").wrap('<p/>').parent().html();
                    newFolderWindow.setContent(appContent);

                    const uppy = Uppy.Core({
                        restrictions: {
                            maxFileSize: 1048576 * <?php echo($diskSpace); ?>,
                            allowedFileTypes: ['.png', '.jpg', '.jpeg', '.bmp', '.gif', '.tiff', '.mp3', '.mp4', '.mov', '.wav', '.pdf', '.xps', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.accdb', '.odt', '.ods', '.odp', '.odb', '.java', '.class', '.cpp', '.h', '.js', 'html', '.htm', '.css', '.sass', '.scss', '.txt', '.rtf', '.go', '.py'],
                        },
                    })
                        .use(Uppy.Dashboard, {
                            inline: true,
                            target: '.fra-windows #drag-drop-area',
                        })
                        .use(Uppy.XHRUpload, {
                            endpoint: ConfigSite.baseURL + "/my/app/file-manager/controller?type=upload&folder=<?php echo(isset($_GET["folder"]) ? $_GET["folder"] : ""); ?>",
                            fieldName: "file"
                        });

                    uppy.on('complete', (result) => {

                    });
                    uppy.on('upload-success', (file, response) => {
                        if (response.body.response === "error") {
                            const notification = new FraNotifications("file-upload-" + FraBasic.generateGUID(), response.body.text);
                            notification.setType("error");
                            notification.show();
                        }
                    });

                    newFolderWindow.setPosition();
                    newFolderWindow.show("fadeIn", 300);
                } else {
                    FraWindows.getWindow("upload-file").bringToFront();
                }
            });

            $(document).keydown(function (event) {
                if (FraWindows.windowExists("new-select") && $('input:focus, textarea:focus, select:focus').length === 0) { // only if new-select window is opened and no input has focus
                    if (event.which === 70) { // 'f' creates a folder
                        $(".form-new-select .new-folder").click();
                        return false;
                    } else if (event.which === 79) { // 'o' creates a notebook
                        $(".form-new-select .new-notebook").click();
                        return false;
                    } else if (event.which === 85) { // 'u' uploads a file
                        $(".form-new-select .upload-file").click();
                        return false;
                    }
                }
            });

            $(document).on("click", ".fra-context-menu[fileid] .desktop", function (e) {
                e.preventDefault();
                const id = $(this).closest(".fra-context-menu[fileid]").attr("fileid");

                const ajax = new FraAjax("controller?type=fav&id=" + id, "post", "");

                ajax.execute(function (result) {
                    const notification = new FraNotifications("desktop-" + id, result["text"]);
                    notification.show();
                    notification.setAutoClose(2000);
                });
            });

            let fileManagerDraggingID = null;
            document.addEventListener("drag", function (event) {
            }, false);

            document.addEventListener("dragstart", function (event) {
                fileManagerDraggingID = event.target.getAttribute("fileid");
                event.target.style.opacity = 0.5;
            }, false);

            document.addEventListener("dragend", function (event) {
                fileManagerDraggingID = null;
                event.target.style.opacity = "";
            }, false);

            document.addEventListener("dragover", function (event) {
                event.preventDefault();
            }, false);

            document.addEventListener("drop", function (event) {
                event.preventDefault();
                const id = getItem(event);
                if (fileManagerDraggingID !== null && id !== null && fileManagerDraggingID != id) {
                    moveFile(fileManagerDraggingID, id);
                }
            }, false);

            const getItem = (event) => {
                let item = event.target;
                if (item.className.indexOf("icon") < 0) {
                    item = event.target.parentNode;
                }

                if (item.className.indexOf("folder") >= 0) {
                    return item.getAttribute("fileid");
                }
                return null;
            };
        </script>

        <div style="display: none" class="grab">
            <div class="form-new-select" style="text-align: center">
                <!--<p style="margin-top: 0; padding-top: 0">Crea un nuovo elemento</p>-->
                <a href="#" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker new-folder">Cartella</a>
                <a href="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/writer/<?php echo(isset($currentFolder) ? $currentFolder["id"] : "0"); ?>/"
                   class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker new-notebook">Quaderno</a>
                <a href="#" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker upload-file">Carica
                    file</a>
            </div>
        </div>

        <div style="display: none" class="grab">
            <form method="post"
                  action="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/file-manager/controller.php?type=create-folder&folder=<?php echo(isset($currentFolder) ? $currentFolder["id"] : ""); ?>"
                  class="form-new-folder">
                <input type="text" id="name" name="name" placeholder="Nome" style="width: calc(100% - 105px)"
                       maxlength="255" disabled/><input type="submit" value="Crea"
                                                        class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"/><br/>
                <div class="response" style="margin-top: 10px"></div>
                <p class="small">Assicurati che il nome che scegli sia univoco. Il limite massimo &egrave; di 255
                    caratteri. Non usare i seguenti caratteri: \ / : * ? " < > | &</p>
            </form>
        </div>

        <div style="display: none" class="grab">
            <form method="post"
                  action="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/file-manager/controller.php?type=upload-file&folder=<?php echo(isset($currentFolder) ? $currentFolder["id"] : ""); ?>"
                  class="form-upload-file">
                <div id="drag-drop-area"></div>
                <!--                    <input type="submit" value="Carica" class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker" /><br/>-->
                <div class="response" style="margin-top: 10px"></div>
                <p class="small">&Egrave; consentito l'upload dei seguenti file: png, jpg, jpeg, bmp, gif, tiff, mp3,
                    mp4, mov, wav, pdf, xps, doc, docx, xls, xlsx, ppt, pptx, accdb, odt, ods, odp, odb, java, class,
                    cpp, h, js, html, htm, css, sass, scss, txt, rtf, go, py. Dimensione massima per ogni
                    file: <?php echo($diskSpace); ?> MB</p>
            </form>
        </div>

        <a class="action-button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker add" href="#">
            <span>+</span>
        </a>

        <a class="action-button second accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker paste" href="#"
           style="display: none">
            <span><img src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/white/paste.png"
                       style="width: 32px; height: 32px; margin-top: -10px"/></span>
        </a>
    <?php } ?>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <?php if ($_GET["folder"] === null || $currentFolder === null) { ?>
                    <li class="breadcrumb-item active folder" aria-current="page" fileid="">Cartella Home</li>
                <?php } else if ($this->getVariables("currentUser")->id !== $owner) { ?>
                    <li class="breadcrumb-item"><a href="../share" class="accent-fore accent-fore-darker-all"
                                                   style="text-decoration: none">Condivisioni</a></li>
                    <?php foreach (array_reverse($tree) as $folder) { ?>
                        <li class="breadcrumb-item folder" fileid="<?php echo($folder["id"]); ?>"><a
                                    href="<?php echo($folder["id"]); ?>" class="accent-fore accent-fore-darker-all"
                                    style="text-decoration: none"><img
                                        src="<?php echo($folder["icon"]); ?>"/><?php echo($folder["name"]); ?></a></li>
                    <?php } ?>
                    <li class="breadcrumb-item active" aria-current="page"><img
                                src="<?php echo($currentFolder["icon"]); ?>"/><?php echo($currentFolder["name"]); ?>
                    </li>
                <?php } else { ?>
                    <?php if ($currentFolder["trash"] === "1") { ?>
                        <li class="breadcrumb-item"><a href="../trash" class="accent-fore accent-fore-darker-all"
                                                       style="text-decoration: none">Cestino</a></li>
                    <?php } else { ?>
                        <li class="breadcrumb-item folder" fileid=""><a href="."
                                                                        class="accent-fore accent-fore-darker-all"
                                                                        style="text-decoration: none"><span class="pc">Cartella&nbsp;</span>Home</a>
                        </li>
                    <?php } ?>
                    <?php foreach (array_reverse($tree) as $folder) { ?>
                        <li class="breadcrumb-item folder" fileid="<?php echo($folder["id"]); ?>"><a
                                    href="<?php echo($folder["id"]); ?>" class="accent-fore accent-fore-darker-all"
                                    style="text-decoration: none"><img
                                        src="<?php echo($folder["icon"]); ?>"/><?php echo($folder["name"]); ?></a></li>
                    <?php } ?>
                    <li class="breadcrumb-item active" aria-current="page"><img
                                src="<?php echo($currentFolder["icon"]); ?>"/><?php echo($currentFolder["name"]); ?>
                    </li>
                <?php } ?>
            </ol>
        </nav>
    <?php } ?>
    <div class="folder-view">
        <p style='color: gray' class="search-no-result">Nessun risultato cercando '<span class="searched-text"></span>'.
        </p>
        <?php
        require_once __DIR__ . "/model.php";
        $folderView = (new \FrancescoSorge\PHP\LightSchool\FileManager())->listFolder($_GET["folder"], $owner);

        if (isset($currentFolder) || $_GET["folder"] === null || $_GET["folder"] === "desktop") {
            $images = [];
            foreach ($folderView as $item) {
                if (strpos($item["file_type"], "image/") !== false && file_exists(CONFIG_SITE["uploadDIR"] . "/" . $item['file_url'])) {
                    array_push($images, $item);
                } else {
                    ?>
                    <a href="<?php echo($item['link']); ?>" draggable="true"
                       class='icon img-change-to-white accent-all box-shadow-1-all <?php echo($item["type"]); ?>'
                       fra-context-menu='file' file_type="<?php echo($item["type"]); ?>"
                       fileid="<?php echo($item["id"]); ?>" file_fav="<?php echo($item["fav"]); ?>"
                       style='display: inline-block' title="<?php echo(htmlspecialchars($item['name'])); ?>">
                        <img src="<?php echo(htmlspecialchars($item['icon'])); ?>" class="change-this-img"
                             style="float: left; <?php echo($item['style']); ?>"/>
                        <span style="display: block; font-size: 1.2em"
                              class="filename text-ellipsis"><?php echo(htmlspecialchars($item['name'])); ?></span>
                        <?php echo($item['secondRow']); ?>
                    </a>
                    <?php
                }
            }

            if (count($images) > 0 && count($images) != count($folderView)) {
                echo("<br/>");
            }
            foreach ($images as $item) {
                ?>
                <a href="<?php echo($item['link']); ?>" draggable="true"
                   class='icon img-change-to-white box-shadow-1-all <?php echo($item["type"]); ?> image'
                   fra-context-menu='file' file_type="<?php echo($item["type"]); ?>"
                   fileid="<?php echo($item["id"]); ?>" file_fav="<?php echo($item["fav"]); ?>"
                   style="display: inline-block; background-image: url('<?php echo(htmlspecialchars($item['icon'])); ?>'); background-position: center; background-size: cover; background-repeat: no-repeat"
                   title="<?php echo(htmlspecialchars($item['name'])); ?>">
                    <span style="display: block; font-size: 1.2em"
                          class="filename text-ellipsis"><?php echo(htmlspecialchars($item['name'])); ?></span>
                    <?php echo($item['secondRow']); ?>
                </a>
                <?php
            }

            if (count($folderView) === 0) {
                echo("<p style='color: gray'>Nessun file presente in questa cartella.</p>");
            }
        } else {
            echo("<p style='color: gray'>Impossibile trovare questa cartella.</p>");
        }
        ?>
    </div>
</div>