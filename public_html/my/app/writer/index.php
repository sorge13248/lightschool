<?php
require_once __DIR__ . "/../../../etc/core.php";

$pageTitle = "Writer";

try {
    if ($fraUserManagement->isLogged()) {
        require_once(APP_API);
        $appApi = new \FrancescoSorge\PHP\LightSchool\AppApi();
    }

    if (!isset($_GET["id"])) {
        header("location: 0/");
    }
    $_GET["id"] = (isset($_GET["id"]) && $_GET["id"] !== "0" ? $_GET["id"] : null);
    $headPage = ["my/common.php", "my/school-emergency.php", "my/app/writer/common.php"];
    $bodyPage = ["my/app/writer/menu.php", "my/app/writer/main.php"];
} catch (\FrancescoSorge\PHP\LightSchool\AppNotPurchased $e) {
    $headPage = ["my/common.php", "my/school-emergency.php", "my/app/writer/common.php"];
    $bodyPage = ["my/menu.php", "my/app-not-purchased.php"];
}

require_once __DIR__ . "/../../../controller/page.php";