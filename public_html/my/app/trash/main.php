<script type="text/javascript">
    const loadTrash = (start = null) => {
        start = start !== null ? start : 0;

        const trash = (new FraJson("controller.php?type=get&start=" + start)).getAll();

        if (trash.length === 0) {
            if ($(".items .icon").length === 0) {
                $(".items").html("<p style='color: gray'>Nessun elemento presente nel cestino.</p>");
                $(".active .empty_trash").remove();
            }
            $(".load-more").remove();
        } else {
            $(".items .loading").remove();
            if (trash.length < 20) $(".load-more").remove();
            $(".load-more").show();

            for (const i in trash) {
                $(".items").append("<a href=\"" + trash[i]["link"] + "\" class='icon img-change-to-white accent-all box-shadow-1-all' style='display: inline-block; " + (trash[i]["diary_color"] ? "color: #" + trash[i]["diary_color"] + " !important" : "") + "' fra-context-menu='file' file-in-trash fileID='" + trash[i]["id"] + "' title=\"" + trash[i]["name"] + "\"><img src=\"" + trash[i]["icon"] + "\" class=\"change-this-img\" style=\"float: left; " + trash[i]["style"] + "\" /><span style=\"display: block; font-size: 1.2em\" class=\"text-ellipsis\">" + trash[i]["name"] + "<span style='display: none'>&nbsp;(</span></span>" + trash[i]["secondRow"] + "<span style='display: none'>)</span></a>");
            }

            recalculateIcons();
        }

        $(".breadcrumb-item.active .num").text($(".items .icon").length);
    };

    $(document).ready(function () {
        loadTrash();
    });

    $(document).on("click", ".load-more", function (e) {
        e.preventDefault();

        loadTrash($(".items .icon").length);
    });

    $(document).on("click", ".fra-context-menu .restore, .fra-context-menu .delete, .active .empty_trash", function (e) {
        e.preventDefault();
        e.stopPropagation();

        const fileID = $(this).closest(".fra-context-menu").attr("fileID");

        let windowName = "", titlebar = "", class1 = "";
        if ($(this).hasClass("restore")) {
            windowName = "restore-" + fileID;
            titlebar = "Ripristina file";
            class1 = "file-restore";
        } else if ($(this).hasClass("delete")) {
            windowName = "delete-" + fileID;
            titlebar = "Elimina file";
            class1 = "file-delete";
        } else if ($(this).hasClass("empty_trash")) {
            windowName = "empty_trash";
            titlebar = "Svuota cestino";
            class1 = "file-empty";
        }

        if (!FraWindows.windowExists(windowName)) {
            const deleteWindow = new FraWindows(windowName, titlebar, "Caricamento...");
            deleteWindow.setTitlebarPadding(10, 0, 10, 0);
            deleteWindow.setContentPadding(20);
            deleteWindow.setSize("100%");
            deleteWindow.setProperty("max-width", "522px");
            deleteWindow.setProperty("max-height", "100vh");
            deleteWindow.attr("fileId", fileID);
            deleteWindow.setControl("close");
            deleteWindow.setDraggable();

            const content = $(".grab ." + class1).wrap('<p/>').parent().html();
            deleteWindow.setContent(content);

            const current = $(".fra-windows[fra-window-id='fra-windows-" + windowName + "'] ." + class1);
            if (!$(this).hasClass("empty_trash")) {
                current.find(".file_name").html($(".icon[fileid='" + fileID + "']").text());
                current.find("form").attr("action", current.find("form").attr("action") + fileID);
            }

            deleteWindow.setPosition();
            deleteWindow.show("fadeIn", 300);
        } else {
            FraWindows.getWindow(windowName).bringToFront();
        }
    });

    $(document).on("submit", ".fra-windows .form-delete", function (e) {
        e.preventDefault();
        const form = new FraForm($(this));
        form.lock();

        const data = form.getDomElements("input", "string");
        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), data);
        const windowID = $(this).closest(".fra-windows").attr("fra-window-id").replace("fra-windows-", "");

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                $(".icon[fileid='" + result["file_id"] + "']").remove();
                $(".breadcrumb-item.active .num").text($(".content-my .icon").length);
                FraWindows.getWindow(windowID).close();
            } else {
                $(".fra-windows .form-delete .response").show().html(result["text"]).addClass("alert alert-danger");
                form.unlock();
            }
        });
    });

    $(document).on("submit", ".fra-windows .form-restore", function (e) {
        e.preventDefault();
        const form = new FraForm($(this));
        form.lock();

        const data = form.getDomElements("input", "string");
        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), data);
        const windowID = $(this).closest(".fra-windows").attr("fra-window-id").replace("fra-windows-", "");

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                $(".icon[fileid='" + result["file_id"] + "']").remove();
                $(".breadcrumb-item.active .num").text($(".content-my .icon").length);
                FraWindows.getWindow(windowID).close();
            } else {
                $(".fra-windows .form-restore .response").show().html(result["text"]).addClass("alert alert-danger");
                form.unlock();
            }
        });
    });

    $(document).on("submit", ".fra-windows .form-empty", function (e) {
        e.preventDefault();
        const form = new FraForm($(this));
        form.lock();

        const data = form.getDomElements("input", "string");
        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), data);
        const windowID = $(this).closest(".fra-windows").attr("fra-window-id").replace("fra-windows-", "");

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                location.reload();
            } else {
                $(".fra-windows .form-empty .response").show().html(result["text"]).addClass("alert alert-danger");
                form.unlock();
            }
        });
    });
</script>

<div class="container content-my trash">

    <div style="display: none" class="grab">
        <div class="file-restore">
            <p>Vuoi ripristinare il file <span class="file_name"></span>?</p>
            <form method="post" action="controller.php?type=restore&id=" class="form-restore">
                <input type="submit" value="Ripristina" style="float: right"
                       class="restore accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"/><br/>
                <div style="clear: both"></div>
                <div class="response" style="margin-top: 10px"></div>
            </form>
        </div>
    </div>

    <div style="display: none" class="grab">
        <div class="file-delete">
            <p>Vuoi eliminare il file <span class="file_name"></span>?</p>
            <form method="post" action="controller.php?type=delete&id=" class="form-delete">
                <input type="submit" value="Elimina definitivamente" style="float: right"
                       class="delete_completely accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"/><br/>
                <div style="clear: both"></div>
                <div class="response" style="margin-top: 10px"></div>
            </form>
        </div>
    </div>

    <div style="display: none" class="grab">
        <div class="file-empty">
            <p>Vuoi veramente svuotare il cestino?</p>
            <p class="small">L'operazione eliminer&agrave; tutti i file contenuti nel cestino. Assicurati che sia ci&ograve;
                che vuoi, l'operazione &egrave; irreversibile.</p>
            <form method="post" action="controller.php?type=empty" class="form-empty">
                <input type="submit" value="Svuota cestino" style="float: right"
                       class="delete_completely accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"/><br/>
                <div style="clear: both"></div>
                <div class="response" style="margin-top: 10px"></div>
            </form>
        </div>
    </div>

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">Cestino (<span class="num"></span> elementi) <a
                        href="#"
                        class="button small empty_trash accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker">Svuota</a>
            </li>
        </ol>
    </nav>

    <div class="folder-view">
        <p style='color: gray' class="search-no-result">Nessun risultato cercando '<span class="searched-text"></span>'.
        </p>

        <div class="items">
            <p class="loading">Caricamento...</p>
        </div>

        <div style="text-align: center">
            <a href="#" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker load-more">Mostra pi&ugrave;
                elementi</a>
        </div>
    </div>
</div>