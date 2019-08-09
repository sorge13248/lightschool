<style type="text/css">
    .message .list {
        width: 100%;
        max-width: 100%;
    }

    .message .list .date {
        font-size: 0.8em;
        display: flex;
        margin-top: -2px;
        color: grey;
    }
</style>

<script type="text/javascript">
    let currentChat = null;

    $(document).ready(() => {
        loadList();

        <?php if (isset($_GET["username"]) || isset($_GET["attach"])) { ?>
            $(".action-button").trigger("click");
            setTimeout(() => {
                <?php if (isset($_GET["username"])) { ?>
                    $(".fra-windows #username").val("<?php echo(htmlspecialchars($_GET["username"])); ?>");
                    $(".fra-windows #body").focus();
                <?php } ?>
                <?php if (isset($_GET["attach"])) { ?>
                    $(".fra-windows .attachment").show();
                    $(".fra-windows .attachment #attachment-type").val("contact");
                    $(".fra-windows .attachment span.attachment-value").html("<?php echo(htmlspecialchars($_GET["attach"])); ?>");
                    $(".fra-windows .attachment input.attachment-value").val("<?php echo(htmlspecialchars($_GET["attach"])); ?>");
                <?php } ?>
            }, 100);
        <?php } ?>
    });

    $(document).on("click", ".message-list .list", function (e) {
        e.preventDefault();

        const id = $(this).attr("chat_id");
        loadChat(id);
    });

    const loadChat = (id) => {
        $(".content-my .selected").removeClass("selected");
        $(".icon[chat_id='" + id + "']").addClass("selected");
        $(".message-list").attr("chatid", id);
        currentChat = id;

        $(".chat-content").load("chat.php?min=1&id=" + id, function () {
            if (drafts[id] !== null) {
                $(".chat-opened #body").val(drafts[id]);
            }
            $(".icon[chat_id='" + id + "']").removeClass("new");
            Message.chat(id);
            Message.focusChat();
            Message.goDown();
        });
    };

    $(document).on("click", ".message-list .load-more", function (e) {
        e.preventDefault();

        loadList($(".message-list .items .icon").length);
    });

    const loadList = (start = null, refresh = null) => {
        if (start === null) start = 0;
        if (refresh === null) refresh = false;
        if (refresh) $(".message-list .items > *").remove();

        const list = (new FraJson("<?php echo(CONFIG_SITE['baseURL']); ?>/my/app/message/controller?type=list&start=" + start)).getAll();

        if (list.length === 0) {
            if ($(".message-list .items .icon").length === 0) {
                $(".message-list .items").html("<p style='color: gray'>Nessuna chat, iniziane una! :)</p>");
            }
            $(".message-list .load-more").remove();
        } else {
            if (list.length < 20) {
                $(".message-list .load-more").remove();
            }
            $(".message-list .load-more").show();

            $(".message-list .items .loading").remove();

            for (const i in list) {
                $(".message-list .items").append("<a href=\"" + list[i]["id"] + "\" class=\"list icon img-change-to-white accent-all box-shadow-1-all " + (list[i]["new"] == true ? "new" : "") + " " + (list[i]["id"] === currentChat ? "selected" : "") + "\" chat_id=\"" + list[i]["id"] + "\">\n" +
                    "                        <img class=\"change-this-img\" src=\"" + list[i]["user"]["profile_picture"] + "\" style=\"float: left; border-radius: 50%; width: 40px; height: 40px\" />\n" +
                    "                        <span class=\"user\"><span class='draft' style='color: orange; " + (drafts[list[i]["id"]] ? "" : "display: none") + "'>[Bozza]</span> " + list[i]["user"]["name"] + " " + list[i]["user"]["surname"] + "</span><br/>\n" +
                    "                        <span class=\"date\">" + list[i]["date"] + "</span>\n" +
                    "                    </a>");
            }
            recalculateIcons();
        }
    };

    $(document).on("click", ".action-button", function (e) {
        e.preventDefault();
        const windowID = "new-message-" + FraBasic.generateGUID();

        if (!FraWindows.windowExists(windowID)) {
            const shareWindow = new FraWindows(windowID, "Nuovo messaggio", $(".grab .form-new-message").wrap('<p/>').parent().html());
            shareWindow.setTitlebarPadding(10, 0, 10, 0);
            shareWindow.setContentPadding(20);
            shareWindow.setSize("100%");
            shareWindow.setControl("close");
            shareWindow.setProperty("max-width", "450px");
            shareWindow.setProperty("max-height", "100vh");
            shareWindow.setDraggable();
            shareWindow.setPosition();
            shareWindow.show("fadeIn", 300);

            setTimeout(() => {
                const current = $(".fra-windows[fra-window-id='fra-windows-" + windowID + "']");
                if($(".fra-windows #body:focus").length === 0) current.find("#username").focus();

                const contact = (new FraJson(ConfigSite.baseURL + "/my/app/contact/controller.php?type=get-contacts&start=-1")).getAll();

                if (contact["contacts"].length === 0) {
                    current.find(".contacts").remove();
                } else {
                    const sort_by = contact["sort_by"];

                    for (const i in contact["contacts"]) {
                        let print = (sort_by === "name, surname" ? contact["contacts"][i]["contact.name"] + " " + contact["contacts"][i]["contact.surname"] : contact["contacts"][i]["contact.surname"] + " " + contact["contacts"][i]["contact.name"]);

                        current.find(".contacts").append("<a href=\"#\" class='icon img-change-to-white accent-all box-shadow-1-all' style='display: inline-block; max-width: 100%' title=\"" + print + "\" contact_username='" + contact["contacts"][i]["users.username"] + "'><img original-src='" + contact["contacts"][i]["users_expanded.profile_picture"] + "' src=\"" + contact["contacts"][i]["users_expanded.profile_picture"] + "\" class='user_image change-this-img' style='float: left; border-radius: 50%; width: 24px; height: 24px' /><span style='display: block; font-size: 1.2em' class='print text-ellipsis'>" + print + "</span></a>");
                    }

                    recalculateIcons();
                }
            }, 300);
        } else {
            FraWindows.getWindow(windowID).bringToFront();
        }
    });

    $(document).on("input", ".fra-windows #username", function () {
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
        $(this).closest(".fra-windows").find("#body").focus();
    });

    $(document).on("submit", ".fra-windows form.new-message", function (e) {
        e.preventDefault();

        const form = new FraForm($(this));
        form.lock();


        let attach = "?";
        if ($(this).find("#attachment-type").length > 0) {
            attach ="attach=" + base64Encode(JSON.stringify({"type": $(this).find(".attachment #attachment-type").val(), "value": $(this).find(".attachment #attachment-value").val()})) + "&";
        }

        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), attach + "username=" + $(this).find("#username").val() + "&body=" + base64Encode($(this).find("#body").val()));
        const self = $(this);

        ajax.execute(function (result) {
            console.log(result);
            const notification = new FraNotifications(FraBasic.generateGUID(), result["text"]);
            notification.setAutoClose(2000);

            if (result["response"] === "success") {
                notification.show();
                loadList(null, true);
                if ($(".message-list[chatid]").length === 0) {
                    loadChat(result["id"]);
                } else if ($(".message-list[chatid='" + result["id"] + "']").length === 1) {
                    Message.chat(result["id"], null, true);
                }

                FraWindows.getWindow(self.closest(".fra-windows").attr("fra-window-id").replace("fra-windows-", "")).close();
                Message.focusChat();
                Message.goDown();
            } else {
                notification.setType("error");
                notification.show();
                form.unlock();
            }
        });
    });
</script>

<div style="display: none" class="grab">
    <div class="form-new-message">
        <form method="post" action="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/message/controller.php?type=new"
              class="new-message">
            <input type="text" id="username" name="username" placeholder="Nome utente o nome del contatto"
                   class="box-shadow-1-all" style="width: calc(100% - 10px)" autocomplete="off"/>
            <p class="no-result small" style="display: none; margin-bottom: -40px">Nessun risultato trovato nei tuoi
                contatti<br/><br/><br/></p>
            <div class="contacts" style="display: none"></div>
            <textarea id="body" name="body" placeholder="Scrivi qui il tuo messaggio..."
                      style="width: calc(100% - 10px)"></textarea>
            <div class="attachment" style="display: none">
                <p>Allegato: <span class="attachment-value"></span></p>
                <input type="hidden" id="attachment-type" name="attachment-type" placeholder="Tipo" readonly /><input type="hidden" id="attachment-value" name="attachment-value" class="attachment-value" placeholder="Allegato" readonly />
            </div>
            <input type="submit" value="Invia" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                   style="float: right"/>
            <div class="response" style="clear: both; margin-top: 10px"></div>
        </form>
    </div>
</div>

<div class="container content-my message">
    <a class="action-button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker add" href="#"
       style="left: 30px; right: auto">
        <span>+</span>
    </a>

    <div class="row">
        <div class="col-md-4" style="max-width: 500px; padding: 0">
            <div class="message-list"
                 style="max-height: calc(100vh - 140px); overflow-y: auto; overflow-x: hidden; padding: 20px 20px 80px 20px;">
                <div class="items">
                    <p class="loading">Caricamento...</p>
                </div>

                <div style="text-align: center">
                    <a href="#" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker load-more">Mostra
                        pi&ugrave; elementi</a>
                </div>
            </div>
        </div>
        <div class="col" style="height: 10px; padding-top: 20px">
            <div class="chat-content">

            </div>
        </div>
    </div>
</div>