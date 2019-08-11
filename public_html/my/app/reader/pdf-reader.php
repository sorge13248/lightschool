<?php
require_once __DIR__ . "/../../../etc/core.php";

    $pageTitle = "Lettore";

try {
    if ($fraUserManagement->isLogged()) {
        require_once(APP_API);
        $appApi = new \FrancescoSorge\PHP\LightSchool\AppApi();
    }

    $_GET["id"] = (isset($_GET["id"]) && $_GET["id"] !== "" ? $_GET["id"] : null);
    $hideWallpaper = true;
    $headPage = ["my/common.php", "my/school-emergency.php", "my/app/reader/common.php"];
    $bodyPage = ["my/app/reader/pdf.php"];
} catch (\FrancescoSorge\PHP\LightSchool\AppNotPurchased $e) {
    $headPage = ["my/common.php", "my/school-emergency.php", "my/app/reader/common.php"];
    $bodyPage = ["my/menu.php", "my/app-not-purchased.php"];
}

require_once __DIR__ . "/../../../controller/page.php";