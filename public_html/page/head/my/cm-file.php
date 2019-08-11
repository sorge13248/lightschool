<script type="text/javascript">
    let fileFraContextMenu;

    document.addEventListener("file-cm-open-event", function (e) {
        fileFraContextMenu = new FraContextMenu("file");
        const fileID = e.detail.attributes.fileid.nodeValue;

        fileFraContextMenu.attr("fileID", fileID);

        $(".fra-context-menu[fra-context-menu='file'] a").css("display", "");

        if (e.detail.attributes.getNamedItem("file-in-trash")) {
            $(".fra-context-menu[fra-context-menu='file'] a:not(.trash)").hide();
        } else if (e.detail.attributes.getNamedItem("file_type")) {
            $(".fra-context-menu[fra-context-menu='file'] a:not(." + e.detail.attributes.file_type.nodeValue + ")").hide();
        }

        if (e.detail.attributes.getNamedItem("file_fav")) {
            if (parseInt(e.detail.attributes.getNamedItem("file_fav").value) === 1) {
                $(".fra-context-menu[fra-context-menu='file'] .fav img").attr("src", $(".fra-context-menu[fra-context-menu='file'] .fav img").attr("src").replace("fav.png", "fav_filled.png"));
                $(".fra-context-menu[fra-context-menu='file'] .fav #fav-text ").text("Rimuovi dal desktop");
            } else {
                $(".fra-context-menu[fra-context-menu='file'] .fav img").attr("src", $(".fra-context-menu[fra-context-menu='file'] .fav img").attr("src").replace("fav_filled.png", "fav.png"));
                $(".fra-context-menu[fra-context-menu='file'] .fav #fav-text ").text("Aggiungi al desktop");
            }
        }

        $(".fra-context-menu[fra-context-menu='file'] a.open").attr("href", $(".icon[fileid='" + fileID + "']:lt(1)").attr("href"));
        $(".fra-context-menu[fra-context-menu='file'] a.open img").attr("src", $(".icon[fileid='" + fileID + "'] img").attr("src"));

        $(".fra-context-menu[fra-context-menu='file'] a.edit").attr("href", ConfigSite.baseURL + "/my/app/writer/n/" + fileID);
        $(".fra-context-menu[fra-context-menu='file'] a.download").attr("href", ConfigSite.baseURL + "/controller/provide-file/" + fileID);

        fileFraContextMenu.setPosition(e.detail.mouse[0] + "px", e.detail.mouse[1] + "px");
        fileFraContextMenu.show("fadeIn", 200);
    });

    $(document).on("click", ".fra-context-menu[fra-context-menu='file'] a.follow-link", function (e) {
        e.preventDefault();
        window.location.href = $(this).attr("href");
    });

    $(document).on("click", ".icon[fileid] img, .image[fileid]", function (e) {
        e.preventDefault();
        PropertyPanel.show($(this).closest(".icon").attr("fileid"));
    });

    $(document).on("click", ".fra-context-menu[fra-context-menu='file'] a.cut", function (e) {
        e.preventDefault();
        const fileid = $(this).closest(".fra-context-menu").attr("fileID");
        const iconselector = $(".icon[fileid='" + fileid + "']");
        FraCookie.set("cuttingFileManagerFileID", JSON.stringify([fileid, iconselector.attr("title"), iconselector.attr("file_type"), iconselector.find("img").attr("src"), iconselector.find(".second-row").html()]), new Date(new Date().getTime() + 15 * 60 * 1000)); // Cookie set to expire in 15 minutes
        checkPastingFile();
        const moveNotification = new FraNotifications("file-cutting-" + FraBasic.generateGUID(), "File tagliato");
        moveNotification.setAutoClose(2000);
        moveNotification.show();
    });

    $(document).on("click", ".action-button.paste", function (e) {
        e.preventDefault();
        const fileid = JSON.parse(FraCookie.get("cuttingFileManagerFileID"))[0];
        moveFile(fileid, "<?php echo(isset($_GET["folder"]) ? $_GET["folder"] : ""); ?>", "paste");
        checkPastingFile();
    });

    $(document).on("click", ".fra-context-menu[fileid] .project", function (e) {
        e.preventDefault();
        const id = $(this).closest(".fra-context-menu[fileid]").attr("fileid");
        const filename = $(".icon[fileid='" + id + "'] .filename").text();

        openProjectDialog(id, filename,  $(".icon[fileid='" + id + "']").hasClass("notebook"));
    });

    const moveFile = (id, folder, type = null) => {
        const ajax = new FraAjax(ConfigSite.baseURL + "/my/app/file-manager/controller.php?type=move&id=" + id + "&folder=" + folder + "&mode=" + type, "POST", "");

        ajax.execute(function (result) {
            const moveNotification = new FraNotifications("file-moved-" + FraBasic.generateGUID(), result["text"]);
            moveNotification.setAutoClose(2000);
            if (result["response"] === "success") {
                {
                    const selector = document.querySelectorAll('[fileid="' + id + '"]')[0];
                    if (selector) {
                        selector.remove();
                    }
                }
                {
                    const selector = document.querySelectorAll('[fileid="' + folder + '"] .second-row')[0];
                    if (selector) {
                        const split = selector.innerHTML.split(' ');
                        document.querySelectorAll('[fileid="' + folder + '"] .second-row')[0].innerHTML = (parseInt(split[0]) + 1) + ' ' + split[1];
                    }
                }
                if (type === "paste") {
                    const element = $(".folder-view .icon:first").wrap("<p/>").parent();
                    const clone = element.clone(true);
                    const file = JSON.parse(FraCookie.get("cuttingFileManagerFileID"));
                    clone.attr("fileid", file[0]);
                    clone.attr("title", file[1]);
                    clone.find("img").attr("src", file[3]);
                    clone.find(".filename").html(file[1]);
                    clone.find(".second-row").html(file[4]);
                    $(".folder-view > p > .icon ").unwrap();
                    $(".folder-view").prepend(clone.html());
                    FraCookie.delete("cuttingFileManagerFileID");
                }
            } else {
                moveNotification.setType("error");
            }

            moveNotification.show();
        });
    };

    const checkPastingFile = () => {
        const selector = $(".action-button.paste");
        if (FraCookie.get("cuttingFileManagerFileID")) {
            selector.show().attr("title", JSON.parse(FraCookie.get("cuttingFileManagerFileID"))[1]);
        } else {
            selector.hide();
        }
    }
</script>

<div class="fra-context-menu accent-bkg-gradient" fra-context-menu="file">
    <a href="#" class="accent-bkg-all-darker box-shadow-1-all folder notebook file trash diary mobile-block">[x] Chiudi
        menu</a>
    <a href="" class="accent-bkg-all-darker box-shadow-1-all folder notebook file trash diary mobile-block open follow-link"><img
                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/white/files.png"/>Apri</a>

    <a href="" class="accent-bkg-all-darker box-shadow-1-all notebook edit follow-link"><img
                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/white/edit.png"/>Modifica</a>
    <a href="" class="accent-bkg-all-darker box-shadow-1-all diary edit"><img
                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/white/edit.png"/>Modifica</a>
    <a href="" class="accent-bkg-all-darker box-shadow-1-all file download follow-link" download><img
                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/white/download.png"/>Scarica</a>
    <a href="#" class="accent-bkg-all-darker box-shadow-1-all folder notebook file diary share"><img
                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/white/share.png"/>Condividi</a>
    <a href="#" class="accent-bkg-all-darker box-shadow-1-all notebook file diary project"><img
                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/project/icon/white/icon.png"/>Proietta</a>
    <a href="#" class="accent-bkg-all-darker box-shadow-1-all folder notebook file rename"><img
                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/white/edit.png"/>Rinomina</a>
    <!--    <a href="#" class="accent-bkg-all-darker box-shadow-1-all folder notebook file move"><img src="-->
    <?php //echo(CONFIG_SITE["baseURL"]); ?><!--/upload/mono/white/folder.png" />Sposta</a>-->
    <a href="#" class="accent-bkg-all-darker box-shadow-1-all folder notebook file cut"><img
                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/white/cut.png"/>Taglia</a>
    <a href="#" class="accent-bkg-all-darker box-shadow-1-all folder notebook file diary fav"><img
                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/white/fav.png"/><span id="fav-text"></span></a>
    <a href="#" class="accent-bkg-all-darker box-shadow-1-all folder notebook file diary delete"><img
                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/white/cross.png"/>Elimina</a>

    <!--    <a href="#" class="accent-bkg-all-darker box-shadow-1-all notebook move"><img src="-->
    <?php //echo(CONFIG_SITE["baseURL"]); ?><!--/upload/mono/white/download.png" />Esporta</a>-->
    <!--    <a href="#" class="accent-bkg-all-darker box-shadow-1-all notebook lim"><img src="-->
    <?php //echo(CONFIG_SITE["baseURL"]); ?><!--/upload/mono/white/lim.png" />Proietta su LIM</a>-->
    <!--    <a href="#" class="accent-bkg-all-darker box-shadow-1-all notebook embed"><img src="-->
    <?php //echo(CONFIG_SITE["baseURL"]); ?><!--/upload/mono/white/embed.png" />Incorpora</a>-->
    <!--    <a href="#" class="accent-bkg-all-darker box-shadow-1-all notebook history"><img src="-->
    <?php //echo(CONFIG_SITE["baseURL"]); ?><!--/upload/mono/white/history.png" />Cronologia modifiche</a>-->
    <!--    <a href="#" class="accent-bkg-all-darker box-shadow-1-all notebook copy"><img src="-->
    <?php //echo(CONFIG_SITE["baseURL"]); ?><!--/upload/mono/white/copy.png" />Copia</a>-->

    <a href="#" class="accent-bkg-all-darker box-shadow-1-all trash restore"><img
                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/white/back.png"/>Ripristina</a>
    <a href="#" class="accent-bkg-all-darker box-shadow-1-all trash delete"><img
                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/white/cross.png"/>Elimina definitivamente</a>

    <a href="#" class="accent-bkg-all-darker box-shadow-1-all folder notebook file trash diary property"><img
                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/white/info.png"/>Propriet&agrave;</a>
</div>
