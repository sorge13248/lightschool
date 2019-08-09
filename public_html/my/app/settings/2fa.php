<?php
require_once __DIR__ . "/../../../etc/core.php";

if ($fraUserManagement->isLogged()) {
    $pageTitle = "<span class='pc-md'>Impostazioni &rtrif; Sicurezza &rtrif; </span>Autenticazione a 2 Fattori";

    $headPage = ["my/common.php"];
    $bodyPage = ["my/menu.php", "my/app/settings/2fa-view.php"];
} else {
    $pageTitle = "Accedi";
    $headPage = ["common.php"];
    $bodyPage = ["my/login.php"];

}
require_once __DIR__ . "/../../../controller/page.php";