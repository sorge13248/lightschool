<div id="pageContent" class="elementToPadMenu">
    <div class="container">
        <h1 style="margin-top: 0; padding-top: 0"><?php echo($this->getVariables("FraLanguage")->get("change-language")); ?></h1>
        <hr/>

        <div style="margin: 0 auto; width: 100%; max-width: 500px">
            <?php
            foreach ($this->getVariables("FraLanguage")->getAll() as $language) {
                $json = $this->getVariables("FraBasic")->getJSON(__DIR__ . "/../../language/{$language}.php");
                ?>
                <a href="<?php echo(CONFIG_SITE["baseURL"]); ?>/controller/set-language.php?lang=<?php echo($language); ?>&redirect=<?php echo($_GET["redirect"]); ?>"
                   class="list list-inline" style="text-align: center; padding-top: 40px; padding-bottom: 40px">
                    <img src="<?php echo(CONFIG_SITE["baseURL"]); ?>/language/icon/<?php echo($language); ?>.png"
                         style="width: 128px; margin-bottom: 10px"/><br/>
                    <b style="font-size: 1.3em"><?php echo($json->LANG_INT_NAME); ?></b><br/>
                    <?php echo($json->LANG_NAME); ?>
                </a>
                <?php
            }
            ?>
        </div>
    </div>

    <?php require_once "footer.php"; ?>
</div>