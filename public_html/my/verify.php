<?php
require_once __DIR__ . "/../etc/core.php";

if ($fraUserManagement->isLogged()) {
    header("location: index");
} else {
    $pageTitle = "Attivazione account";
    $headPage = ["common.php"];
    $bodyPage = ["my/verify.php"];

}
require_once __DIR__ . "/../controller/page.php";