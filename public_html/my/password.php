<?php
require_once __DIR__ . "/../etc/core.php";

if ($fraUserManagement->isLogged()) {
    header("location: index");
} else {
    $pageTitle = "Password dimenticata";
    $headPage = ["common.php"];
    $bodyPage = ["my/password.php"];

}
require_once __DIR__ . "/../controller/page.php";