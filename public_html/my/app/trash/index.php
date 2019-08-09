<?php
require_once __DIR__ . "/../../../etc/core.php";

if ($fraUserManagement->isLogged()) {
    $pageTitle = "Cestino";

    try {
        require_once(APP_API);
        $appApi = new \FrancescoSorge\PHP\LightSchool\AppApi();

        $headPage = ["my/common.php", "my/cm-file.php"];
        $bodyPage = ["my/menu.php", "my/app/trash/main.php", "my/app/file-manager/dialog/property.php"];
    } catch (\FrancescoSorge\PHP\LightSchool\AppNotPurchased $e) {
        $headPage = ["my/common.php"];
        $bodyPage = ["my/menu.php", "my/app-not-purchased.php"];
    }
} else {
    $pageTitle = "Accedi";
    $headPage = ["common.php"];
    $bodyPage = ["my/login.php"];

}
require_once __DIR__ . "/../../../controller/page.php";