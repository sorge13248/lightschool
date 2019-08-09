/**
 * @author Francesco Sorge <contact@francescosorge.com>
 * @link http://docs.francescosorge.com/
 */

let allFraContextMenu = new Object();

class FraContextMenu {

    constructor(id) {
        this.id = id;
        this.selector = ".fra-context-menu[fra-context-menu='" + id + "']";

        allFraContextMenu[this.id] = this;
    }

    static contextExists(id) {
        return $(".fra-context-menu[fra-context-menu='" + id + "']").length;
    }

    static getContext(id) {
        return allFraContextMenu[id];
    }

    static getOpened() {
        const selector = ".fra-context-menu[fra-context-menu]:visible";
        return $(selector).length === 0 ? null : $(selector).attr("fra-context-menu");
    }

    setPosition(x, y) {
        $(this.selector).css({"top": y, "left": x});
    }

    attr(name, value = null) {
        if (value === null) {
            return $(this.selector).attr(name);
        } else {
            return $(this.selector).attr(name, value);
        }
    }

    show(effect = null, duration = null) {
        if (effect !== null && (duration === null || !parseInt(duration))) {
            duration = 300;
        }

        if (effect === null) {
            $(this.selector).show().addClass("visible");
        } else {
            $(this.selector).fadeIn(duration).addClass("visible");
            this.effect = effect;
            this.duration = duration;
        }
    }

    hide() {
        if ($('#overlay-' + this.id).length) {
            $("#overlay-" + this.id).hide();
        }
        if (this.effect !== null) {
            $(this.selector).fadeOut(this.duration).removeClass("visible");
        } else {
            $(this.selector).hide().removeClass("visible");
        }
    }

    close() {
        if (this.effect !== null && (this.duration === null || !parseInt(this.duration))) {
            this.duration = 300;
        }

        if (this.effect !== null) {
            $(this.selector).fadeOut(this.duration).queue(function () {
                $(this).remove();
            });
        } else {
            $(this.selector).remove();
        }

        document.dispatchEvent(new CustomEvent(this.id + "-close-event", {"detail": this.id + " context menu has been closed"}));
    }
}

$(document).on("contextmenu", "*[fra-context-menu]:not(.fra-context-menu)", function (e) {
    e.preventDefault();

    const contextMenu = $(this).attr("fra-context-menu");
    document.dispatchEvent(new CustomEvent(contextMenu + "-cm-open-event", {
        "detail": {
            "id": contextMenu,
            "classList": e.currentTarget.classList,
            "attributes": e.currentTarget.attributes,
            "mouse": [e.pageX, e.pageY]
        }
    }));
});

$(document).on("click", ".fra-context-menu[fra-context-menu][doNotAutoClose!='y'] *", function (e) {
    e.preventDefault();

    const opened = FraContextMenu.getOpened();
    if (opened !== null) allFraContextMenu[opened].hide();
});

$(document).on("click", document, function (e) {
    if ($(e.target).closest('.fra-context-menu').length !== 0) {
        e.preventDefault();
        return false;
    }

    const opened = FraContextMenu.getOpened();
    if (opened !== null) allFraContextMenu[opened].hide();
});