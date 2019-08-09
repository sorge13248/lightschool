<script type="text/javascript">
    const load = (type, start = null) => {
        start = start !== null ? start : 0;

        const items = (new FraJson("controller.php?type=get-" + type + "&start=" + start)).getAll();
        if (items["response"] === "error") {
            $("." + type).html("<div class='alert alert-danger'><h4>Errore</h4><p>" + items["text"] + "</p></div>");
        } else {
            if (items.length === 0) {
                if ($("." + type + " .items .icon").length === 0) {
                    $("." + type + " .items").html("<p style='color: gray'>Non stai condividendo nessun elemento.</p>");
                }
                $("." + type + " .load-more").remove();
            } else {
                $("." + type + " .items .loading").remove();
                if (items.length < 20) $("." + type + " .load-more").remove();
                $("." + type + " .load-more").show();

                for (const i in items) {
                    let element = "elementi";
                    if (parseInt(items[i]["count"]) === 1) {
                        element = "elemento";
                    }
                    $("." + type + " .items").append("<a href=\"" + items[i]["userid"] + "\" class='icon img-change-to-white accent-all box-shadow-1-all' style='display: inline-block' title=\"" + items[i]["name"] + "\"><img src=\"" + items[i]["profile_picture"] + "\" class=\"change-this-img\" style=\"float: left\" /><span style=\"display: block; font-size: 1.2em\" class=\"text-ellipsis\">" + items[i]["name"] + " " + items[i]["surname"] + "<span style='display: none'>&nbsp;(</span></span>" + items[i]["count"] + " " + element + "<span style='display: none'>)</span></a>");
                }

                recalculateIcons();
            }
        }
    };

    $(document).ready(function () {
        load("all");
    });

    $(document).on("click", ".load-more", function (e) {
        e.preventDefault();

        const section = $(this).closest(".share .section").attr("class").split(' ')[1];
        load(section, $("." + section + " .items .icon").length);
    });
</script>

<div class="container content-my share">
    <div class="section all">
        <p style='color: gray' class="search-no-result">Nessun risultato cercando '<span class="searched-text"></span>'.
        </p>

        <div class="items">
            <p class="loading">Caricamento...</p>
        </div>

        <div style="text-align: center">
            <a href="#" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker load-more">Mostra pi&ugrave;
                elementi</a>
        </div>
    </div>
</div>