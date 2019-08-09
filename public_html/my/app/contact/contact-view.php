<script type="text/javascript">
    $(document).on("click", ".action-button", function (e) {
        e.preventDefault();
        newContact();
    });

    $(document).on("click", ".contact_icon", function (e) {
        e.preventDefault();
        if (!ctrlPressed) {
            if (!FraWindows.windowExists($(this).attr("contact_username"))) {
                const contactWindow = new FraWindows($(this).attr("contact_username"), $(this).attr("title"), "Caricamento...");
                contactWindow.setTitlebarPadding(10, 0, 10, 0);
                contactWindow.setContentPadding(20);
                contactWindow.setSize("100%");
                contactWindow.setProperty("max-width", "622px");
                contactWindow.setProperty("max-height", "100vh");
                contactWindow.attr("contact_username", $(this).attr("contact_username"));
                contactWindow.attr("contact_id", $(this).attr("contact_id"));
                contactWindow.setControl("close");
                contactWindow.setDraggable();

                const contactContent = $(".grab .contact-detail").wrap('<p/>').parent().html();
                contactWindow.setContent(contactContent);

                const current = $(".fra-windows[fra-window-id='fra-windows-" + $(this).attr("contact_username") + "'] .contact-detail");
                current.find(".contact_id").text($(this).attr("contact_id"));
                current.find(".profile_picture").attr("src", $(".icon[contact_id='" + $(this).attr("contact_id") + "']").find("img").attr("original-src"));
                current.find(".contact_name_and_surname").html($(".icon[contact_id='" + $(this).attr("contact_id") + "']").attr("title"));
                current.find(".official_name").html($(".icon[contact_id='" + $(this).attr("contact_id") + "']").attr("contact_official_name"));
                current.find(".username").html($(".icon[contact_id='" + $(this).attr("contact_id") + "']").attr("contact_username"));
                current.find(".send_message").attr("href", "<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/message/?username=" + $(this).attr("contact_username"));
                current.find(".share_contact").attr("href", "<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/message/?attach=" + $(this).attr("contact_username"));

                if (parseInt($(this).attr("contact_blocked")) === 1) {
                    current.find(".block-button .text").html(current.find(".block-button .text").html().replace("Blocca", "Sblocca"));
                }
                current.find(".block-button").attr("href", current.find(".block-button").attr("href") + "&username=" + $(this).attr("contact_username"));
                current.find(".block-button").attr("is-blocked", parseInt($(this).attr("contact_blocked")));

                current.find(".fav-button").attr("href", current.find(".fav-button").attr("href") + "&fav=" + (parseInt($(this).attr("contact_fav")) === 1 ? "remove" : "add") + "&id=" + contactWindow.attr("contact_id"));
                current.find(".fav-button").attr("is-fav", parseInt($(this).attr("contact_fav")));

                current.find(".pin_contact > .text").text((parseInt($(this).attr("contact_fav")) === 0 ? "Aggiungi al" : "Rimuovi dal") + " Desktop");

                if (parseInt($(this).attr("contact_fav")) === 1) {
                    current.find(".pin_contact > img").attr("src", current.find(".pin_contact > img").attr("src").replace("fav", "fav_filled"));
                }

                contactWindow.setPosition();
                contactWindow.show("fadeIn", 300);
            } else {
                FraWindows.getWindow($(this).attr("contact_id")).bringToFront();
            }
        }
    });

    $(document).on("click", ".delete_contact", function (e) {
        e.preventDefault();
        e.stopPropagation();

        const username = $(this).closest(".fra-windows").attr("contact_username");
        const deleteWindowName = "delete-" + username;

        if (!FraWindows.windowExists(deleteWindowName)) {
            const deleteWindow = new FraWindows(deleteWindowName, "Elimina contatto", "Caricamento...");
            deleteWindow.setTitlebarPadding(10, 0, 10, 0);
            deleteWindow.setContentPadding(20);
            deleteWindow.setSize("100%");
            deleteWindow.setProperty("max-width", "522px");
            deleteWindow.setProperty("max-height", "100vh");
            deleteWindow.attr("contact_username", username);
            deleteWindow.setControl("close");
            deleteWindow.setDraggable();

            const deleteContent = $(".grab .contact-delete").wrap('<p/>').parent().html();
            deleteWindow.setContent(deleteContent);

            const current = $(".fra-windows[fra-window-id='fra-windows-" + deleteWindowName + "'] .contact-delete");
            current.find(".contact_name_and_surname").html($(this).closest(".contact-detail").find(".contact_name_and_surname").text());
            current.find("form").attr("action", current.find("form").attr("action") + $(this).closest(".contact-detail").find(".contact_id").text());

            deleteWindow.setPosition();
            deleteWindow.show("fadeIn", 300);
        } else {
            FraWindows.getWindow(deleteWindowName).bringToFront();
        }
    });

    $(document).on("click", ".fra-windows .block-button", function (e) {
        e.preventDefault();
        const ajax = new FraAjax($(this).attr("href"), "POST", "");
        const self = $(this);

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                const current = parseInt(self.attr("is-blocked"));
                if (current === 1) {
                    self.attr("is-blocked", "0");
                    $(".contact_icon[contact_username='" + self.closest(".fra-windows").attr("contact_username") + "']").attr("contact_blocked", "0");
                    self.attr("href", self.attr("href").replace("remove", "add"));
                    self.find("span").html(self.find("span").html().replace("Sblocca", "Blocca"));
                } else {
                    self.attr("is-blocked", "1");
                    $(".contact_icon[contact_username='" + self.closest(".fra-windows").attr("contact_username") + "']").attr("contact_blocked", "1");
                    self.attr("href", self.attr("href").replace("add", "remove"));
                    self.find("span").html(self.find("span").html().replace("Blocca", "Sblocca"));
                }
            }
            const username = self.closest(".fra-windows").attr("id");
            const errorNotification = new FraNotifications("contact-block-" + FraBasic.generateGUID(), result["text"]);
            errorNotification.show();
            errorNotification.setAutoClose(2000);
        });
    });

    $(document).on("click", ".fra-windows .fav-button", function (e) {
        e.preventDefault();
        const ajax = new FraAjax($(this).attr("href"), "POST", "");
        const self = $(this);

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                const current = parseInt(self.attr("is-fav"));
                if (current === 1) {
                    self.attr("is-fav", "0");
                    $(".contact_icon[contact_username='" + self.closest(".fra-windows").attr("contact_username") + "']").attr("contact_fav", "0");
                    self.attr("href", self.attr("href").replace("remove", "add"));
                    self.find("img").attr("src", self.find("img").attr("src").replace("fav_filled", "fav"));
                    self.find("span").html(self.find("span").html().replace("Rimuovi dal", "Aggiungi al"));
                } else {
                    self.attr("is-fav", "1");
                    $(".contact_icon[contact_username='" + self.closest(".fra-windows").attr("contact_username") + "']").attr("contact_fav", "1");
                    self.attr("href", self.attr("href").replace("add", "remove"));
                    self.find("img").attr("src", self.find("img").attr("src").replace("fav", "fav_filled"));
                    self.find("span").html(self.find("span").html().replace("Aggiungi al", "Rimuovi dal"));
                }
            }
            const username = self.closest(".fra-windows").attr("id");
            const errorNotification = new FraNotifications("contact-fav-" + FraBasic.generateGUID(), result["text"]);
            errorNotification.show();
            errorNotification.setAutoClose(2000);
        });
    });

    $(document).on("submit", ".fra-windows .form-delete", function (e) {
        e.preventDefault();
        const form = new FraForm($(this));
        form.lock();

        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), form.getRadio("string"));
        const self = $(this);

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                const username = self.closest(".fra-windows").attr("contact_username");
                FraWindows.getWindow(username).close();
                FraWindows.getWindow("delete-" + username).close();
                load("contacts", null, true);
                const deleteNotification = new FraNotifications("delete-" + username, result["text"]);
                deleteNotification.show();
                deleteNotification.setAutoClose(2000);
            } else {
                $(".fra-windows .form-delete .response").show().html(result["text"]).addClass("alert alert-danger");
                form.unlock();
            }
        });
    });
</script>

<script type="text/javascript">
    const load = (type, start = null, reload = null) => {
        if (reload === true) {
            $("." + type + " .items *").remove();
            $("." + type + " .load-more").show();
            start = null;
        }

        start = start !== null ? start : 0;

        const items = (new FraJson("controller.php?type=get-" + type + "&start=" + start)).getAll();
        const divide_letters = items["divide_letters"];
        const first_letter = items["first_letter"];
        const sort_by = items["sort_by"];

        if (items["response"] === "error") {
            $("." + type).html("<div class='alert alert-danger'><h4>Errore</h4><p>" + items["text"] + "</p></div>");
        } else {
            if (items["contacts"].length === 0) {
                if ($("." + type + " .items .icon").length === 0) {
                    $("." + type + " .items").html("<p style='color: gray'>Nessun contatto salvato in rubrica.</p>");
                }
                $("." + type + " .load-more").remove();
            } else {
                $("." + type + " .items .loading").remove();
                if (items["contacts"].length < 20) $("." + type + " .load-more").remove();
                $("." + type + " .load-more").show();

                let previous_letter = null;
                for (const i in items["contacts"]) {
                    let letter = items["contacts"][i][first_letter].substr(0, 1).toLowerCase();
                    if (divide_letters === true && previous_letter !== letter) {
                        if (previous_letter !== null) {
                            $("." + type + " .items").append("<br/><br/>");
                        }
                        $("." + type + " .items").append("<br/><span style='font-weight: bold'>" + (letter.toUpperCase()) + "</span><br/>");
                        previous_letter = letter;
                    }

                    let print = (sort_by === "name, surname" ? items["contacts"][i]["contact.name"] + " " + items["contacts"][i]["contact.surname"] : items["contacts"][i]["contact.surname"] + " " + items["contacts"][i]["contact.name"]);
                    $("." + type + " .items").append("<a href=\"../reader/contact/" + items["contacts"][i]["contact.id"] + "\" class='icon img-change-to-white contact_icon selectable accent-all box-shadow-1-all' style='display: inline-block' title=\"" + print + "\" contact_username='" + items["contacts"][i]["users.username"] + "' contact_id='" + items["contacts"][i]["contact.id"] + "' contact_fav='" + items["contacts"][i]["contact.fav"] + "' contact_blocked='" + items["contacts"][i]["blocked"] + "' contact_official_name='" + items["contacts"][i]["users_expanded.name"] + " " + items["contacts"][i]["users_expanded.surname"] + "'><img original-src='" + items["contacts"][i]["users_expanded.profile_picture"] + "' src=\"" + items["contacts"][i]["users_expanded.profile_picture"] + "\" class='change-this-img' style='float: left; border-radius: 50%; width: 32px; height: 32px' /><span style='display: block; font-size: 1.2em' class='text-ellipsis'>" + print + "</span></a>");
                }

                recalculateIcons();
            }
        }
    };

    $(document).ready(function () {
        load("contacts");
    });

    $(document).on("click", ".load-more", function (e) {
        e.preventDefault();

        const section = $(this).closest(".contact .section").attr("class").split(' ')[1];
        load(section, $("." + section + " .items .icon").length);
    });
</script>

<style type="text/css">
    .contact-detail .profile_picture {
        box-shadow: none;
    }

    .contact-detail .icon {
        width: 100%;
        max-width: 100%;
        text-align: left;
    }

    .contact-detail .icon img {
        float: left;
        width: 24px;
        height: 24px;
        margin-right: 10px;
    }
</style>

<div class="container content-my contact">
    <div style="display: none" class="grab">
        <div class="contact-detail">
            <span class="contact_id" style="display: none"></span>
            <h3><img src=""
                     style="width: 64px; height: 64px; border-radius: 50%; float: left; margin-right: 20px; margin-top: 5px"
                     class="profile_picture"/><span class="contact_name_and_surname"></span></h3>
            <p style="word-wrap: break-word"><b>Nome ufficiale:</b> <span class="official_name"></span><span class="pc">&nbsp;&bull;&nbsp;</span><br
                        class="mobile"/>Nome utente:</b> <span class="username"></span></p>
            <br/>
            <div class="row">
                <div class="col-sm-6">
                    <a href="#" class="icon img-change-to-white send_message accent-all box-shadow-1-all"><img
                                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/black/message.png"
                                class="change-this-img"/>Invia messaggio</a>
                </div>
                <div class="col-sm-6">
                    <a href="#" class="icon img-change-to-white share_contact accent-all box-shadow-1-all"><img
                                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/black/share.png"
                                class="change-this-img"/>Condividi per messaggio</a>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <a href="controller.php?type=block"
                       class="icon img-change-to-white block_contact accent-all box-shadow-1-all block-button"><img
                                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/black/cross.png"
                                class="change-this-img"/><span class="text">Blocca contatto</span></a>
                </div>
                <div class="col-sm-6">
                    <a href="#" class="icon img-change-to-white delete_contact accent-all box-shadow-1-all"><img
                                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/trash/icon/black/icon.png"
                                class="change-this-img"/>Elimina contatto</a>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <a href="controller.php?type=fav"
                       class="icon img-change-to-white pin_contact accent-all box-shadow-1-all fav-button"><img
                                src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/black/fav.png"
                                class="change-this-img"/><span class="text"></span></a>
                </div>
            </div>
        </div>
    </div>

    <div style="display: none" class="grab">
        <div class="contact-delete">
            <p>Vuoi eliminare il contatto <span class="contact_name_and_surname"></span>?</p>
            <form method="post" action="controller.php?type=delete&id=" class="form-delete">
                <!--<label><input type="radio" id="type" name="type" value="move_to_trash" />Sposta nel
                    cestino</label>
                <label><input type="radio" id="type" name="type" value="delete_completely" checked />Elimina
                    definitivamente</label>-->
                <input type="submit" value="Conferma" style="float: right"
                       class="accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"/><br/>
                <div class="response" style="margin-top: 10px"></div>
            </form>
        </div>
    </div>

    <a class="action-button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker add" href="#">
        <span>+</span>
    </a>

    <div class="section contacts">
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