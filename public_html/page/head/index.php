<title><?php echo(CONFIG_SITE["title"]); ?></title>

<style type="text/css">
    .portfolio-item {
        margin: 20px 0;
    }
</style>

<script type="text/javascript">
    $(document).on("click", ".menu a", function (e) {
        if ($(this).hasClass("title")) {
            if ($(window).scrollTop() !== 0) {
                e.preventDefault();
                $('html,body').unbind().animate({scrollTop: 0}, 'slow');
            }
        }
        if ($(this).attr("goto")) {
            e.preventDefault();
            $('html,body').unbind().animate({scrollTop: $($(this).attr("goto")).offset().top - $(".menu").outerHeight()}, 'slow');
        }
    });

    $(document).on("submit", ".contact-form", function (e) {
        e.preventDefault();
        const form = new FraForm($(this));
        form.lock();

        const data = form.getDomElements("input, textarea", "string");
        const ajax = new FraAjax($(this).attr("action"), $(this).attr("method"), data);

        ajax.execute(function (result) {
            result = ajax.getResult(result);

            if (result["response"] === "success") {
                $(".contact-form .response").slideUp(200);
                $(".contact-form input[type='submit']").val(lang.get("contact-email-sent")).addClass("green");
            } else {
                $(".contact-form .response").html(result["text"]).addClass("alert alert-danger");
                form.unlock();
            }
        });
    });

    $(document).on("click", ".contact-form a[href='privacy.php']", function (e) {
        e.preventDefault();

        const lang = new FraJson("language/current.php");
        const privacyWindow = new FraWindows("privacy-policy", lang.get("privacy-policy"), lang.get("privacy-policy-last-edit") + "<br/><br/>" + lang.get("privacy-policy-text"));
        privacyWindow.setContentPadding(20);
        privacyWindow.setOverlay(true);
        privacyWindow.setSize("100%");
        privacyWindow.setProperty("max-width", "1000px");
        privacyWindow.setProperty("max-height", "40vh");
        privacyWindow.setPosition();
        privacyWindow.show();
        privacyWindow.setControl("close");
    });
</script>