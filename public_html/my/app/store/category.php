<?php
require_once __DIR__ . "/../../../etc/core.php";

if ($fraUserManagement->isLogged()) {
    $pageTitle = "LightStore";

    $headPage = ["my/common.php"];
    $bodyPage = ["my/menu.php", "my/app/store/category-view.php"];
} else {
    $pageTitle = "Accedi";
    $headPage = ["common.php"];
    $bodyPage = ["my/login.php"];

}
require_once __DIR__ . "/../../../controller/page.php";