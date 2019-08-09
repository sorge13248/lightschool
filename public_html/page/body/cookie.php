<?php
require_once __DIR__ . "/../../model/language.php";
$cookie = new FrancescoSorge\PHP\Language("fra-cookie-bar-{$this->getVariables("FraLanguage")->getLanguage()}");
?>
<div id="pageContent" class="elementToPadMenu">
    <div class="container">
        <h1 style="margin-top: 0; padding-top: 0"><?php echo($cookie->get("cookie-bar")); ?></h1>
        <p><?php echo($cookie->get("cookie-bar-last-edit")); ?></p>
        <hr/>
        <?php echo($cookie->get("cookie-bar-description")); ?>
        <br/><br/>
        <button style="float: right"
                onclick="deleteCookieBar()"><?php echo($cookie->get("cookie-show-message-again")); ?></button>
        <div style="clear: both"></div>
    </div>

    <?php require_once "footer.php"; ?>
</div>