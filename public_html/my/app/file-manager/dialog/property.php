<script type="text/javascript">
    class PropertyPanel {
        static show(id, getHtml = null) {
            if (getHtml === null) getHtml = true;
            $(".property-panel .close img").attr("src", ConfigSite.baseURL + "/upload/mono/white/cross.png");

            self.id = id;
            $(".property-panel .hide-default").hide();
            $(".property-panel .ph-item").show();

            $(".content-my").css({"margin-left": 0, "margin-right": 0, "max-width": "calc(100% - 400px)"});
            $(".property-panel").attr("fileid", id).fadeIn(200, function () {
                const file = new FraJson(ConfigSite.baseURL + "/my/app/file-manager/controller?type=details&id=" + id + "&fields=type,name,icon,file_url,file_type,file_size,diary_date,diary_type,diary_reminder,create_date,last_view,last_edit" + (!getHtml ? "" : ",html")).getAll().file;
                file.icon = file.icon.replace("black", "white");

                $(".property-panel .ph-item").hide();

                if (currentApp === "reader" && file.file_type !== null && file.file_type.indexOf("image/") >= 0) {
                    $(".property-panel .structure .file-icon-col").remove();
                    $(".property-panel .structure .file-title-col").removeClass("col-md-10").addClass("col-md-12");
                } else {
                    $(".property-panel .structure .file-icon").attr("src", file.icon);
                }
                $(".property-panel .structure .file-title").text((file.type === "diary" ? file.diary_type + " di " : "") + file.name);
                $(".property-panel .structure .file-type").text(file.type === "folder" ? "Cartella" : file.type === "notebook" ? "Quaderno" : file.type === "file" ? "File" : file.type === "diary" ? "Evento diario" : file.type);
                $(".property-panel .structure .create-date").html("<b>Data di creazione:</b> " + file.create_date);
                if (file.type === "notebook") {
                    $(".property-panel .structure .notebook").show();
                    $(".property-panel .structure .notebook .last-edit").html("<b>Ultima modifica:</b> " + (file.last_edit ? file.last_edit : "Mai"));
                } else if (file.type === "diary") {
                    $(".property-panel .structure .diary").show();
                    $(".property-panel .structure .diary .date").html("<b>Data:</b> " + file.diary_date);
                    $(".property-panel .structure .diary .reminder").html("<b>Promemoria:</b> " + (file.diary_reminder ? file.diary_reminder : "Mai"));
                } else if (file.type === "file") {
                    $(".property-panel .structure .file").show();
                    $(".property-panel .structure .file .file-size").html("<b>Dimensione:</b> " + file.file_size);
                    $(".property-panel .structure .file .download").attr("href", ConfigSite.baseURL + "/controller/provide-file/" + id);

                    if (file.file_type.startsWith("image/")) {
                        $(".property-panel .structure .file .file-image").show();
                    }
                }
                if (getHtml && (file.type === "notebook" || file.type === "diary") && file.html !== null) {
                    $(".property-panel .structure .notebook-h, .property-panel .structure .diary-h").show();

                    if (typeof file.n_ver !== "undefined" && file.n_ver === "2") {
                        const quill = new Quill('#html', {
                            placeholder: 'Il quaderno &egrave; vuoto',
                            modules: {
                                toolbar: false
                            },
                            readOnly: true,
                            theme: 'snow'
                        });
                        quill.setContents(JSON.parse(file.html));
                    } else {
                        $(".property-panel .structure .html").html(file.html);
                    }
                }

                recalculateIcons();

                $(".property-panel .structure").show();
            });
        }

        static close() {
            $(".content-my").css({"margin-left": "auto", "margin-right": "auto", "max-width": "100%"});
            $(".property-panel").fadeOut(200);
        }

        static getFileID() {
            return self.id;
        }
    }

    $(document).on("click", ".fra-context-menu[fileid] .property", function () {
        fileFraContextMenu.hide();
        PropertyPanel.show($(this).closest(".fra-context-menu[fileid]").attr("fileid"));
    });

    $(document).on("click", ".property-panel .close", function (e) {
        e.preventDefault();
        PropertyPanel.close();
    });

    $(document).on("click", ".property-panel .set-profile-picture", function (e) {
        e.preventDefault();
        const ajax = new FraAjax(ConfigSite.baseURL + "/my/app/file-manager/controller?type=set-profile-picture&id=" + $(this).closest(".property-panel").attr("fileid"), "POST", "");
        const self = $(this);

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                PropertyPanel.close();
                const success = new FraNotifications("set-wallpaper-" + FraBasic.generateGUID(), result["text"]);
                success.show();
                success.setAutoClose(2000);
                $(".user-profile-picture").attr("src", ConfigSite.baseURL + "/controller/provide-file.php?id=" + self.closest(".property-panel").attr("fileid"));
            } else {
                const error = new FraNotifications("set-wallpaper-" + FraBasic.generateGUID(), result["text"]);
                error.setType("error");
                error.show();
                error.setAutoClose(2000);
            }
        });
    });
    $(document).on("click", ".property-panel .set-wallpaper", function (e) {
        e.preventDefault();
        const ajax = new FraAjax(ConfigSite.baseURL + "/my/app/file-manager/controller?type=set-wallpaper&id=" + $(this).closest(".property-panel").attr("fileid"), "POST", "");
        const self = $(this);

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                PropertyPanel.close();
                const success = new FraNotifications("set-wallpaper-" + FraBasic.generateGUID(), result["text"]);
                success.show();
                success.setAutoClose(2000);
                $(".wallpaper").css("background-image", "url('" + ConfigSite.baseURL + "/controller/provide-file.php?id=" + self.closest(".property-panel").attr("fileid") + ")");
                $("html, body, .content-my").css("background-color", "transparent");
                if ($(".wallpaper-opacity").css("background-color").length === 0) $(".wallpaper-opacity").css("background-color", "rgba(255, 255, 255, 0.5)");
            } else {
                const error = new FraNotifications("set-wallpaper-" + FraBasic.generateGUID(), result["text"]);
                error.setType("error");
                error.show();
                error.setAutoClose(2000);
            }
        });
    });
</script>
<script src="<?php echo(CONFIG_SITE["baseURL"]); ?>/js/quill.min.js"></script>

<div class="property-panel accent-bkg-gradient">
    <p class="mobile-block"><br/></p>
    <a href="#" title="Chiudi" class="close"><img src="" alt="close_button"
                                                  style="float: right; width: 16px; height: 16px"/></a>

    <div style="clear: both"></div>

    <div class="ph-item">
        <div class="ph-col-12">
            <div class="ph-row">
                <div class="ph-col-6 big"></div>
                <div class="ph-col-4 empty big"></div>
                <div class="ph-col-4"></div>
                <div class="ph-col-8 empty"></div>
                <div class="ph-col-6"></div>
                <div class="ph-col-6 empty"></div>
                <div class="ph-col-12" style="margin-bottom: 0"></div>
            </div>
        </div>
    </div>

    <div class="hide-default structure" style="display: none">
        <div class="row">
            <div class="col-md-2 file-icon-col">
                <img src="" alt="" class="file-icon"
                     style="float: left; max-width: 60px; height: auto; margin-right: 20px; margin-top: 15px"/>
            </div>
            <div class="col-md-10 file-title-col">
                <h2 class="file-title" style="margin-bottom: 0; word-break: break-all;"></h2>
                <p class="file-type" style="margin-top: 0"></p>
            </div>
            <div class="col-md-12">
                <p class="create-date"></p>
            </div>
            <div class="col-md-12 hide-default notebook">
                <p class="last-edit"></p>
            </div>
            <div class="col-md-12 hide-default diary">
                <p class="date"></p>
                <p class="reminder"></p>
            </div>
            <div class="col-md-12 hide-default file">
                <p class="file-size"></p>
                <p><a href="" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker download"
                      style="width: 100%;" download>Scarica</a><br/></p>
                <p class="hide-default file-image" style="padding-top: 10px">
                    <b>Utilizza come:</b>
                    <a href="#"
                       class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker set-profile-picture"
                       style="width: 100%;">Immagine profilo</a><br/>
                    <a href="#" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker set-wallpaper"
                       style="width: 100%;">Sfondo</a>
                </p>
            </div>
            <div class="col-md-12 hide-default notebook-h diary-h">
                <p><b>Anteprima:</b></p>
                <p class="html" id="html" style="overflow: hidden; height: 100%"></p>
            </div>
        </div>
    </div>
</div>