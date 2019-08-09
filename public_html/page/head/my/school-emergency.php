<script type="text/javascript">
    const schoolEmergency = (schools) => {
        if ($(".emergency").length === 0 && new FraCookie().get("schoolEmergencyOk") === null) {
            $("body").append("<div class='emergency center-content'><span><img src='<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/mono/white/warning.png'><br/><span class='texts'></span></span></div>");
            for (const i in schools) {
                $(".emergency span span.texts").append("<br/><h1>" + schools[i]["name"] + "</h1>" + schools[i]["emergency_text"]);
            }
            $(".emergency span:not(.texts)").append("<br/><a href='#' class='button close-emergency'>Chiudi avviso</a><br/><small>Non mostrare per i prossimi 10 minuti</small>");
            $(".emergency").fadeIn(400).css('display', 'table');
            $("html").css("overflow-y", "hidden");

            /*for (let i = 0; i < 100; i++ ) {
                $(".emergency").animate({backgroundColor: "#970000"}, 600).animate({ backgroundColor: "#f00"}, 600);
            }*/
        } else {
            $(".emergency span span.texts").html("");
            for (const i in schools) {
                $(".emergency span span.texts").append("<br/><h1>" + schools[i]["name"] + "</h1>" + schools[i]["emergency_text"]);
            }
        }
    };

    $(document).on("click", ".emergency .close-emergency", function (e) {
        e.preventDefault();
        $(".emergency").fadeOut(400);
        $("html").css("overflow-y", "auto");

        let now = new Date();
        const minutes = 10;
        now.setTime(now.getTime() + (minutes * 60 * 1000));

        new FraCookie().set("schoolEmergencyOk", "1", now.toUTCString());
    });

    $(document).ready(function () {
        if (false) { // @TODO Deactivated for performance reasons
            setInterval(function () {
                const results = (new FraJson("<?php echo(CONFIG_SITE["baseURL"]); ?>/controller/get-emergency.php")).getAll();
                if (results.length > 0) schoolEmergency(results);
                else {
                    if ($(".emergency").length !== 0) {
                        $(".emergency").fadeOut(400);
                        $("html").css("overflow-y", "auto");
                    }
                }
            }, 1000);
        }
    });
</script>