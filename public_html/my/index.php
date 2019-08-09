<?php

require_once __DIR__ . "/../etc/core.php";

if ($fraUserManagement->isLogged()) {
    $pageTitle = $fraLanguage->get("welcome-my") . " " . $fraUserManagement->getCurrentUserInfo(["name"], ["users_expanded"])->name;
    $headPage = ["my/common.php", "my/index.php"];
    $bodyPage = ["my/menu.php", "my/index.php", "my/app/file-manager/dialog/property.php"];
} else {
    $pageTitle = "Accedi";
    $headPage = ["common.php"];
    $bodyPage = ["my/login.php"];

}
require_once __DIR__ . "/../controller/page.php";