<style type="text/css">
    .contacts {
        display: block;
        height: 100%;
        max-height: 300px;
        overflow-y: auto;
        padding: 20px;
        border-radius: 20px;
    }

    .contacts .icon {
        display: block;
        width: 100%;
        max-width: 100%;
        font-size: 0.8em;
    }

    .contacts .icon .user_image {
        margin-top: 3px;
        width: 16px;
        height: 16px;
    }
</style>

<script type="text/javascript">
    $(document).on("click", ".fra-context-menu[fileid] .share", function (e) {
        e.preventDefault();
        const id = $(this).closest(".fra-context-menu[fileid]").attr("fileid");

        if (!FraWindows.windowExists("share-" + id)) {
            const shareWindow = new FraWindows("share-" + id, "Condividi ", "Caricamento...");
            shareWindow.setTitlebarPadding(10, 0, 10, 0);
            shareWindow.setContentPadding(20);
            shareWindow.setSize("100%");
            shareWindow.setControl("close");
            shareWindow.setProperty("max-width", "450px");
            shareWindow.setProperty("max-height", "100vh");
            shareWindow.attr("fileid", id);
            shareWindow.setDraggable();
            shareWindow.setPosition();
            shareWindow.show("fadeIn", 300);

            setTimeout(() => {
                const appContent = $(".grab .form-share").wrap('<p/>').parent().html();
                shareWindow.setContent(appContent);
                shareWindow.setPosition();

                const current = $(".fra-windows[fra-window-id='fra-windows-share-" + id + "']");
                const filename = $("*[fileid='" + id + "'] .filename:lt(1)").text();
                shareWindow.setTitlebar("\"" + filename + "\"", true);
                current.find(".file-id").html(id);
                current.find(".file-name").html(filename);

                current.find("form").attr("action", current.find("form").attr("action") + id);

                shareWindowReload(id, current, shareWindow);

                current.find("#username").focus();

                const contact = (new FraJson(ConfigSite.baseURL + "/my/app/contact/controller.php?type=get-contacts&start=" + -1)).getAll();

                if (contact["contacts"].length === 0) {
                    current.find(".contacts").remove();
                } else {
                    const sort_by = contact["sort_by"];

                    for (const i in contact["contacts"]) {
                        let print = (sort_by === "name, surname" ? contact["contacts"][i]["contact.name"] + " " + contact["contacts"][i]["contact.surname"] : contact["contacts"][i]["contact.surname"] + " " + contact["contacts"][i]["contact.name"]);

                        current.find(".contacts").append("<a href=\"#\" class='icon img-change-to-white accent-all box-shadow-1-all' style='display: inline-block' title=\"" + print + "\" contact_username='" + contact["contacts"][i]["users.username"] + "'><img original-src='" + contact["contacts"][i]["users_expanded.profile_picture"] + "' src=\"" + contact["contacts"][i]["users_expanded.profile_picture"] + "\" class='user_image change-this-img' style='float: left; border-radius: 50%' /><span style='display: block; font-size: 1.2em' class='print text-ellipsis'>" + print + "</span></a>");
                    }

                    recalculateIcons();
                }
            }, 300);
        } else {
            FraWindows.getWindow("share-" + id).bringToFront();
        }
    });

    const shareWindowReload = (id, current, window) => {
        current.find(".sharing").show();
        current.find(".not_sharing").show();

        const sharing = new FraJson(ConfigSite.baseURL + "/my/app/share/controller.php?type=file-shared&id=" + id).getAll();
        if (sharing.length === 0) {
            current.find(".sharing").hide();
            window.setProperty("max-width", "450px");
            window.setProperty("max-height", "100vh");
        } else {
            current.find(".sharing #sharing_list > *").remove();
            current.find(".not_sharing").hide();
            window.setProperty("max-width", "650px");

            for (const i in sharing) {
                current.find(".sharing #sharing_list").append("<li class=\"list no-transition accent-all box-shadow-1-all stop-share img-change-to-white\" receiving_id='" + sharing[i]["receiving"] + "' style=\"display: block\"><a href=\"#\" style=\"color: <?php echo($this->getVariables("currentUser")->theme["icon"]); ?>; text-decoration: none\"><img src=\"" + sharing[i]["user"]["profile_picture"] + "\" class=\"change-this-img user_image\" style=\"width: 48px; height: 48px; float: left; margin-right: 10px; border-radius: 50%\" /><span class=\"user-name\">" + sharing[i]["user"]["name"] + " " + sharing[i]["user"]["surname"] + "</span><br/><span class=\"timestamp small\">" + (sharing[i]["timestamp"] !== null ? sharing[i]["timestamp"] : "&nbsp;") + "</span></a></li>");
            }
        }
        window.setPosition();
    };

    $(document).on("click", ".stop-share[receiving_id]", function (e) {
        e.preventDefault();
        e.stopPropagation();
        const id = $(this).attr("receiving_id");
        const windowID = "stop-share-" + id;

        if (!FraWindows.windowExists(windowID)) {
            const shareWindow = new FraWindows(windowID, "Conferma", "Caricamento...");
            shareWindow.setTitlebarPadding(10, 0, 10, 0);
            shareWindow.setContentPadding(20);
            shareWindow.setSize("100%");
            shareWindow.setProperty("max-width", "450px");
            shareWindow.setProperty("max-height", "100vh");
            shareWindow.setControl("close");
            shareWindow.attr("fileid", $(this).closest(".fra-windows").attr("fileid"));
            shareWindow.setDraggable();
            shareWindow.setPosition();
            shareWindow.show("fadeIn", 300);

            const appContent = $(".grab .stop-share").wrap('<p/>').parent().html();
            shareWindow.setContent(appContent);

            const current = $(".fra-windows[fra-window-id='fra-windows-" + windowID + "']");
            current.find("form").attr("action", current.find("form").attr("action") + id + "&file_id=" + $(this).closest(".row").find(".file-id").html());
            current.find("form").attr("file_id", $(this).closest(".row").find(".file-id").html());
            current.find("form").attr("user_id", id);
            current.find(".file-name").html($(this).closest(".row").find(".file-name").html());
            current.find(".user-name").html($(this).find(".user-name").html());

            shareWindow.setPosition();
        } else {
            FraWindows.getWindow(windowID).bringToFront();
        }
    });

    $(document).on("submit", ".fra-windows .stop-share form", function (e) {
        e.preventDefault();
        const form = new FraForm($(this));
        form.lock();

        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), "");
        const self = $(this);

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                self.closest(".fra-windows").find(".stop-share[receiving_id='" + self.attr("user_id") + "']").remove();
                const username = self.closest(".fra-windows").attr("fra-window-id").replace("fra-windows-", "");
                FraWindows.getWindow(username).close();

                const id = self.closest(".fra-windows").attr("fileid");
                shareWindowReload(id, $(".fra-windows[fileid='" + id + "']"), FraWindows.getWindow("share-" + id));

                const deleteNotification = new FraNotifications(username, result["text"]);
                deleteNotification.show();
                deleteNotification.setAutoClose(2000);
            } else {
                $(".fra-windows .stop-share form .response").show().html(result["text"]).addClass("alert alert-danger");
                form.unlock();
            }
        });
    });

    $(document).on("input", "#username", function () {
        const val = $(this).val().toLowerCase();
        if (val.length === 0) {
            $(this).closest(".fra-windows").find(".contacts").slideUp(200);
            $(this).closest(".fra-windows").find(".no-result").hide();
        } else {
            $(this).closest(".fra-windows").find(".contacts .icon").css("display", "block");
            $(this).closest(".fra-windows").find(".contacts").slideDown(200);
            $(this).closest(".fra-windows").find(".contacts .icon").each(function () {
                if (!$(this).find(".print").text().toLowerCase().includes(val) && !$(this).attr("contact_username").toLowerCase().includes(val)) {
                    $(this).css("display", "none");
                }
            });

            if ($(this).closest(".fra-windows").find(".contacts .icon:visible").length > 0) {
                $(this).closest(".fra-windows").find(".no-result").hide();
            } else {
                $(this).closest(".fra-windows").find(".no-result").show();
            }
        }
    });

    $(document).on("click", ".contacts .icon", function (e) {
        e.preventDefault();
        $(this).closest(".fra-windows").find("#username").val($(this).attr("contact_username"));
        $(this).closest(".fra-windows").find(".contacts").slideUp(200);
        $(this).closest(".fra-windows").find(".start-sharing").focus();
    });

    $(document).on("submit", ".fra-windows .form-share form", function (e) {
        e.preventDefault();
        const form = new FraForm($(this));
        form.lock();

        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), form.getInput());
        const self = $(this);

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                $(".fra-windows .form-share form .response").hide().html("");
                self.closest(".fra-windows").find("#username").val("");
                const id = self.closest(".fra-windows").attr("fileid");
                shareWindowReload(id, self.closest(".fra-windows"), FraWindows.getWindow("share-" + id));
                const deleteNotification = new FraNotifications("share-started-" + FraBasic.generateGUID(), result["text"]);
                deleteNotification.show();
                deleteNotification.setAutoClose(2000);
            } else {
                $(".fra-windows .form-share form .response").show().html(result["text"]).addClass("alert alert-danger");
            }
            form.unlock();
            $(".fra-windows .form-share form #username").focus();
        });
    });
</script>

<div style="display: none" class="grab">
    <div class="form-share">
        <div class="row">
            <div class="col">
                <span class="file-id" style="display: none"></span>
                <p>Puoi condividere "<span class="file-name"></span>" per permettere ad altri di visualizzarlo e/o
                    modificarlo.</p>
                <p class="small">Condividi con:</p>
                <form method="post"
                      action="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/share/controller.php?type=add&id=">
                    <input type="text" id="username" name="username" placeholder="Nome utente o nome del contatto"
                           class="box-shadow-1-all" style="width: calc(100% - 10px)" autocomplete="off"/>
                    <p class="no-result small" style="display: none; margin-bottom: -40px">Nessun risultato trovato nei
                        tuoi contatti</p>
                    <div class="contacts" style="display: none"></div>
                    <input type="submit" value="Condividi"
                           class="start-sharing button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                           style="float: right"/>
                    <div class="response" style="clear: both; margin-top: 10px"></div>
                </form>
                <p class="small not_sharing" style="clear: both">Non stai condividendo con nessuno</p>
            </div>
            <div class="col sharing">
                <div class="sharing">
                    <p class="small">Stai condividendo con:</p>
                    <ul id="sharing_list" style="padding-left: 0"></ul>
                </div>
            </div>
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