<?php
require_once __DIR__ . "/../../../etc/core.php";

$pageTitle = "Project";

try {
    if ($fraUserManagement->isLogged()) {
        require_once(APP_API);
        $appApi = new \FrancescoSorge\PHP\LightSchool\AppApi();
    }

    $headPage = ["my/common.php"];
    $bodyPage = [];
    if ($fraUserManagement->isLogged()) {
        array_push($bodyPage, "my/menu.php");
        array_push($bodyPage, "my/app/project/dialog/stop.php");
    }
    array_push($bodyPage, "my/app/project/main.php");
} catch (\FrancescoSorge\PHP\LightSchool\AppNotPurchased $e) {
    $headPage = ["my/common.php"];
    $bodyPage = ["my/menu.php", "my/app-not-purchased.php"];
}

require_once __DIR__ . "/../../../controller/page.php";