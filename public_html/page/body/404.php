<div id="pageContent" class="elementToPadMenu">
    <div class="container">
        <!--<img src="--><?php //echo(CONFIG_SITE["baseURL"]); ?><!--/img/error/404--->
        <?php //echo(rand(1, 4)); ?><!--.png" style="display: block; margin: 0 auto; margin-top: 50px; margin-bottom: 50px; max-width: 100%" />-->
        <h1><?php echo($this->getVariables("FraLanguage")->get("error-404")); ?></h1>
        <h4 style="margin-top: 0; padding-top: 0"><?php echo($this->getVariables("FraLanguage")->get("error-404-description")); ?></h4>
    </div>

    <br/>
    <?php include_once __DIR__ . "/footer.php"; ?>
</div>