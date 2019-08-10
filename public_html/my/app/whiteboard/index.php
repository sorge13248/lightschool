<?php
require_once __DIR__ . "/../../../etc/core.php";

$pageTitle = "WhiteBoard";

try {
    if ($fraUserManagement->isLogged()) {
        require_once(APP_API);
        $appApi = new \FrancescoSorge\PHP\LightSchool\AppApi();
    }

    $headPage = ["my/common.php"];
    $bodyPage = [];
    if ($fraUserManagement->isLogged()) {
        array_push($bodyPage, "my/menu.php");
    }
    array_push($bodyPage, "my/app/whiteboard/main.php");
} catch (\FrancescoSorge\PHP\LightSchool\AppNotPurchased $e) {
    $headPage = ["my/common.php"];
    $bodyPage = ["my/menu.php", "my/app-not-purchased.php"];
}

require_once __DIR__ . "/../../../controller/page.php";