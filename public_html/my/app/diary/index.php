<?php
require_once __DIR__ . "/../../../etc/core.php";

if ($fraUserManagement->isLogged()) {
    $pageTitle = "Diario";

    try {
        require_once(APP_API);
        $appApi = new \FrancescoSorge\PHP\LightSchool\AppApi();

        $headPage = ["my/common.php", "my/cm-file.php"];
        $bodyPage = ["my/menu.php", "my/color-picker.php", "my/app/diary/month-view.php", "my/app/file-manager/dialog/property.php", "my/app/file-manager/dialog/share.php", "my/app/file-manager/dialog/delete.php", "my/app/file-manager/dialog/fav.php", "my/app/timetable/subject-chooser.php"];
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