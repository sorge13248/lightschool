<script type="text/javascript">
    $(document).on("click", ".fra-context-menu[fileid] .rename", function (e) {
        e.preventDefault();
        const id = $(this).closest(".fra-context-menu[fileid]").attr("fileid");
        const windowID = "rename-" + id;

        if (!FraWindows.windowExists(windowID)) {
            const renameWindow = new FraWindows(windowID, "Rinomina ", $(".grab .form-rename").wrap('<p/>').parent().html());
            renameWindow.setTitlebarPadding(10, 0, 10, 0);
            renameWindow.setContentPadding(20);
            renameWindow.setSize("100%");
            renameWindow.setProperty("max-width", "450px");
            renameWindow.setProperty("max-height", "100vh");
            renameWindow.attr("fileid", id);
            renameWindow.setControl("close");
            renameWindow.setDraggable();
            renameWindow.setPosition();

            renameWindow.show("fadeIn", 300);

            const current = $(".fra-windows[fra-window-id='fra-windows-" + windowID + "']");
            const filename = $(".icon[fileid='" + id + "'] .filename").text();
            renameWindow.setTitlebar("\"" + filename + "\"", true);
            current.find(".file-id").html(id);
            current.find(".file-name").html(filename);
            current.find("form #name").val(filename).focus();
            current.find("form").attr("action", current.find("form").attr("action") + id + "&folder=<?php echo($_GET["folder"]); ?>");
            const query = document.querySelectorAll(".fra-windows[fra-window-id='fra-windows-" + windowID + "'] form #name")[0];
            query.setSelectionRange(0, query.value.lastIndexOf("."));

            renameWindow.setPosition();
        } else {
            FraWindows.getWindow(windowID).bringToFront();
        }
    });

    $(document).on("submit", ".fra-windows .form-rename form", function (e) {
        e.preventDefault();
        const form = new FraForm($(this));
        form.lock();

        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), form.getInput());
        const self = $(this);

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                const username = self.closest(".fra-windows").attr("fra-window-id").replace("fra-windows-", "");
                const name = form.getInput("associative-array")["name"];
                $(".icon[fileid='" + self.closest(".fra-windows").attr("fileid") + "']").attr("title", name);
                $(".icon[fileid='" + self.closest(".fra-windows").attr("fileid") + "']").find(".filename").text(name);
                FraWindows.getWindow(username).close();
                const deleteNotification = new FraNotifications(username, result["text"]);
                deleteNotification.show();
                deleteNotification.setAutoClose(2000);
            } else {
                $(".fra-windows .form-rename form .response").show().html(result["text"]).addClass("alert alert-danger");
                form.unlock();
            }
        });
    });
</script>

<div style="display: none" class="grab">
    <div class="form-rename">
        <form method="post"
              action="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/file-manager/controller.php?type=rename&id=">
            <input type="text" id="name" name="name" placeholder="Rinomina" value="" class="box-shadow-1-all"
                   style="width: calc(100% - 140px)"/>
            <input type="submit" value="Rinomina"
                   class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker" style="float: right"/>
            <div class="response" style="clear: both; margin-top: 10px"></div>
        </form>
    </div>
</div>