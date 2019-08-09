<script type="text/javascript">
    const newContact = (name = null, surname = null, username = null) => {
        if (name === null) name = "";
        if (surname === null) surname = "";
        if (username === null) username = "";
        const windowID = "new-contact-" + username;

        if (!FraWindows.windowExists(windowID)) {
            const newContactWindow = new FraWindows(windowID, "Nuovo contatto", "Caricamento...");
            newContactWindow.setTitlebarPadding(10, 0, 10, 0);
            newContactWindow.setContentPadding(20);
            newContactWindow.setSize("100%");
            newContactWindow.setProperty("max-width", "522px");
            newContactWindow.setProperty("max-height", "100vh");
            newContactWindow.attr("username", username);
            newContactWindow.setControl("close");
            newContactWindow.setDraggable();

            const appContent = $(".grab .form-new-contact").wrap('<p/>').parent().html();
            newContactWindow.setContent(appContent);

            newContactWindow.setPosition();
            newContactWindow.show("fadeIn", 300);

            setTimeout(function () {
                $(".fra-windows[fra-window-id='fra-windows-" + windowID + "'] form.form-new-contact #name").removeAttr("disabled").val(name).focus();
                $(".fra-windows[fra-window-id='fra-windows-" + windowID + "'] form.form-new-contact #surname").val(surname);
                $(".fra-windows[fra-window-id='fra-windows-" + windowID + "'] form.form-new-contact #username").val(username);
            }, 10);
        } else {
            FraWindows.getWindow(windowID).bringToFront();
        }
    };

    $(document).on("submit", ".fra-windows .form-new-contact", function (e) {
        e.preventDefault();
        const username = $(this).closest(".fra-windows").attr("username");

        const form = new FraForm($(this));
        form.lock();

        const data = form.getDomElements("input", "string");
        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), data);

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                const notification = new FraNotifications(FraBasic.generateGUID(), result["text"]);
                notification.setAutoClose(2000);
                notification.show();
                FraWindows.getWindow("new-contact-" + username).close();
                if (typeof load !== "undefined") load("contacts", null, true);
            } else {
                $(".fra-windows .form-new-contact .response").show().html(result["text"]).addClass("alert alert-danger");
                form.unlock();
                $(".fra-windows form.form-new-contact #name").focus();
            }
        });
    });
</script>

<div style="display: none" class="grab">
    <form method="post" action="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/contact/controller.php?type=create"
          class="form-new-contact">
        <p>Aggiungendo un contatto, potrai facilmente inviargli messaggi e condividergli contenuti come quaderni, file
            ed eventi.</p>
        <input type="text" id="name" name="name" placeholder="Nome" style="width: calc(50% - 13px)" disabled/><input
                type="text" id="surname" name="surname" placeholder="Cognome" style="width: calc(50% - 13px)"/><br/>
        <input type="text" id="username" name="username" placeholder="Nome utente"
               style="width: calc(100% - 150px)"/><input type="submit" value="Aggiungi"/><br/>
        <div class="response" style="margin-top: 10px"></div>
    </form>
</div>