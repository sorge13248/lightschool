<script type="text/javascript">
    const loadTimetable = () => {
        $(".items > *").remove();
        const timetable = (new FraJson("<?php echo(CONFIG_SITE['baseURL']); ?>/my/app/timetable/controller?type=get")).getAll();

        if (timetable.length === 0) {
            if ($(".items .icon").length === 0) {
                $(".items").html("<p style='color: gray'>Nessun orario salvato. Creane uno!</p>");
            }
        } else {
            $(".items .loading").remove();
            let day = null;
            const days = [<?php echo(json_encode($this->getVariables("FraLanguage")->get("week-days"))); ?>][0];

            for (const i in timetable) {
                if (parseInt(timetable[i]["day"]) !== day) {
                    if (day !== null) $(".items").append("<br/><br class='mobile-md'/>");
                    day = parseInt(timetable[i]["day"]);
                    let className = "";
                    if (day === new Date().getDay()) className = "selected";
                    $(".items").append("<span class='dayRow " + className + "' style='display: inline-block; width: 100%; max-width: 150px; font-weight: bold; font-size: 1.5em' day='" + timetable[i]["day"] + "'>" + days[day] + "</span>");
                }
                if (timetable[i]["book"] === null) timetable[i]["book"] = "&nbsp;";
                if (timetable[i]["fore"] !== null) timetable[i]["fore"] = "color: #" + timetable[i]["fore"];
                $(".items").append("<a href='#' fileid=\"" + timetable[i]["id"] + "\" day='" + day + "' slot='" + timetable[i]["slot"] + "' fore='" + (timetable[i]["fore"] !== null ? timetable[i]["fore"].replace("color: #", "") : "") + "'  class='icon img-change-to-white accent-all box-shadow-1-all' style='display: inline-block' title=\"" + timetable[i]["subject"] + (timetable[i]["book"] !== "&nbsp;" ? ": " + timetable[i]["book"] : "") + "\"><span style=\"display: block; font-size: 1.2em; " + timetable[i]["fore"] + "\" class=\"filename text-ellipsis\">" + timetable[i]["subject"] + "</span><span style='display: inline-block; max-width: 100%' class='book text-ellipsis'>" + timetable[i]["book"] + "</span></a>");
            }
        }
    };

    $(document).ready(function () {
        loadTimetable();

        const subjectsList = (new FraJson(ConfigSite.baseURL + "/my/app/timetable/controller.php?type=get-subjects")).getAll();
        if (subjectsList.length === 0) {
            $(".subjectsList").remove();
        } else {
            for (const i in subjectsList) {
                $(".subjectsList").append("<a href=\"#\" class='icon img-change-to-white accent-all box-shadow-1-all' fore='" + subjectsList[i]["fore"] + "' book='" + (subjectsList[i]["book"] !== null ? subjectsList[i]["book"] : "") + "' style='max-width: 100%; text-align: left; display: inline-block; color: #" + subjectsList[i]["fore"] + "' title=\"" + subjectsList[i]["subject"] + "\"><span style='display: block' class='print text-ellipsis'>" + subjectsList[i]["subject"] + "</span></a>");
            }
        }
    });

    $(document).on("click", ".action-button", function (e) {
        e.preventDefault();
        if (!FraWindows.windowExists("new-subject")) {
            const newWindow = new FraWindows("new-subject", "Nuova materia", "Caricamento...");
            newWindow.setTitlebarPadding(10, 0, 10, 0);
            newWindow.setContentPadding(20);
            newWindow.setSize("100%");
            newWindow.setProperty("max-width", "522px");
            newWindow.setProperty("max-height", "100vh");
            newWindow.setControl("close");
            newWindow.setDraggable();

            const appContent = $(".grab .form-new-subject").wrap('<p/>').parent().html();
            newWindow.setContent(appContent);

            $(".fra-windows .form-new-subject form").attr("action", $(".fra-windows .form-new-subject form").attr("action") + "create");

            newWindow.setPosition();
            newWindow.show("fadeIn", 300);

            $(".fra-windows.fra-windows-new-subject #type").focus();
        } else {
            FraWindows.getWindow("new-subject").bringToFront();
        }
    });

    $(document).on("click", ".icon[fileid]", function (e) {
        e.preventDefault();
        const fileid = $(this).attr("fileid");
        const filename = $(this).find(".filename").text();
        const windowID = "edit-subject-" + fileid;

        if (!FraWindows.windowExists(windowID)) {
            const editWindow = new FraWindows(windowID, filename, "Caricamento...");
            editWindow.setTitlebarPadding(10, 0, 10, 0);
            editWindow.setContentPadding(20);
            editWindow.setSize("100%");
            editWindow.setProperty("max-width", "522px");
            editWindow.setProperty("max-height", "100vh");
            editWindow.setControl("close");
            editWindow.setDraggable();
            editWindow.attr("fileid", fileid);

            const appContent = $(".grab .form-new-subject").wrap('<p/>').parent().html();
            editWindow.setContent(appContent);

            const current = $(".fra-windows[fra-window-id='fra-windows-" + windowID + "']");
            current.find("form").attr("action", current.find("form").attr("action") + "edit&id=" + fileid);
            current.find("form #day").val($(this).attr("day"));
            current.find("form #slot").val($(this).attr("slot"));
            current.find("form #color").attr("id", "color-" + fileid);
            if ($(this).attr("fore") !== "") setFraColorPicker("color-" + fileid, "#" + $(this).attr("fore"), false);
            else setFraColorPicker("color-" + fileid, "");
            current.find("form #subject").val(filename);
            current.find("form #book").val($(this).find(".book").text().trim());
            current.find("form .create").attr("value", "Modifica");
            current.find("form .delete").show();

            editWindow.setPosition();
            editWindow.show("fadeIn", 300);

            current.find("form #subject").focus();
        } else {
            FraWindows.getWindow(windowID).bringToFront();
        }
    });

    $(document).on("click", ".fra-windows .form-new-subject form .delete", function () {
        $(this).closest("form").attr("action", $(this).closest("form").attr("action").replace("edit", "remove"));
    });

    $(document).on("submit", ".fra-windows .form-new-subject form", function (e) {
        e.preventDefault();
        const form = new FraForm($(this));
        form.lock();

        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), form.getAll());
        const self = $(this);

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                FraWindows.getWindow(self.closest(".fra-windows").attr("fra-window-id").replace("fra-windows-", "")).close();
                const createNotification = new FraNotifications("event-created-" + FraBasic.generateGUID(), result["text"]);
                createNotification.show();
                createNotification.setAutoClose(2000);
                loadTimetable();
            } else {
                $(".fra-windows .form-new-subject form .response").show().html(result["text"]).addClass("alert alert-danger");
            }
            form.unlock();
            $(".fra-windows .form-new-subject form #type").focus();
        });
    });
</script>

<div style="display: none" class="grab">
    <div class="form-new-subject" style="text-align: center">
        <form method="post" action="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/timetable/controller.php?type=">
            <select id="day" name="day">
                <option value="" selected disabled>Giorno</option>
                <option value="1">Luned&igrave;</option>
                <option value="2">Marted&igrave;</option>
                <option value="3">Mercoled&igrave;</option>
                <option value="4">Gioved&igrave;</option>
                <option value="5">Venerd&igrave;</option>
                <option value="6">Sabato</option>
                <option value="7">Domenica</option>
            </select> <br class="mobile"> <input type="number" id="slot" name="slot"
                                                                     placeholder="Slot" min="0" max="255"><br/>
            <input type="text" id="color" name="color" class="color" placeholder="" fra-color-picker='1'
                   fra-color-picker-default
                   style="width: 40px; margin-right: 0; border-right: 0; border-top-right-radius: 0; border-bottom-right-radius: 0;"/><input
                    type="text" id="subject" name="subject" placeholder="Materia"
                    style="width: calc(50% - 60px); margin-left: 0; border-left: 0; border-top-left-radius: 0; border-bottom-left-radius: 0;"> <br class="mobile">
            <input type="text" id="book" name="book" placeholder="Libro"><br/>
            <p class="no-result small" style="display: none; margin-bottom: -40px">Nessun risultato trovato nelle tue
                materie<br/><br/><br/></p>
            <div class="subjectsList" style="display: none"></div>
            <div class="response" style="margin-bottom: 0"></div>
            <input type="submit" value="Elimina"
                   class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker delete"
                   style="display: none; float: left"/><input type="submit" value="Crea"
                                                              class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker create"
                                                              style="float: right"/>
        </form>
    </div>
</div>

<div class="container content-my timetable" id="timetable">

    <a class="action-button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker add" href="#">
        <span>+</span>
    </a>

    <div class="folder-view">
        <p style='color: gray' class="search-no-result">Nessun risultato cercando '<span class="searched-text"></span>'.
        </p>

        <div class="items">
            <p class="loading">Caricamento...</p>
        </div>
    </div>
</div>