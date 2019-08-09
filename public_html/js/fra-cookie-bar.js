/*class FraCookieBar {
    constructor(title, text) {
        this.title = title;
        this.text = text;
    }

    show() {
        this.cookieWindow = new FraWindows("cookie", this.title, this.text);

        this.cookieWindow.setTitlebarPadding(10, 0, 10, 0);
        this.cookieWindow.setContentPadding(20);
        this.cookieWindow.setSize("100%");
        this.cookieWindow.setProperty("max-width", "1300px");
        this.cookieWindow.setProperty("max-height", "100vh");
        this.cookieWindow.setDraggable();
        this.cookieWindow.setOverlay();
        this.cookieWindow.setContent("<br/><br/><div style='text-align: right'><button onclick='cookieBar.exit();'>" + lang.get("cookie-bar-exit") + "</button><button onclick='cookieBar.accept();'>" + lang.get("cookie-bar-accept") + "</button></div>", true);

        this.cookieWindow.setPosition();
        this.cookieWindow.show("fadeIn", 300);
    }

    exit() {
        const cookies = document.cookie.split(";");

        for (var i = 0; i < cookies.length; i++) {
            const cookie = cookies[i];
            const eqPos = cookie.indexOf("=");
            const name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
            document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
        }

        window.location.href = "http://www.google.com/";
    }

    accept() {
        const cookie = new FraCookie();
        cookie.set("cookie-bar", "accepted");
        this.cookieWindow.close();
    }
}*/

const lang = new FraJson(ConfigSite.baseURL + "/language/current.php?file=fra-cookie-bar");
// const cookieBar = new FraCookieBar(lang.get("cookie-bar"), lang.get("cookie-bar-description") + "" + lang.get("cookie-bar-dialog"));

if (FraCookie.get("cookie-bar") !== "accepted") {
    $("body").append("<div class='cookie-bar'><h3>" + lang.get("cookie-bar") + "</h3><p>" + lang.get("cookie-bar-dialog") + "</p><button onclick='FraCookie.set(\"cookie-bar\", \"accepted\"); $(this).closest(\".cookie-bar\").fadeOut(200, function() { $(this).remove(); });'>" + lang.get("cookie-bar-accept") + "</button></div>");
}
