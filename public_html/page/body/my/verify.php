<?php if (isset($_GET["selector"]) && isset($_GET["token"])) {
    $response = $this->getVariables("fraUserManagement")->emailVerification($_GET["selector"], $_GET["token"]);
} ?>

<div class="welcome center-content background-image login verify"
     style="background-image: url('<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/background.png')">
        <span>
            <div class="content">
                <h1 style="color: #004A7F"><img src="<?php echo(CONFIG_SITE["baseURL"]); ?>/upload/logo.png"
                                                style="width: 64px; height: 64px; margin-right: 10px"/>LightSchool</h1>
                <br/>
                <div class="form-content">
                    <?php if (isset($_GET["selector"]) && isset($_GET["token"])) {
                        if ($response["response"] === "success") {
                            ?>
                            <div class="alert alert-success">
                                Account attivato con successo!<br/><br/>
                                <a href="index.php" class="button">Accedi</a>
                            </div>
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-danger">
                                Impossibile attivare l'account. Riprova ad usare il link che ti &egrave; arrivato via e-mail. Se ancora non funziona, contatta il supporto tecnico.
                            </div>
                            <?php
                        }
                        ?>
                    <?php } else { ?>
                        <div class="alert alert-danger">
                                Non c'&egrave; niente da vedere qui.
                            </div>
                    <?php } ?>
                    <br/>
                    <small><a href="<?php echo(CONFIG_SITE["baseURL"]); ?>">Sito web di LightSchool</a></small>
                </div>
            </div>
        </span>
</div>