<style type="text/css">
    .fra-windows .history {
        display: block;
        height: 100%;
        max-height: 300px;
        overflow-y: auto;
        padding: 20px;
        border-radius: 20px;
    }

    .fra-windows .history .icon {
        display: block;
        width: 100%;
        max-width: 100%;
        font-size: 0.8em;
    }
</style>

<script type="text/javascript">
    $(document).on("click", ".menu-my .history", function (e) {
        e.preventDefault();

        if (!FraWindows.windowExists("history")) {
            const historyWindow = new FraWindows("history", "Cronologia modifiche", "Caricamento...");
            historyWindow.setTitlebarPadding(10, 0, 10, 0);
            historyWindow.setContentPadding(20);
            historyWindow.setSize("100%");
            historyWindow.setControl("close");
            historyWindow.setProperty("max-width", "450px");
            historyWindow.setProperty("max-height", "100vh");
            historyWindow.setDraggable();
            historyWindow.setPosition();
            historyWindow.show("fadeIn", 300);

            const appContent = $(".grab .form-history").wrap('<p/>').parent().html();
            historyWindow.setContent(appContent);

            const current = $(".fra-windows[fra-window-id='fra-windows-history']");

            const history = new FraJson(ConfigSite.baseURL + "/my/app/reader/controller?type=history&id=<?php echo(isset($_GET["id"]) ? $_GET["id"] : ""); ?>").getAll();

            if (history.length === 0) {
                current.find(".history").html("Nessuna versione precedente trovata").addClass("alert alert-warning");
            } else if (history.response === "error") {
                current.find(".history").html(history.text).addClass("alert alert-danger");
            } else {
                for (const i in history) {
                    current.find(".history").append("<a href=\"" + history[i]["id"] + "\" class='icon img-change-to-white accent-all box-shadow-1-all' style='display: inline-block' title=\"" + history[i]["name"] + "\"><img src=\"" + history[i]["icon"] + "\" class='change-this-img' style='float: left'  onclick='PropertyPanel.show(" + history[i]["id"] + "); return false;' /><span style='display: block; font-size: 1.2em' class='print text-ellipsis'>" + history[i]["name"] + "</span><small>" + history[i]["create_date"] + "</small></a>");
                }

                recalculateIcons();
            }
        } else {
            FraWindows.getWindow("history").bringToFront();
        }
    });
</script>

<div style="display: none" class="grab">
    <div class="form-history">
        <p class="small">Versioni precedenti del quaderno:</p>
        <div class="history">

        </div>
    </div>
</div>

<div style="display: none" class="grab">
    <div class="stop-share">
        <form method="post" action="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/share/controller.php?type=delete&id=">
            <p>Vuoi interrompere la condivisione del file "<span class="file-name"></span>" con <span
                        class="user-name"></span>?</p>
            <input type="submit" value="Conferma" class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                   style="float: right"/>
            <div class="response" style="clear: both; margin-top: 10px"></div>
        </form>
    </div>
</div>