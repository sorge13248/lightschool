<?php
require_once __DIR__ . "/../etc/core.php";

if ($fraUserManagement->isLogged()) {
    header("location: index");
} else {
    $pageTitle = "Registrati";
    $headPage = ["common.php"];
    $bodyPage = ["my/register.php"];

}
require_once __DIR__ . "/../controller/page.php";