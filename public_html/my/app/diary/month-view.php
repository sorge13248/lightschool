<?php
if (!isset($_GET['year']) || $_GET['year'] < 1970) { // 1970 Unix epoch
    $_GET["year"] = date("Y");
}
if (!isset($_GET['month']) || $_GET['month'] < 1 || $_GET['month'] > 12) {
    $_GET["month"] = date("m");
}

$icon = isset($this->getVariables("currentUser")->theme["icon"]) ? $this->getVariables("currentUser")->theme["icon"] : "black";
?>
<script type="text/javascript">
    let diary;

    class FraCalendar {
        constructor(year, month, element, dayName = null, monthName = null) {
            if (dayName === null) {
                dayName = [null, "monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"];
            }
            if (monthName === null) {
                monthName = [null, "january", "february", "march", "april", "may", "june", "july", "august", "september", "october", "november", "december"];
            }
            this.year = parseInt(year);
            this.month = parseInt(month);
            this.element = element;
            this.starts = [
                [7, 1, 2, 3, 4, 5, 6], // monday
                [1, 2, 3, 4, 5, 6, 7] // sunday
            ];
            this.dayName = dayName;
            this.monthName = monthName;
            this.dayStarts = 0;

            this.navigation = {
                'back': (this.getMonth() === 1 ? this.getYear() - 1 : this.getYear()) + '/' + (this.getMonth() === 1 ? 12 : this.getMonth() - 1),
                'next': (this.getMonth() === 12 ? this.getYear() + 1 : this.getYear()) + '/' + (this.getMonth() === 12 ? 1 : this.getMonth() + 1)
            };
        }

        setDayStarts(day = null) {
            if (day > this.starts.length) {
                console.warn("FraCalendar: Invalid 'dayStarts' property value. Resetting.");
                day = null;
            }
            if (day === null) {
                day = 0;
            }
            this.dayStarts = day;
        }

        getYear() {
            return this.year;
        }

        getMonth() {
            return this.month;
        }

        getDays(year = null, month = null) {
            if (year === null) {
                year = this.year;
            }
            if (month === null) {
                month = this.month;
            }
            const names = Object.freeze(this.starts[this.dayStarts]);
            const date = new Date(year, month - 1, 1);
            const result = [];
            while (date.getMonth() === month - 1) {
                result.push([date.getDate(), names[date.getDay()]]);
                date.setDate(date.getDate() + 1);
            }
            return result;
        }

        build() {
            this.element.html("");
            let variable = "<table>";
            const links = {
                'back': '<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/diary/' + this.navigation.back + '',
                'next': '<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/diary/' + this.navigation.next + ''
            };

            variable += "<h3 class='month'><a href='" + links['back'] + "' class='back' style='float: left'><img src='<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/<?php echo($icon); ?>/back.png' style='width: 32px' /></a>" + (this.monthName[this.getMonth()]) + " " + this.getYear() + "<a href='" + links['next'] + "' class='next' style='float: right'><img src='<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/<?php echo($icon); ?>/next.png' style='width: 32px' /></a></h3>";

            variable += "<tr>";
            let index = 0;
            if (this.dayStarts === 0) {
                index = 1;
            }
            for (const dayName in this.starts[this.dayStarts]) {
                if (this.dayName[this.starts[this.dayStarts][dayName]] !== null) {
                    variable += "<td class='header'><span class='pc-md'>" + this.dayName[this.starts[index][dayName]] + "</span><span class='mobile-md'>" + this.dayName[this.starts[index][dayName]].substr(0, 3) + "</span></td>";
                }
            }
            variable += "</tr>";

            const date = new Date();
            const today = date.getFullYear() + "-" + String(date.getMonth() + 1).padStart(2, '0') + "-" + String(date.getDate()).padStart(2, '0');

            const days = this.getDays();
            let i = 1;
            for (const day in days) {
                if (i === 8) {
                    variable += "</tr>";
                }
                if (i >= 8) {
                    i = 1;
                    variable += "<tr>";
                }
                while (i < parseInt(days[day][1])) {
                    variable += "<td class='empty'></td>";
                    i++;
                }
                const cur_day = this.getYear() + "-" + (String(this.getMonth()).padStart(2, '0')) + "-" + (String(days[day][0]).padStart(2, '0'));
                let selected = "";
                if (cur_day === today) {
                    selected = ' selected';
                }
                variable += "<td class='accent-all box-shadow-1-all white-text-hover day" + selected + "' day='" + cur_day + "'>" + days[day][0] + "</td>";
                i++;
            }
            variable += "</table>";
            this.element.append(variable);
        }
    }

    $(document).ready(function () {
        initializeDiary(<?php echo($_GET["year"]); ?>, <?php echo($_GET["month"]); ?>);
        history.replaceState({
            id: 'diary-' + diary.getYear() + '/' + diary.getMonth()
        }, null, ConfigSite.baseURL + '/my/app/diary/' + diary.getYear() + '/' + diary.getMonth());

        const subjectsList = (new FraJson(ConfigSite.baseURL + "/my/app/timetable/controller.php?type=get-subjects")).getAll();
        if (subjectsList.length === 0) {
            $(".subjectsList").remove();
        } else {
            for (const i in subjectsList) {
                $(".subjectsList").append("<a href=\"#\" class='icon img-change-to-white accent-all box-shadow-1-all' fore='" + subjectsList[i]["fore"] + "' style='max-width: 100%; text-align: left; display: inline-block; color: #" + subjectsList[i]["fore"] + "' title=\"" + subjectsList[i]["subject"] + "\"><span style='display: block' class='print text-ellipsis'>" + subjectsList[i]["subject"] + "</span></a>");
            }
        }
    });

    $(document).on("click", ".back", (e) => {
        e.preventDefault();
        const original = diary.navigation.back;
        const data = diary.navigation.back.split("/");
        initializeDiary(data[0], data[1]);

        history.pushState({
            id: 'diary-' + original
        }, null, ConfigSite.baseURL + '/my/app/diary/' + data[0] + '/' + data[1]);
    });

    $(document).on("click", ".next", (e) => {
        e.preventDefault();
        const original = diary.navigation.next;
        const data = original.split("/");
        initializeDiary(data[0], data[1]);

        history.pushState({
            id: 'diary-' + original
        }, null, ConfigSite.baseURL + '/my/app/diary/' + data[0] + '/' + data[1]);
    });

    window.addEventListener('popstate', function (event) {
        if (history.state && history.state.id.startsWith('diary-') === true) {
            const data = history.state.id.replace('diary-', '').split('/');
            initializeDiary(data[0], data[1]);
        }
    }, false);

    const initializeDiary = (year, month, element = $("#diary")) => {
        diary = new FraCalendar(year, month, element);
        diary.build();
        getEvents();
    };

    const getEvents = () => {
        $(".event").remove();
        const events = new FraJson("<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/diary/controller.php?type=events&year=" + diary.getYear() + "&month=" + diary.getMonth()).get("events");
        for (const i in events) {
            const selector = $(".day[day='" + events[i]["diary_date"] + "']");
            if (selector.children().length <= 0) {
                selector.append("<br class='mobile-md'/><a href=\"\" style='text-decoration: none' class='mobile-md accent-fore'>&bull;</a>");
            }
            selector.append("<a href=\"" + ConfigSite.baseURL + "/my/app/reader/diary/" + events[i]["id"] + "\" fra-context-menu='file' fileid='" + events[i]["id"] + "' file_fav='" + events[i]["fav"] + "' type='" + events[i]["diary_type"] + "' subject='" + events[i]["name"] + "' fore='" + (events[i]["diary_color"] !== null ? events[i]["diary_color"] : "") + "' date='" + events[i]["diary_date"] + "' reminder='" + events[i]["diary_reminder"] + "' priority='" + events[i]["diary_priority"] + "' content='" + base64Encode(events[i]["html"]) + "' file_type='diary' style='text-decoration: none; " + (events[i]["diary_color"] ? "color: #" + events[i]["diary_color"] + " !important" : "") + "' class='event pc-md accent-fore'><br/><span class='filename'>" + events[i]["diary_type"] + " di " + events[i]["name"] + "</span></a>");
        }
    }

    $(document).on("click", ".event", function (e) {
        e.preventDefault();
        e.stopPropagation();
        PropertyPanel.show($(this).attr("fileid"));
    });

    $(document).on("click", ".action-button", function (e) {
        e.preventDefault();
        if (!FraWindows.windowExists("new-event")) {
            const newEventWindow = new FraWindows("new-event", "Nuovo evento diario", "Caricamento...");
            newEventWindow.setTitlebarPadding(10, 0, 10, 0);
            newEventWindow.setContentPadding(20);
            newEventWindow.setSize("100%");
            newEventWindow.setProperty("max-width", "522px");
            newEventWindow.setProperty("max-height", "100vh");
            newEventWindow.setControl("close");
            newEventWindow.setDraggable();

            const appContent = $(".grab .form-new-event").wrap('<p/>').parent().html();
            newEventWindow.setContent(appContent);

            const current = $(".fra-windows[fra-window-id='fra-windows-new-event']");
            current.find("form").attr("action", current.find("form").attr("action") + "create");

            newEventWindow.setPosition();
            newEventWindow.show("fadeIn", 300);

            $(".fra-windows.fra-windows-new-event #type").focus();
        } else {
            FraWindows.getWindow("new-event").bringToFront();
        }
    });

    $(document).on("submit", ".fra-windows .form-new-event form", function (e) {
        e.preventDefault();
        const form = new FraForm($(this));
        form.lock();

        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), form.getAll());
        const self = $(this);

        ajax.execute(function (result) {
            if (result["response"] === "success") {
                getEvents();
                FraWindows.getWindow(self.closest(".fra-windows").attr("fra-window-id").replace("fra-windows-", "")).close();
                const createNotification = new FraNotifications("event-created-" + FraBasic.generateGUID(), result["text"]);
                createNotification.show();
                createNotification.setAutoClose(2000);
            } else {
                $(".fra-windows .form-new-event form .response").show().html(result["text"]).addClass("alert alert-danger");
            }
            form.unlock();
            $(".fra-windows .form-new-event form #type").focus();
        });
    });

    $(document).on("click", ".fra-context-menu[fra-context-menu='file'] a.diary.edit", function (e) {
        e.preventDefault();

        const fileid = $(this).closest(".fra-context-menu").attr("fileid");
        const event = $(".event[fileid='" + fileid + "']:lt(1)");
        const filename = event.find(".filename").text();
        const windowID = "edit-event-" + fileid;

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

            editWindow.setPosition();
            editWindow.show("fadeIn", 300);

            setTimeout(() => {
                const html = new FraJson(ConfigSite.baseURL + "/my/app/file-manager/controller?type=details&id=" + fileid + "&fields=type,html").getAll().file.html;

                const appContent = $(".grab .form-new-event").wrap('<p/>').parent().html();
                editWindow.setContent(appContent);

                const current = $(".fra-windows[fra-window-id='fra-windows-" + windowID + "']");
                current.find("form").attr("action", current.find("form").attr("action") + "edit&id=" + fileid);
                current.find("form #type").val(event.attr("type"));
                current.find("form #subject").val(event.attr("subject"));
                current.find("form #color").attr("id", "color-" + fileid);
                if ($(this).attr("fore") !== "") setFraColorPicker("color-" + fileid, "#" + event.attr("fore"), false);
                else setFraColorPicker("color-" + fileid, "");
                current.find("form #date").val(event.attr("date"));
                current.find("form #reminder").val(event.attr("reminder"));
                current.find("form #priority[value='" + event.attr("priority") + "']").attr("checked", "");
                current.find("form #content").val(html);
                current.find("form .create").attr("value", "Modifica");

                current.find("form #subject").focus();

                editWindow.setPosition();
            }, 300);
        } else {
            FraWindows.getWindow(windowID).bringToFront();
        }
    });

    $(document).on("click", ".day", function(e) {
       e.preventDefault();

        const day = $(this).attr("day");
        const windowID = "events-" + day;

        if (!FraWindows.windowExists(windowID)) {
            const window = new FraWindows(windowID, timestampToHuman(day), "Caricamento...");
            window.setTitlebarPadding(10, 0, 10, 0);
            window.setContentPadding(20);
            window.setSize("100%");
            window.setProperty("max-width", "522px");
            window.setProperty("max-height", "100vh");
            window.setControl("close");
            window.setDraggable();

            window.setPosition();
            window.show("fadeIn", 300);

            setTimeout(() => {
                const appContent = $(".grab .list-events").wrap('<p/>').parent().html();
                window.setContent(appContent);
                const current = $(".fra-windows[fra-window-id='fra-windows-" + windowID + "'] .fra-windows-content .list-events");

                $(".day[day='" + day + "'] a.event").each(function() {
                    const event = $(this);
                    current.append("<a href=\"" + ConfigSite.baseURL + "/my/app/reader/diary/" + event.attr("fileid") + "\" fra-context-menu='file' fileid='" + event.attr("fileid") + "' file_fav='" + event.attr("file_fav") + "' type='" + event.attr("type") + "' subject='" + event.attr("subject") + "' fore='" + event.attr("fore") + "' date='" + event.attr("date") + "' reminder='" + event.attr("reminder") + "' priority='" + event.attr("priority") + "' content='" + event.attr("content") + "' file_type='diary' style='max-width: 100%; " + event.attr("style") + "' class='icon event accent-fore'><span class='filename'>" + event.find(".filename").html() + "</span></a><br/>");
                });

                if (current.find(".event").length === 0) {
                    current.append("<p style='color: gray'>Nessun evento per questo giorno</p>");
                }

                window.setPosition();
            }, 300);
        } else {
            FraWindows.getWindow(windowID).bringToFront();
        }
    });
</script>

<div style="display: none" class="grab">
    <div class="form-new-event" style="text-align: center">
        <form method="post" action="<?php echo(CONFIG_SITE["baseURL"]); ?>/my/app/diary/controller.php?type=">
            <input type="text" id="type" name="type" placeholder="Tipo (es: compiti)" class="box-shadow-1-all"/><br
                    class="mobile">di<br class="mobile"><input type="text" id="color" name="color"
                                                               class="color box-shadow-1-all" placeholder=""
                                                               fra-color-picker='1' fra-color-picker-default
                                                               style="width: 40px; margin-right: 0; border-right: 0; border-top-right-radius: 0; border-bottom-right-radius: 0;"/><input
                    type="text" id="subject" name="subject" placeholder="Materia" class="box-shadow-1-all"
                    style="width: calc(50% - 40px); margin-left: 0; border-left: 0; border-top-left-radius: 0; border-bottom-left-radius: 0;"><br/>
            <p class="no-result small" style="display: none; margin-bottom: -40px">Nessun risultato trovato nelle tue
                materie<br/><br/><br/></p>
            <div class="subjectsList" style="display: none"></div>
            <input type="date" id="date" name="date" placeholder="Data" class="box-shadow-1-all"/> <br class="mobile">
            <input type="date" id="reminder" name="reminder" placeholder="Promemoria" class="box-shadow-1-all"/><br/>
            <b>Priorit&agrave;</b> <label><input type="radio" id="priority" name="priority" value="-1">Bassa</label>
            <label><input type="radio" id="priority" name="priority" value="0" checked>Normale</label> <label><input
                        type="radio" id="priority" name="priority" value="1">Alta</label>
            <textarea id="content" name="content" placeholder="Contenuto" class="box-shadow-1-all"
                      style="width: calc(100% - 10px)"></textarea><br/>
            <div class="response" style="margin-bottom: 0"></div>
            <input type="submit" value="Crea"
                   class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker create"
                   style="float: right"/>
        </form>
    </div>
</div>

<div style="display: none" class="grab">
    <div class="list-events" style="text-align: center"></div>
</div>

<div class="container content-my diary">
    <a class="action-button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker add" href="#">
        <span>+</span>
    </a>
    <div id="diary"></div>
</div>