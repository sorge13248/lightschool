/**
 * @author Francesco Sorge <contact@francescosorge.com>
 * @link http://docs.francescosorge.com/
 */

let allFraNotifications = new Object();
let FraNotificationsTop = 20;

class FraNotifications {

    constructor(id, content) {
        this.id = id;
        this.selector = ".fra-notifications[fra-notification='" + id + "']";

        $("body").append("<div class='fra-notifications " + this.id + "' fra-notification='" + this.id + "'>" + content + "</div>");

        allFraNotifications[this.id] = this;
    }

    static calculateAllHeight() {
        let height = 0;
        $(".fra-notifications:visible").each(function () {
            height += $(this).outerHeight() + 20;
        });
        return height;
    }

    static moveUp(selector = null, px = null) {
        if (selector === null) selector = 0;
        if (px === null) px = 80;

        $(".fra-notifications:visible").each(function () {
            if ($(this).offset().top > selector) $(this).animate({"top": ($(this).offset().top - px) + "px"}, 300);
        });
    }

    static moveDown(selector = null, px = null) {
        if (selector === null) selector = 0;
        if (px === null) px = 80;

        $(".fra-notifications:visible").each(function () {
            if ($(this).offset().top > selector) $(this).animate({"top": ($(this).offset().top + px) + "px"}, 300);
        });
    }

    static notificationExists(id) {
        return $(".fra-notifications[fra-notification='" + id + "']").length;
    }

    static getOpened() {
        const selector = ".fra-notifications[fra-notification]:visible";
        return $(selector).length === 0 ? null : $(selector).attr("fra-notification");
    }

    attr(name, value = null) {
        if (value === null) {
            return $(this.selector).attr(name);
        } else {
            return $(this.selector).attr(name, value);
        }
    }

    show() {
        FraNotifications.moveDown();
        $(this.selector).fadeIn(300).addClass("visible");
    }

    hide() {
        $(this.selector).removeClass("visible");
    }

    close() {
        const offset = $(this.selector).offset().top;
        $(this.selector).fadeOut(300).queue(function () {
            FraNotifications.moveUp(offset);
            $(this).remove();
        });

        document.dispatchEvent(new CustomEvent(this.id + "-close-event", {"detail": this.id + " notification has been closed"}));
    }

    setType(type) {
        $(this.selector).addClass(type);
    }

    setZIndex(index) {
        $(this.selector).css("z-index", index);
    }

    setAutoClose(timeout = null) {
        if (timeout === false) {
            clearTimeout(this.autoClose);
        } else {
            timeout = parseInt(timeout) ? parseInt(timeout) : 5000;
            const self = this;
            this.autoClose = setTimeout(function () {
                self.close();
            }, timeout);
        }
    }
}

$(document).on("click", ".fra-notifications[fra-notification]", function (e) {
    e.preventDefault();

    allFraNotifications[$(this).attr("fra-notification")].close();
});