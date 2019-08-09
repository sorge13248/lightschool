<script type="text/javascript">
    let drafts = FraCookie.get("drafts") !== null ? JSON.parse(FraCookie.get("drafts")) : {};

    class Message {
        static goDown() {
            if (typeof $(".chat")[0] !== "undefined") $(".chat").scrollTop($(".chat")[0].scrollHeight);
        }

        static focusChat() {
            $(".send-message textarea").focus();
        }

        static chat(id, start = null, refresh = null) {
            this.id = id;
            if (start === null) start = 0;
            if (refresh === null) refresh = false;
            if (refresh) $(".chat-opened .items > *").remove();

            const list = (new FraJson("<?php echo(CONFIG_SITE['baseURL']); ?>/my/app/message/controller?type=chat&id=" + id + "&start=" + start)).getAll();

            if (list.length === 0) {
                $(".chat-opened .load-more").remove();
            } else if (list.response === "error") {
                $(".chat-opened .load-more").remove();
                if ($(".chat-opened .chat-bubble").length === 0) {
                    $(".chat-opened").html(list.text);
                }
            } else {
                $(".chat-opened h3 .name_and_surname").html(list["other_user"]["name"] + " " + list["other_user"]["surname"]);
                $(".chat-opened h3 .profile_picture").attr("src", list["other_user"]["profile_picture"]).show();
                $(".chat-opened .items .loading").remove();

                if (list["chat"].length < 20) {
                    $(".chat-opened .load-more").remove();
                }
                $(".chat-opened .load-more").show();

                for (const i in list["chat"]) {
                    let html = "";
                    let is_read = true;
                    /*const curDate = timestampToHuman(list["chat"][i]["date"], "d/m/Y");
                    if (date !== curDate) {
                        date = curDate;
                        temp += "<div style='text-align: center; margin-top: 20px' class='list'>" + date + "</div>";
                    }*/
                    let float;
                    if (list["chat"][i]["sender"] === list["current_user_id"]) {
                        float = "right";
                    } else {
                        is_read = false;
                        float = "left";
                    }

                    html += "<div class=\"chat-bubble " + float + " accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker\" date=\"" + timestampToHuman(list["chat"][i]["date"], "d/m/Y") + "\">" +
                        "                    <div style=\"padding: 10px 20px\">" +
                        list["chat"][i]["body"] + "" +
                        "                        <span class=\"date\" style=\"font-size: 0.7em; display: flex; margin-top: -2px; float: right\" title=\"" + list["chat"][i]["date"] + "\">Inviato: " + list["chat"][i]["date"] + "</span><br/>" +
                        "                        <div style=\"clear: both\"></div>";
                    if (is_read) {
                        html += "                    <span class=\"is_read\" style=\"font-size: 0.7em; display: flex; margin-top: -2px; float: right\" title=\"" + (list["chat"][i]["is_read"] === null ? "" : list["chat"][i]["is_read"]) + "\">Letto: " + (list["chat"][i]["is_read"] === null ? "No" : list["chat"][i]["is_read"]) + "</span>" +
                            "                        <div style=\"clear: both\"></div>";
                    }
                    html += "                    </div>";

                    if (list["chat"][i]["attachment"] !== null) {
                        html += "<div class=\"attachment accent-bkg-gradient-darker\">";
                        if (list["chat"][i]["attachment"]["type"] === "contact") {
                            if (list["chat"][i]["attachment"]["user"] === null) {
                                html += "<p class=\"small\"><i>Questo contatto non esiste pi&ugrave; su LightSchool</i></p>";
                            } else {
                                html += "<img src='" + list["chat"][i]["attachment"]["user"]["profile_picture"] + "' class='profile_picture' style='margin-top: 5px; box-shadow: none; width: 40px; height: 40px' /><span class=\"name_and_surname\"><span class='name'>" + list["chat"][i]["attachment"]["user"]["name"] + "</span> <span class='surname'>" + list["chat"][i]["attachment"]["user"]["surname"] + "</span></span>" +
                                    "                                    <a href=\"#\" class=\"button small add-to-contact\" style=\"float: right; margin-left: 40px\" contactusername=\"" + list["chat"][i]["attachment"]["user"]["username"] + "\">Aggiungi ai contatti</a>" +
                                    "                                    <p class=\"small\" style=\"margin-top: 0; margin-bottom: 0\">" + list["chat"][i]["attachment"]["user"]["username"] + "</p>";
                            }
                            html += "<div style=\"clear: both\"></div>";
                        }
                        html += "</div>";
                    }
                    html += "</div><div style=\"clear: both\"></div>";
                    $(".chat-opened .items").prepend(html);
                }
                recalculateIcons();
            }
        }
    }

    $(document).ready(function () {
        $(".pageTitle .name_and_surname").text($(".message .name_and_surname").text());
        $(".send-message textarea").focus();
        Message.goDown();
    });

    $(document).on("input", ".send-message #body", function () {
        let chatID = $(".message-list .list.selected").attr("chat_id");
        if (typeof chatID === "undefined") {
            chatID = 0 + <?php echo(isset($_GET["id"]) ? (int)$_GET["id"] : 0); ?>;
        }

        drafts[chatID] = $(this).val();
        FraCookie.set("drafts", JSON.stringify(drafts));

        if ($(this).val().trim() === "") {
            $(".list[chat_id='" + chatID + "'] .draft").hide();
        } else {
            $(".list[chat_id='" + chatID + "'] .draft").show();
        }
    });

    $(document).on("submit", ".send-message", function (e) {
        e.preventDefault();

        const form = new FraForm($(this));
        form.lock();

        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), "body=" + base64Encode($(this).find("textarea").val()));

        ajax.execute(function (result) {
            form.unlock();
            const notification = new FraNotifications(FraBasic.generateGUID(), result["text"]);
            notification.setAutoClose(2000);

            if (result["response"] === "success") {
                const dateObj = new Date();
                const date = (dateObj.getDate() <= 9 ? "0" : "") + dateObj.getDate() + "/" + (dateObj.getMonth() + 1 <= 9 ? "0" : "") + (dateObj.getMonth() + 1) + "/" + dateObj.getFullYear() + " " + (dateObj.getHours() <= 9 ? "0" : "") + dateObj.getHours() + ":" + (dateObj.getMinutes() <= 9 ? "0" : "") + dateObj.getMinutes();
                /*if ($(".chat-bubble:last").attr("date") !== date) {
                    $(".chat").append("<div style='text-align: center; margin-top: 20px' class='list'>" + date + "</div>");
                }*/
                $(".chat").append("<div class=\"chat-bubble right accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker\" date=\"" + date + "\"><div style=\"padding: 10px 20px\"><p>" + $(".send-message textarea").val().replace(/\n/g, "<br/>") + "</p><span class=\"date\" style=\"font-size: 0.7em; display: flex; margin-top: -2px; float: right\">" + date + "</span><div style=\"clear: both\"></div></div></div><div style=\"clear: both\"></div>");
                $("textarea#body:focus").val("").trigger("input");
                Message.focusChat();
                Message.goDown();
                loadList(null, true);
            } else {
                notification.setType("error");
            }

            notification.show();
        });
    });

    $(document).keydown(function (event) {
        if (event.which === 13 && event.ctrlKey && $("textarea#body:focus").length > 0) {
            event.preventDefault();
            $("textarea#body:focus").val($("textarea#body:focus").val() + "\n");
        } else if (event.which === 13 && $("textarea#body:focus").length > 0) {
            event.preventDefault();
            $(".send-message").submit();
        }
    });

    $(document).on("click", ".add-to-contact", function (e) {
        e.preventDefault();
        newContact($(this).closest(".attachment").find(".name").text(), $(this).closest(".attachment").find(".surname").text(), $(this).attr("contactusername"));
    });

    $(document).on("click", ".chat-opened .load-more", function (e) {
        e.preventDefault();

        Message.chat(Message.id, $(".chat-opened .items .chat-bubble").length);
    });
</script>