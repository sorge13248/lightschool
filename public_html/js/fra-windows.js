/**
 * @author Francesco Sorge <contact@francescosorge.com>
 * @link http://www.francescosorge.com/docs/latest/index.html
 */

let allFraWindows = new Object();
let overlays = 0;

class FraWindows {

    constructor(id, title = null, content = null) {
        this.id = "fra-windows-" + id;

        if (title === null) {
            title = "New window";
        }

        if (content === null) {
            content = "This window is empty";
        }
        $("body").append("<div class='fra-windows " + this.id + "' fra-window-id='" + this.id + "'><div class='titlebar accent-frawindows-titlebar'><div class='left-control'></div><div class='center-control'>" + title + "</div><div class='right-control'></div></div><div class='fra-windows-content'>" + content + "</div></div>");

        $(document).on("click dragstart", ".fra-windows." + this.id, function () {
            allFraWindows[$(this).attr("fra-window-id")].bringToFront();
        });

        $(document).on("click", ".fra-windows." + this.id + " .btn_close", function (e) {
            e.stopPropagation();
        });

        allFraWindows[this.id] = this;

        this.bringToFront();
    }

    static getVersion() {
        return 0.5;
    }

    static calculateMaxZindex() {
        let maxZindex = 0;

        $(".fra-windows").each(function () {
            if (maxZindex < $(this).css("z-index")) {
                maxZindex = $(this).css("z-index");
            }
        });

        return maxZindex;
    }

    static getFocused() {
        let maxZindex = null;

        $(".fra-windows").each(function () {
            if (maxZindex === null || maxZindex.css("z-index") < $(this).css("z-index")) {
                maxZindex = $(this);
            }
        });

        return maxZindex === null ? null : maxZindex.attr("fra-window-id").replace("fra-windows-", "");
    }

    static windowExists(id) {
        return $(".fra-windows-" + id).length;
    }

    static getWindow(id) {
        return allFraWindows["fra-windows-" + id];
    }

    bringToFront() {
        if (this.id !== undefined) {
            $(".fra-windows").removeClass("active");
            $("." + this.id).css("z-index", parseInt(FraWindows.calculateMaxZindex()) + 1).addClass("active");
        }
    }

    getWindowId() {
        return this.id;
    }

    setPosition(x = null, y = null, ifNotMoved = null) {
        if (ifNotMoved === null) ifNotMoved = true;

        if (ifNotMoved && $("." + this.id).hasClass("dragged")) { // do not move window if it has been drag
            return false;
        }

        if (x === null) {
            x = ($(window).width() - $("." + this.id).outerWidth()) / 2;

            if (x < 0) {
                x = 0;
            }
        }

        if (y === null) {
            y = ($(window).height() - $("." + this.id).outerHeight()) / 2;

            if (y < 0 + $(".fra-windows-margin-top").outerHeight()) {
                y = 0 + $(".fra-windows-margin-top").outerHeight();
            }
        }

        $("." + this.id).css({"top": y, "left": x});
    }

    setSize(width = null, height = null) {
        if (width !== null) {
            if (width > $(window).width()) {
                width = $(window).width();
            }

            $("." + this.id).css("width", width);
            $("." + this.id).attr("fra-windows-width", width);
        }

        if (height !== null) {
            if (height > $(window).height()) {
                height = $(window).height();
            }

            $("." + this.id).find(".fra-windows-content").css("height", height);
            $("." + this.id).attr("fra-windows-height", height);
        }
    }

    setTitlebarPadding(paddingTop = 0, paddingRight = null, paddingBottom = null, paddingLeft = null) {
        if (paddingRight === null) {
            paddingRight = paddingTop;
        }
        if (paddingBottom === null) {
            paddingBottom = paddingTop;
        }
        if (paddingLeft === null) {
            paddingLeft = paddingTop;
        }

        $("." + this.id).find(".titlebar .center-control").css("padding", paddingTop + "px " + paddingRight + "px " + paddingBottom + "px " + paddingLeft + "px");
    }

    setContentPadding(paddingTop = 0, paddingRight = null, paddingBottom = null, paddingLeft = null) {
        if (paddingRight === null) {
            paddingRight = paddingTop;
        }
        if (paddingBottom === null) {
            paddingBottom = paddingTop;
        }
        if (paddingLeft === null) {
            paddingLeft = paddingRight;
        }

        $("." + this.id).find(".fra-windows-content").css("padding", paddingTop + "px " + paddingRight + "px " + paddingBottom + "px " + paddingLeft + "px");
    }

    setDraggable(boolean = true) {
        if (boolean === true) {
            $("." + this.id).draggable({
                handle: ".titlebar",
                containment: "window",
                drag: function () {
                    $(this).addClass('dragged');
                }
            });
        } else {
            $("." + this.id).draggable('disable');
        }
    }

    setResizable(boolean = true) { // BUGGED
        if (boolean === true) {
            $("." + this.id).resizable({
                helper: "ui-resizable-helper",
                animate: true
            });
        } else {
            $("." + this.id).resizable('disable');
        }
    }

    setOverlay(boolean = true) {
        if (boolean === true) {
            if ($("#overlay-" + this.id).length === 0) {
                $("body").append("<div class='overlay' id='overlay-" + this.id + "' style='z-index: " + (parseInt(FraWindows.calculateMaxZindex()) + 1) + "'></div>");
                $("." + this.id).css("z-index", parseInt(FraWindows.calculateMaxZindex()) + 2);
                overlays++;
                $("html").css("overflow-y", "hidden");
            }
        } else {
            if (this.effect !== null && (this.duration === null || !parseInt(this.duration))) {
                this.duration = 300;
            }

            if (this.effect !== null) {
                $("#overlay-" + this.id).fadeOut(this.duration).queue(function () {
                    $(this).remove();
                });
            } else {
                $("#overlay-" + this.id).remove();
            }

            overlays--;

            if (overlays === 0) {
                $("html").css("overflow-y", "auto");
            }
        }
    }

    setProperty(name, value) {
        if (name !== null && value !== null) {
            $("." + this.id).css(name, value);
        }
    }

    setProperties(width = null, height = null, minWidth = null, minHeight = null, maxWidth = null, maxHeight = null) {
        if (width) this.setProperty("width", width);
        if (height) this.setProperty("height", height);
        if (minWidth) this.setProperty("min-width", minWidth);
        if (minHeight) this.setProperty("min-height", minHeight);
        if (maxWidth) this.setProperty("max-width", maxWidth);
        if (maxHeight) this.setProperty("max-height", maxHeight);
    }

    attr(id, value = null) {
        if (value === null) {
            return $("." + this.id).attr(id);
        } else {
            $("." + this.id).attr(id, value);
            return true;
        }
    }

    setContent(content, append = false) {
        if (append === true) {
            $("." + this.id).find(".fra-windows-content").append(content);
        } else {
            $("." + this.id).find(".fra-windows-content").html(content);
        }
    }

    setTitlebar(title, append = false) {
        if (append === true) {
            $("." + this.id).find(".titlebar .center-control").append(title);
        } else {
            $("." + this.id).find(".titlebar .center-control").html(title);
        }
    }

    reCalculate(position = true) {
        if ($("." + this.id).outerWidth() > $(window).width()) {
            // $("." + this.id).css("width", $(window).width());
        }
        if ($("." + this.id).outerHeight() > $(window).height()) {
            $("." + this.id).find(".fra-windows-content").css("height", ($(window).height() - $("." + this.id).find(".titlebar").outerHeight()));
        } else {
            $("." + this.id).find(".fra-windows-content").css("height", "");
        }

        if ($("." + this.id).offset().top + ($("." + this.id).outerHeight() / 2) > $(window).outerHeight()) {
            $("." + this.id).css("top", (0 + $(".fra-windows-margin-top").outerHeight()) + "px");
        }
        if ($("." + this.id).offset().left + ($("." + this.id).outerWidth() / 2) > $(window).outerWidth()) {
            $("." + this.id).css("left", "0");
        }

        if (position === true) {
            this.setPosition();
        }
    }

    generateControl(type) {
        let element = "<span class='control ";

        switch (type) {
            case "close":
                element = element + "red btn_close' onclick='allFraWindows[\"" + this.id + "\"].close()'><span style='font-weight: bold; font-family: Arial'>&cross;</span>";
                break;
        }

        element = element + "</span>";

        return element;
    }

    setControl(type, enable = true, position = null) {
        let element;

        switch (type) {
            case "close":
                if (position === null) position = "right";
                element = $("." + this.id).find(".titlebar").find("." + position + "-control");
                break;
        }

        if (enable === true) {
            element.append(this.generateControl(type));
        } else {

        }

        let left_width = 0;
        $("." + this.id).find(".titlebar .left-control *").each(function () {
            left_width += $(this).outerWidth();
        });

        let right_width = 0;
        $("." + this.id).find(".titlebar .right-control *").each(function () {
            right_width += $(this).outerWidth();
        });

        $("." + this.id).find(".titlebar").find(".center-control").css("width", "calc(100% - " + (left_width + right_width) + "px)");
    }

    show(effect = null, duration = null) {
        if (effect !== null && (duration === null || !parseInt(duration))) {
            duration = 300;
        }
        if ($('#overlay-' + this.id).length) {
            if (effect === null) {
                $("#overlay-" + this.id).show();
            } else {
                this.show();
                this.hide();
                $("#overlay-" + this.id).fadeIn(duration);
            }
        } else {
            if (effect === "fadeIn") {
                this.show();
                this.hide();
            }
        }

        if (effect === null) {
            $("." + this.id).addClass("visible").show();
        } else {
            $("." + this.id).addClass("visible").hide().fadeIn(duration);
            this.effect = effect;
            this.duration = duration;
        }

        this.reCalculate();
    }

    hide() {
        if ($('#overlay-' + this.id).length) {
            $("#overlay-" + this.id).hide();
        }
        $("." + this.id).removeClass("visible");
    }

    close(removeOverlay = null) {
        if (removeOverlay === null) removeOverlay = true;
        if (removeOverlay) this.setOverlay(false);
        if (this.effect !== null && (this.duration === null || !parseInt(this.duration))) {
            this.duration = 300;
        }

        if (this.effect !== null) {
            $("." + this.id).fadeOut(this.duration).queue(function () {
                $(this).remove();
            });
        } else {
            $("." + this.id).remove();
        }

        document.dispatchEvent(new CustomEvent(this.id + "-close-event", {"detail": this.id + " window has been closed"}));

        delete allFraWindows[this.id];
    }
}

$(window).on('resize', function () {
    for (const i in allFraWindows) {
        let option = true;
        if ($(".fra-windows[fra-window-id='" + allFraWindows[i].getWindowId() + "']").hasClass("dragged")) {
            option = false;
        }
        allFraWindows[i].reCalculate(option);
    }
});

$(document).keydown(function (event) {
    if (event.which === 27 && $('input:focus, textarea:focus, select:focus').length === 0) { // 'esc' closes focused window only if not inside any input
        const focused = FraWindows.getFocused();
        if (focused !== null) FraWindows.getWindow(focused).close();
    }
});