<?php
require_once __DIR__ . "/../../../etc/core.php";

$pageTitle = "Lettore";

try {
    if ($fraUserManagement->isLogged()) {
        require_once(APP_API);
        $appApi = new \FrancescoSorge\PHP\LightSchool\AppApi();
    }

    $_GET["id"] = (isset($_GET["id"]) && $_GET["id"] !== "" ? $_GET["id"] : null);

    $allowOnce = false;
    if ((bool)\FrancescoSorge\PHP\Cookie::get("temp_google_documents") === true) {
        $allowOnce = true;
        \FrancescoSorge\PHP\Cookie::delete("temp_google_documents");
    }

    $hideWallpaper = true;
    $headPage = ["my/common.php", "my/school-emergency.php", "my/app/reader/common.php"];
    $bodyPage = ["my/app/reader/menu.php", "my/app/reader/main.php", "my/app/file-manager/dialog/property.php", "my/app/reader/dialog/history.php"];
} catch (\FrancescoSorge\PHP\LightSchool\AppNotPurchased $e) {
    $headPage = ["my/common.php", "my/school-emergency.php", "my/app/reader/common.php"];
    $bodyPage = ["my/menu.php", "my/app-not-purchased.php"];
}

require_once __DIR__ . "/../../../controller/page.php";