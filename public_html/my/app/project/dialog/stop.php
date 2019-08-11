<div style="display: none" class="grab">
    <div class="form-stop">
        <form method="post"
              action="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/project/controller.php?type=stop&file=">
            <p>Vuoi interrompere la proiezione del file dalla LIM <code class="project-code"></code>?</p>
            <input type="submit" value="S&igrave;"
                   class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker" style="float: right"/>
            <div class="response" style="clear: both; margin-top: 10px"></div>
        </form>
    </div>
</div>

<script type="text/javascript">
    $(document).on("click", ".your-files .icon", function(e) {
        e.preventDefault();

        const id = $(this).attr("fileid");
        const windowID = "stop-" + id;

        if (!FraWindows.windowExists(windowID)) {
            const window = new FraWindows(windowID, "Interrompi proiezione di ", $(".grab .form-stop").wrap('<p/>').parent().html());
            window.setTitlebarPadding(10, 0, 10, 0);
            window.setContentPadding(20);
            window.setSize("100%");
            window.setProperty("max-width", "500px");
            window.setProperty("max-height", "100vh");
            window.attr("fileid", id);
            window.setControl("close");
            window.setDraggable();

            const current = $(".fra-windows[fra-window-id='fra-windows-" + windowID + "']");
            window.setTitlebar("\"" + $(this).find(".filename").html() + "\"", true);
            current.find("form .project-code").html($(this).attr("projectcode"));
            current.find("form").attr("action", current.find("form").attr("action") + id + "&project=" + $(this).attr("projectcode"));

            window.setPosition();
            window.show("fadeIn", 300);

            current.find("form #stop").focus();
        } else {
            FraWindows.getWindow(windowID).bringToFront();
        }
    });

    $(document).on("submit", ".fra-windows .form-stop form", function (e) {
        e.preventDefault();
        const form = new FraForm($(this));
        form.lock();

        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), form.getInput());
        const self = $(this);

        ajax.execute(function (result) {
            console.log(result);
            if (result["response"] === "success") {
                const id = self.closest(".fra-windows").attr("fra-window-id").replace("fra-windows-", "");
                FraWindows.getWindow(id).close();
                const notification = new FraNotifications(id, result["text"]);
                notification.show();
                notification.setAutoClose(2000);
                Project.yourFiles();
            } else {
                $(".fra-windows .form-stop form .response").show().html(result["text"]).addClass("alert alert-danger");
                form.unlock();
            }
        });
    });
</script>