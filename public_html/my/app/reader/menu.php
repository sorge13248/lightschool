<script type="text/javascript">
    $(document).on("mouseover", ".menu-my.top", function () {
        if (!$(this).is("[original-bkg-image]")) {
            $(this).attr("original-bkg-image", $(".menu-my.top.base").css("background-image"));
        }
        if (!$(this).is("[original-box-shadow]")) {
            $(this).attr("original-box-shadow", $(".menu-my.top.base").css("box-shadow"));
        }
        $(this).css({
            "background-image": $(this).attr("original-bkg-image"),
            "color": "white",
            "box-shadow": $(this).attr("original-box-shadow")
        });
    });

    $(document).on("mouseleave", ".menu-my.top", function () {
        $(this).css({"background-image": "none", "box-shadow": "none"});
    });
</script>

<style type="text/css">
    .property-panel {
        padding-top: 70px;
    }
</style>

<div class="menu-my top base" style="display: none"></div>

<div class="menu-my top no-print img-change-to-white"
     style="background-color: rgba(223, 223, 223, 0); background-image: none; box-shadow: none">
    <div class="row">
        <div class="col-sm-6">
            <a href="../../file-manager/" class="back-button"
               style="display: inline-block; padding: 10px 5px 0; float: left"><img
                        src="<?php echo(CONFIG_SITE['baseURL']); ?>/upload/mono/black/back.png" class="change-this-img"
                        title="Indietro"/></a><h5
                    style="font-weight: bold" class="text-ellipsis"><?php echo($this->getVariables("pageTitle")); ?></h5>
        </div>
        <div class="col-sm-6 pc-md commands" style="text-align: right;">
            <!--            <a href="#" style="display: inline-block; padding: 10px 10px" title="Invia un messaggio" class=""><img src="-->
            <?php //echo(CONFIG_SITE['baseURL']); ?><!--/upload/mono/white/message.png" /></a>-->
            <a href="../../../../controller/provide-file?id=" style="display: inline-block; padding: 10px 10px"
               title="Download" class="file download"><img
                        src="<?php echo(CONFIG_SITE['baseURL']); ?>/upload/mono/white/download.png"/></a>
            <a href="<?php echo(CONFIG_SITE['baseURL']); ?>/my/app/writer/n/<?php echo($_GET["id"]); ?>"
               style="display: inline-block; padding: 10px 10px" title="Modifica" class="notebook"><img
                        src="<?php echo(CONFIG_SITE['baseURL']); ?>/upload/mono/white/edit.png"/></a>
            <!--<a href="#" style="display: inline-block; padding: 10px 10px" title="Stampa" class="notebook diary"><img
                        src="<?php /*echo(CONFIG_SITE['baseURL']); */?>/upload/mono/white/print.png"/></a>-->
            <a href="#" style="display: inline-block; padding: 10px 10px" title="Cronologia modifiche"
               class="notebook history"><img
                        src="<?php echo(CONFIG_SITE['baseURL']); ?>/upload/mono/white/history.png"/></a>
            <!--<a href="#" style="display: inline-block; padding: 10px 10px" title="Condividi" class="notebook file diary"><img src="<?php /*echo(CONFIG_SITE['baseURL']); */ ?>/upload/mono/white/share.png" /></a>
            <a href="#" style="display: inline-block; padding: 10px 10px" title="Proietta su LIM" class="notebook diary"><img src="<?php /*echo(CONFIG_SITE['baseURL']); */ ?>/upload/mono/white/lim.png" /></a>
            <a href="#" style="display: inline-block; padding: 10px 10px" title="Aggiungi/Rimuovi dal Desktop" class="notebook diary file"><img src="<?php /*echo(CONFIG_SITE['baseURL']); */ ?>/upload/mono/white/fav.png" /></a>-->
            <a href="#" style="display: inline-block; padding: 10px 10px" title="Propriet&agrave;" class="notebook file"
               onclick="PropertyPanel.show(<?php echo($_GET["id"]); ?>, false); return false;"><img
                        src="<?php echo(CONFIG_SITE['baseURL']); ?>/upload/mono/white/info.png"/></a>
        </div>
    </div>
</div>