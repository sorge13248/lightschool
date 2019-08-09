<?php
$userId = $this->getVariables("fraUserManagement")->getCurrentUserInfo(["id"], ["users"])->id;
?>
<script type="text/javascript">
    $(document).on("click", ".list[fileid] img", function (e) {
        e.preventDefault();
        PropertyPanel.show($(this).closest(".list").attr("fileid"));
    });

    $(document).ready(() => {
        const timetable = (new FraJson("<?php echo(CONFIG_SITE['baseURL']); ?>/my/app/timetable/controller?type=get-tomorrow")).getAll();

        if (timetable.length === 0) {
            if ($(".timetable .icon").length === 0) {
                $(".timetable").html("<p style='color: gray'>Nessuna materia domani. Giornata libera? :)</p>");
            }
        } else {
            $(".timetable > *").remove();
            for (const i in timetable) {
                if (timetable[i]["book"] === null) timetable[i]["book"] = "&nbsp;";
                if (timetable[i]["fore"] !== null) timetable[i]["fore"] = "color: #" + timetable[i]["fore"];
                $(".timetable").append("<a href='#' onclick='return false;' fileid=\"" + timetable[i]["id"] + "\" slot='" + timetable[i]["slot"] + "' fore='" + (timetable[i]["fore"] !== null ? timetable[i]["fore"].replace("color: #", "") : "") + "'  class='icon img-change-to-white accent-all box-shadow-1-all' style='display: inline-block; max-width: 300px; width: 100%' title=\"" + timetable[i]["subject"] + (timetable[i]["book"] !== "&nbsp;" ? ": " + timetable[i]["book"] : "") + "\"><span style=\"display: block; font-size: 1.2em; " + timetable[i]["fore"] + "\" class=\"filename text-ellipsis\">" + timetable[i]["subject"] + "</span><span style='display: inline-block; max-width: 100%' class='book text-ellipsis'>" + timetable[i]["book"] + "</span></a>");
            }
        }
    });
</script>
<div class="content content-my">
    <div style="margin: 0 auto; width: 100%; max-width: 1100px; text-align: center">
        <p><b>Citazione</b><br/>
            <?php $quote = $this->getVariables("FraLanguage")->get("quotes")[array_rand($this->getVariables("FraLanguage")->get("quotes"))];
            echo($quote->text . " &bull; <small><i>" . $quote->author . "</i></small>"); ?></p>
        <div class="row">
            <div class="col-md-6">
                <h2>Desktop <a href="<?php echo(CONFIG_SITE['baseURL']); ?>/my/app/desktop/"
                               class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                               style="float: right">Vai &gt;</a></h2>
                <div style="clear: both"></div>
                <div style="text-align: left;">
                    <?php
                    require_once __DIR__ . "/../../../my/app/file-manager/model.php";
                    $desktop = (new \FrancescoSorge\PHP\LightSchool\FileManager())->listFolder("desktop", null, 10);

                    if (count($desktop) === 0) {
                        echo("<p style='color: gray'>Nessun file aggiunto al desktop. Posizionati sopra un file nel File Manager, fai click destro (o tieni premuto) e seleziona \"Aggiungi al Desktop\".</p>");
                    } else {
                        foreach ($desktop as $item) {
                            ?>
                            <a href="<?php echo($item['link']); ?>" fileid="<?php echo($item["id"]); ?>"
                               class='list icon img-change-to-white text-ellipsis accent-all box-shadow-1-all'
                               style='display: inline-block; width: 100%; max-width: 100%; text-align: left; margin-bottom: -10px; font-size: 0.8em'
                               title="<?php echo(htmlspecialchars($item['name'])); ?>"><img
                                        src="<?php echo(htmlspecialchars($item['icon'])); ?>" class="change-this-img"
                                        style="float: left; width: auto; height: 16px; margin-right: 5px; margin-top: 3px; <?php echo($item['style']); ?>"/><span
                                        style="display: block; font-size: 1.2em"
                                        class="text-ellipsis"><?php echo(htmlspecialchars($item['name'])); ?></span></a>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
            <div class="col-md-6">
                <p class="mobile-md"></p>
                <h2>Prossimi eventi <a href="<?php echo(CONFIG_SITE['baseURL']); ?>/my/app/diary/"
                                       class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker"
                                       style="float: right">Vai &gt;</a></h2>
                <div style="clear: both"></div>
                <?php
                require_once __DIR__ . "/../../../my/app/diary/model.php";
                $diary = (new \FrancescoSorge\PHP\LightSchool\Diary())->getIncoming($userId, 10);

                if (count($diary) === 0) {
                    echo("<p style='color: gray'>Nessun evento in agenda entro i prossimi 7 giorni o con priorit&agrave; alta.</p>");
                } else {
                    foreach ($diary["events"] as $item) {
                        $item['name'] = $item['diary_type'] . " di " . $item['name'] . " il " . $this->getVariables("FraBasic")::timestampToHuman($item['diary_date'], "d/m/Y");
                        if ($item['diary_priority'] >= 1) {
                            $priority = "<img src='" . CONFIG_SITE['baseURL'] . "/upload/color/arrow-up.png' style='width: 16px; margin-right: 5px' title='Priorit&agrave; alta' />";
                        } else {
                            $priority = "";
                        }

                        echo("<a href='" . CONFIG_SITE['baseURL'] . "/my/app/reader/diary/{$item['id']}' fileid='{$item['id']}' class='list icon img-change-to-white text-ellipsis accent-all box-shadow-1-all' style='display: inline-block; width: 100%; max-width: 100%; text-align: left; margin-bottom: -10px' title='{$item['name']}'><img src='" . CONFIG_SITE['baseURL'] . "/upload/mono/black/diary.png' class='change-this-img' style='width: 16px; margin-right: 5px' />{$priority}<span>{$item['name']}</span></a>");
                    }
                }
                ?>
            </div>
            <div class="col-md-12">
                <p class="mobile-md"></p>
                <h2>Orario
                    di <?php echo($this->getVariables("FraLanguage")->get("week-days")->{(new DateTime('tomorrow'))->format("N")}); ?>
                    <a href="<?php echo(CONFIG_SITE['baseURL']); ?>/my/app/timetable/"
                       class="button accent-bkg-gradient box-shadow-1-all accent-bkg-all-darker">Vai &gt;</a></h2>
                <div class="timetable">
                    <div class="ph-item">
                        <div class="ph-col-12">
                            <div class="ph-row">
                                <div class="ph-col-6 big"></div>
                                <div class="ph-col-4 empty big"></div>
                                <div class="ph-col-4"></div>
                                <div class="ph-col-8 empty"></div>
                                <div class="ph-col-6"></div>
                                <div class="ph-col-6 empty"></div>
                                <div class="ph-col-12" style="margin-bottom: 0"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>