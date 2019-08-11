<div style="display: none" class="grab">
    <div class="form-project">
        <form method="post"
              action="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/project/controller.php?type=project&file=">
            <p>Proiettare un file sulla LIM permette alla classe di vedere il tuo lavoro.</p>
            <input type="text" id="project" name="project" placeholder="Codice LIM" value="" class="box-shadow-1-all"
                   style="width: calc(100% - 10px)"/>
            <br/>
            <label><input type="checkbox" id="editable" name="editable">Modificabile</label>
            <input type="submit" value="Proietta"
                   class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker" style="float: right"/>
            <div class="response" style="clear: both; margin-top: 10px"></div>
        </form>
    </div>
</div>

<script type="text/javascript">
    const openProjectDialog = (id, name = null, editable = null) => {
        if (editable === null) editable = false;
        const windowID = "project-" + id;

        if (!FraWindows.windowExists(windowID)) {
            const window = new FraWindows(windowID, "Proietta ", $(".grab .form-project").wrap('<p/>').parent().html());
            window.setTitlebarPadding(10, 0, 10, 0);
            window.setContentPadding(20);
            window.setSize("100%");
            window.setProperty("max-width", "450px");
            window.setProperty("max-height", "100vh");
            window.attr("fileid", id);
            window.setControl("close");
            window.setDraggable();

            const current = $(".fra-windows[fra-window-id='fra-windows-" + windowID + "']");
            if (name) window.setTitlebar("\"" + name + "\"", true);
            current.find("form").attr("action", current.find("form").attr("action") + id);
            if (editable === false) {
                current.find("form label").hide();
            }

            window.setPosition();
            window.show("fadeIn", 300);

            current.find("form #project").focus();
        } else {
            FraWindows.getWindow(windowID).bringToFront();
        }
    };

    $(document).on("submit", ".fra-windows .form-project form", function (e) {
        e.preventDefault();
        const form = new FraForm($(this));
        form.lock();

        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), form.getAll());
        const self = $(this);

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                const id = self.closest(".fra-windows").attr("fra-window-id").replace("fra-windows-", "");
                FraWindows.getWindow(id).close();
                const notification = new FraNotifications(id, result["text"]);
                notification.show();
                notification.setAutoClose(2000);
            } else {
                $(".fra-windows .form-project form .response").show().html(result["text"]).addClass("alert alert-danger");
                form.unlock();
            }
        });
    });
</script>