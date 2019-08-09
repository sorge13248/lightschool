<style type="text/css">
    .settings .sections .icon span {
        display: inline-block;
        padding-bottom: 5px;
    }
</style>

<div class="container content-my settings" style="padding-left: 0; padding-right: 0; padding-top: 0px">
    <?php
    $account = $database->query("SELECT plan.disk_space, users.id FROM users, (SELECT disk_space FROM users_expanded, plan WHERE users_expanded.id = :user_id AND users_expanded.plan = plan.id LIMIT 1) AS plan WHERE users.id = :user_id LIMIT 1", [
        [
            "name" => "user_id",
            "value" => $this->getVariables("fraUserManagement")->getCurrentUserInfo(["id"], ["users"])->id,
            "type" => \PDO::PARAM_INT,
        ],
    ], "fetchAll");

    $account = (isset($account[0]) ? $account[0] : null);

    if ($account === null) {
        echo("<h3>Something went very bad...</h3>");
        echo("<p>We need you to logout and login again in order to fix this error. Please, keep in mind that this should have not happened and if you do not logout now something worst may happen.</p>");
        if ($this->getVariables("fraUserManagement")->logout()) {
            echo("<p>We logged you out automatically. Refresh this page to login again.</p>");
        } else {
            echo("<p style='color: red; font-weight: bold'>We tried to force logout, but system responded unexpectedly. We suggest you to clean your browser cache and cookie.</p>");
        }
        die();
    } else {
        require_once __DIR__ . "/../../../model/filemanager.php";
        try {
            $mainUserDir = new \FrancescoSorge\PHP\FileManager\Folder(CONFIG_SITE["uploadDIR"] . "/" . md5($account['users.id']) . "/");
            $usedSpace = $mainUserDir->size("mb", false);
        } catch (Exception $e) {
            $usedSpace = 0;
        }
        $disk_space_string = str_replace(['$USED_SPACE', '$TOTAL_SPACE'], [$usedSpace, $account['plan.disk_space']], 'Stai utilizzando $USED_SPACE mb di spazio su $TOTAL_SPACE mb.');
        $disk_space_warning = "";
        if ($usedSpace >= $account["plan.disk_space"]) {
            $disk_space_warning = "<br/><p class='alert alert-danger' style='max-width: 800px; display: inline-block'>Hai esaurito completamente lo spazio di archiviazione a tua disposizione. Le funzionalit&agrave; di LightSchool saranno limitate finch&eacute; non farai spazio.<br/>Inoltre facciamo periodicamente pulizia e rimuoveremo arbitrariamente quanti file necessari a far scendere il valore sotto la soglia.</p>";
        } else if ($usedSpace + ($account["plan.disk_space"] / 10) >= $account["plan.disk_space"]) {
            $disk_space_warning = "<br/><p class='alert alert-warning' style='max-width: 800px; display: inline-block'>Hai quasi finito lo spazio di archiviazione a tua disposizione. Ti consigliamo di far pulizia dei file meno importanti o che non usi pi&ugrave;.</p>";
        }
        $disk_space_width = $usedSpace * 100 / $account["plan.disk_space"];
        ?>
        <div class="header">
            <div class="row">
                <div class="col-md-4">
                    <img src="<?php echo($this->getVariables("currentUser")->profile_picture["url"]); ?>"
                         style="width: 120px; height: 120px; border-radius: 50%; margin-right: 10px; margin-top: 16px"/>
                </div>
                <div class="col-md-8" style="text-align: left">
                    <h1><?php echo(htmlspecialchars($this->getVariables("currentUser")->name)); ?> <?php echo(htmlspecialchars($this->getVariables("currentUser")->surname)); ?></h1>
                    <p><?php echo($disk_space_string); ?></p>
                    <div class="bar" style="width: 100%; max-width: 300px; background-color: white; margin-left: 5px">
                        <span class=""
                              style="width: <?php echo($disk_space_width); ?>%; background-color: <?php echo($this->getVariables("currentUser")->accent["base"]); ?>">&nbsp;</span>
                    </div>
                </div>
                <div class="col-md-12">
                    <span style="margin: 0 auto"><?php echo($disk_space_warning); ?></span>
                </div>
            </div>
        </div>
        <div class="sections">
            <p style='color: gray' class="search-no-result">Nessun risultato cercando '<span
                        class="searched-text"></span>'.</p>
            <a href="account" class="icon img-change-to-white accent-all box-shadow-1-all">
                <img src="../../../upload/mono/black/user.png" class="change-this-img"><br/>
                <span>Account</span><br/>
                <small class="second-row text-ellipsis" style="display: block">Email, nome utente e foto profilo<br/>&nbsp;</small>
            </a>
            <a href="app" class="icon img-change-to-white accent-all box-shadow-1-all">
                <img src="../../../upload/mono/black/app.png" class="change-this-img"><br/>
                <span>App</span><br/>
                <small class="second-row text-ellipsis" style="display: block">Aggiungi/Rimuovi app dal menu di avvio
                    o<br/>cancella i dati</small>
            </a><br/>
            <a href="customize" class="icon img-change-to-white accent-all box-shadow-1-all">
                <img src="../../../upload/mono/black/brush.png" class="change-this-img"><br/>
                <span>Personalizza</span><br/>
                <small class="second-row text-ellipsis" style="display: block">Colore preferito, foto profilo, sfondo
                    e<br/>taskbar</small>
            </a>
            <a href="security" class="icon img-change-to-white accent-all box-shadow-1-all">
                <img src="../../../upload/mono/black/lock.png" class="change-this-img"><br/>
                <span>Sicurezza</span><br/>
                <small class="second-row text-ellipsis" style="display: block">Chiavi RSA, autenticazione a 2
                    fattori,<br/> password e privacy</small>
            </a>
            <!--<a href="security" class="icon img-change-to-white accent-all box-shadow-1-all">
                <img src="../../../upload/mono/black/third-parties.png" class="change-this-img"><br/>
                <span>Servizi di terze parti</span><br/>
                <small class="second-row text-ellipsis" style="display: block">Google Documents<br/>&nbsp;</small>
            </a>-->
        </div>
    <?php } ?>
</div>