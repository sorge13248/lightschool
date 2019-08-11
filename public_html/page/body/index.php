<div class="welcome center-content background-image">
        <span>
            <?php if (file_exists(__DIR__ . "/../../install/")) { ?>
                <div class="alert alert-danger">
                    <h2>Cartella d'installazione ancora presente</h2>
                    <p>La cartella d'installazione di LightSchool &egrave; ancora presente e questo significa un grossissimo rischio per la sicurezza e per l'integrit&agrave; di LightSchool. Elimina la cartella "install" il prima possibile!</p>
                </div>
            <?php } ?>
            <h1 style="margin-top: 80px"><?php
                echo($this->getVariables("FraLanguage")->get("introduction")); ?></h1>
            <h3 style="max-width: 1500px; margin: 0 auto"><?php echo($this->getVariables("FraLanguage")->get("introduction-description")); ?></h3>
        </span>
</div>

<div id="pageContent">
    <div style="background-color: #FFFFFF" id="what-is">
        <div class="what-is container">
            <div class="row">
                <div class="col-md-2 pc"
                     style="background: url(upload/logo.png) no-repeat top center; background-size: contain; min-height: 50px;">
                </div>
                <div class="col-md-10">
                    <h2 class="title"><img src="upload/logo.png" style="max-width: 64px; margin-right: 10px"
                                           class="mobile"/><?php echo($this->getVariables("FraLanguage")->get("what-is")); ?>
                    </h2>
                    <?php echo($this->getVariables("FraLanguage")->get("what-is-description")); ?>
                </div>
            </div>
        </div>
    </div>

    <div style="background-color: #F6F6F6" id="for-whom">
        <div class="for-whom container">
            <div class="row">
                <div class="col-md-2 pc"
                     style="background: url(upload/for-whom.png) no-repeat top center; background-size: contain; min-height: 50px">
                </div>
                <div class="col-md-10">
                    <h2 class="title"><img src="upload/for-whom.png" style="max-width: 70px; margin-right: 20px"
                                           class="mobile"><?php echo($this->getVariables("FraLanguage")->get("for-whom")); ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <?php echo($this->getVariables("FraLanguage")->get("for-whom-student")); ?>
                </div>
                <div class="col-md-4">
                    <?php echo($this->getVariables("FraLanguage")->get("for-whom-teacher")); ?>
                </div>
                <div class="col-md-4">
                    <?php echo($this->getVariables("FraLanguage")->get("for-whom-everyone")); ?>
                </div>
            </div>
            <div class="row" style="text-align: center">
                <div class="col-md-12">
                    <?php if (CONFIG_SITE["isPreview"]) { ?>
                        <div class="alert alert-warning">
                            <p>LightSchool &egrave; attualmente disponibile come versione di anteprima pubblica, pertanto alcune funzioni potrebbero essere mancanti rispetto a quanto dichiarato sul sito.</p>
                            <p>Quando sar&agrave; disponibile in versione finale, tutte le funzioni saranno presenti.</p>
                        </div>
                    <?php } ?>
                    <p>Vuoi saperne di pi&ugrave;? Consulta la panoramica. <a href="overview" class="button">Panoramica</a></p>
                </div>
            </div>
        </div>
    </div>

    <div id="get-started">
        <?php if ($this->getVariables("fraUserManagement")->isLogged()) {
            ?>
            <div class="get-started container">
                <div class="row">
                    <div class="col-md-2 pc"
                         style="background: url('<?php echo($this->getVariables("currentUser")->profile_picture["url"]); ?>') no-repeat top center; background-size: contain; min-height: 50px">
                    </div>
                    <div class="col-md-10">
                        <h2 class="title"><img src="<?php echo($this->getVariables("currentUser")->profile_picture["url"]); ?>"
                                               style="max-width: 70px; margin-right: 20px; border-radius: 50%"
                                               class="mobile"/><?php echo($this->getVariables("FraLanguage")->get("hello")); ?>,
                            <?php echo($this->getVariables("currentUser")->name); ?></h2>
                    </div>
                </div>
            </div>
            <div class="container">
                <style type="text/css">
                    .content-my {
                        padding-top: 0 !important;
                    }
                </style>
                <?php require_once __DIR__ . "/../head/my/common.php"; ?>
                <?php require_once __DIR__ . "/my/index.php"; ?>
            </div>
        <?php } else { ?>
            <div class="get-started container">
                <div class="row">
                    <div class="col-md-2 pc"
                         style="background: url(upload/get-started.png) no-repeat top center; background-size: contain; min-height: 50px">
                    </div>
                    <div class="col-md-10">
                        <h2 class="title"><img src="upload/get-started.png" style="max-width: 70px; margin-right: 20px"
                                               class="mobile"/><?php echo($this->getVariables("FraLanguage")->get("get-started")); ?>
                        </h2>
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <?php echo($this->getVariables("FraLanguage")->get("get-started-login")); ?>
                        <a href="my/" class="button"
                           style="float: right"><?php echo($this->getVariables("FraLanguage")->get("login")); ?></a>
                    </div>
                    <div class="col-md-6">
                        <?php echo($this->getVariables("FraLanguage")->get("get-started-register")); ?>
                        <a href="my/register" class="button"
                           style="float: right"><?php echo($this->getVariables("FraLanguage")->get("register")); ?></a>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <?php require_once "footer.php"; ?>
</div>