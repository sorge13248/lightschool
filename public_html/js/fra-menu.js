class FraCollectionMenu {
    constructor(id, elementToPad) {
        this.id = id;
        this.elementToPad = elementToPad;

        const idd = this.id;
        $(document).on("click", "#fra-menu-" + this.id + " .mobile-menu, #fra-menu-mobile-" + this.id + " .mobile-menu", function (e) {
            e.preventDefault();

            if ($("#fra-menu-mobile-" + idd).is(":hidden")) {
                $("#fra-menu-mobile-" + idd).fadeIn(200);
            } else {
                $("#fra-menu-mobile-" + idd).fadeOut(200);
            }
        });

        $(document).on("click", "#fra-menu-mobile-" + this.id + " a", function () {
            $(this).parent().fadeOut(200);
        });
    }

    setPaddingTop() {
        $(this.elementToPad).css("padding-top", ($("#fra-menu-" + this.id).outerHeight() + 20) + "px");
    }
}

const menu = new FraCollectionMenu("menu", ".elementToPadMenu");

window.onload = function () {
    menu.setPaddingTop();
};

window.onresize = function () {
    menu.setPaddingTop();
};