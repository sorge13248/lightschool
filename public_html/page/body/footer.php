<?php
require_once __DIR__ . "/../../model/language.php";
$cookie = new FrancescoSorge\PHP\Language("fra-cookie-bar-{$this->getVariables("FraLanguage")->getLanguage()}");
?>
<div style="background-color: #F6F6F6">
    <div class="contact container">
        <div class="row">
            <div class="col-md-6">
                <h4>LightSchool</h4>
                <p>Versione <?php echo(CONFIG_SITE["version"]); ?> <?php if (CONFIG_SITE["isPreview"]) echo("(Anteprima)"); ?></p>
            </div>
            <div class="col-md-6">
                <p style="font-weight: bold"><?php echo($this->getVariables("FraLanguage")->get("footer-useful-links")); ?></p>
                <div class="row">
                    <div class="col-md-6">
                        <p>
                            <a href="<?php echo(CONFIG_SITE["baseURL"]); ?>/tos"><?php echo($this->getVariables("FraLanguage")->get("tos")); ?></a>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p>
                            <a href="<?php echo(CONFIG_SITE["baseURL"]); ?>/privacy"><?php echo($this->getVariables("FraLanguage")->get("privacy-policy")); ?></a>
                        </p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p>
                            <a href="<?php echo(CONFIG_SITE["baseURL"]); ?>/cookie"><?php echo($cookie->get("cookie-bar")); ?></a>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p>
                            <a href="//www.francescosorge.com/#contact"><?php echo($this->getVariables("FraLanguage")->get("contact")); ?></a>
                        </p>
                    </div>
                </div>

                <p><a href="language.php?redirect=<?php echo($this->getVariables("FraBasic")->getURL()); ?>"><img
                                src='<?php echo(CONFIG_SITE["baseURL"]); ?>/language/icon/<?php echo($this->getVariables("FraLanguage")->getLanguage()); ?>.png'
                                style='width: 26px; margin-right: 5px'/><?php echo($this->getVariables("FraLanguage")->get("change-language")); ?>
                    </a></p>
            </div>
        </div>
    </div>
</div>