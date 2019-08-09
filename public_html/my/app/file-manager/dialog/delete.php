<script type="text/javascript">
    $(document).on("click", ".fra-context-menu[fileid] .delete", function (e) {
        e.preventDefault();
        const id = $(this).closest(".fra-context-menu[fileid]").attr("fileid");
        const windowID = "delete-" + id;

        if (!FraWindows.windowExists(windowID)) {
            const deleteWindow = new FraWindows(windowID, "Elimina ", $(".grab .form-delete").wrap('<p/>').parent().html());
            deleteWindow.setTitlebarPadding(10, 0, 10, 0);
            deleteWindow.setContentPadding(20);
            deleteWindow.setSize("100%");
            deleteWindow.setProperty("max-width", "450px");
            deleteWindow.setProperty("max-height", "100vh");
            deleteWindow.setControl("close");
            deleteWindow.setDraggable();
            deleteWindow.setPosition();

            deleteWindow.show("fadeIn", 300);

            const filename = $("[fileid='" + id + "'] .filename:lt(1)").text();
            const current = $(".fra-windows[fra-window-id='fra-windows-" + windowID + "']");
            deleteWindow.setTitlebar("\"" + filename + "\"", true);
            current.find(".file-id").html(id);
            current.find(".file-name").html(filename);
            current.find("form #name").val(filename).focus();
            current.find("form").attr("action", current.find("form").attr("action") + id);

            deleteWindow.setPosition();
        } else {
            FraWindows.getWindow(windowID).bringToFront();
        }
    });

    $(document).on("submit", ".fra-windows .form-delete form", function (e) {
        e.preventDefault();
        const form = new FraForm($(this));
        form.lock();

        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), form.getRadio("string"));
        const self = $(this);

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                if (PropertyPanel.getFileID() === self.closest(".form-delete").find(".file-id").text()) {
                    PropertyPanel.close();
                }
                $("[fileid='" + self.closest(".form-delete").find(".file-id").text() + "']").remove();
                const deleteID = self.closest(".fra-windows").attr("fra-window-id").replace("fra-windows-", "");
                FraWindows.getWindow(deleteID).close();
                const deleteNotification = new FraNotifications(deleteID, result["text"]);
                deleteNotification.show();
                deleteNotification.setAutoClose(2000);
            } else {
                $(".fra-windows .form-delete form .response").show().html(result["text"]).addClass("alert alert-danger");
                form.unlock();
            }
        });
    });
</script>

<div style="display: none" class="grab">
    <div class="form-delete">
        <span class="file-id" style="display: none"></span>
        <p style="margin-bottom: 0; padding-bottom: 0">Vuoi eliminare il file "<span class="file-name">"</span>?</p>
        <form method="post"
              action="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/file-manager/controller.php?type=delete&id=">
            <label><input type="radio" id="type" name="type" value="move_to_trash" checked/>Sposta nel cestino</label>
            <label><input type="radio" id="type" name="type" value="delete_completely"/>Elimina definitivamente</label>
            <input type="submit" value="Conferma" style="float: right"
                   class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"/><br/>
            <div class="response" style="clear: both; margin-top: 10px"></div>
        </form>
    </div>
</div>