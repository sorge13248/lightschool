<?php
require_once __DIR__ . "/../../../etc/core.php";

if ($fraUserManagement->isLogged()) {
    if (isset($_GET["folder"]) && $_GET["folder"] === "desktop") {
        $pageTitle = "Desktop";
    } else {
        $pageTitle = "Gestore file";
    }

    try {
        require_once(APP_API);
        $appApi = new \FrancescoSorge\PHP\LightSchool\AppApi();

        $headPage = ["my/common.php", "my/cm-file.php"];
        $bodyPage = ["my/menu.php", "my/app/file-manager/folder-view.php", "my/app/file-manager/dialog/share.php", "my/app/file-manager/dialog/rename.php", "my/app/file-manager/dialog/delete.php", "my/app/file-manager/dialog/property.php", "my/app/file-manager/dialog/fav.php", "my/app/project/dialog/project.php"];
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