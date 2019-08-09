<script type="text/javascript">
    const load = (type, start = null) => {
        start = start !== null ? start : 0;

        const items = (new FraJson("controller.php?type=get-user-" + type + "&id=<?php echo(urlencode($_GET["id"])); ?>&start=" + start)).getAll();

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

                    let link = "";
                    switch (items[i]["type"]) {
                        case "folder":
                            link = "file-manager";
                            break;
                        case "notebook":
                            link = "reader/notebook";
                            break;
                        case "file":
                            link = "reader/file";
                            break;
                        case "diary":
                            link = "reader/diary";
                            break;
                    }
                    $("." + type + " .items").append("<a href=\"../" + link + "/" + items[i]["fileid"] + "\" class='icon img-change-to-white accent-all box-shadow-1-all' style='display: inline-block' title=\"" + items[i]["name"] + "\"><img src=\"" + items[i]["icon"] + "\" class=\"change-this-img\" style=\"float: left; height: 40px; width: auto\" /><span class=\"filename text-ellipsis\" style=\"display: block; font-size: 1.2em\">" + items[i]["name"] + "</span><small class='second-row'>" + items[i]["second-row"] + "</small></a>");
                }

                recalculateIcons();
            }
        }
    };

    $(document).ready(function () {
        load("shared");
        load("sharing");
    });

    $(document).on("click", ".load-more", function (e) {
        e.preventDefault();

        const section = $(this).closest(".col-md-6").attr("class").split(' ')[1];
        load(section, $("." + section + " .items .icon").length);
    });
</script>

<style type="text/css">
    .items .icon {
        width: auto;
        max-width: 100%;
    }
</style>

<div class="container content-my share">
    <?php
    $user = \FrancescoSorge\PHP\LightSchool\User::get(["name", "surname"], (int)$_GET["id"]);
    ?>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../" class="accent-fore accent-fore-darker-all"
                                           style="text-decoration: none">Condivisioni</a></li>
            <li class="breadcrumb-item active"
                aria-current="page"><?php echo($user["name"] . " " . $user["surname"]); ?></li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-6 shared">
            <h3>Condivisi con me</h3>

            <p style='color: gray' class="search-no-result">Nessun risultato cercando '<span
                        class="searched-text"></span>'.</p>

            <div class="items">
                <p class="loading">Caricamento...</p>
            </div>

            <div style="text-align: center">
                <a href="#" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker load-more">Mostra
                    pi&ugrave; elementi</a>
            </div>
        </div>
        <div class="col-md-6 sharing">
            <h3>Cosa sto condividendo</h3>

            <p style='color: gray' class="search-no-result">Nessun risultato cercando '<span
                        class="searched-text"></span>'.</p>

            <div class="items">
                <p class="loading">Caricamento...</p>
            </div>

            <div style="text-align: center">
                <a href="#" class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker load-more">Mostra
                    pi&ugrave; elementi</a>
            </div>
        </div>
    </div>
</div>