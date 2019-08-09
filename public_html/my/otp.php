<?php
require_once __DIR__ . "/../etc/core.php";

if ($fraUserManagement->isLogged()) {
    header("location: index");
} else {
    $pageTitle = "OTP perso";
    $headPage = ["common.php"];
    $bodyPage = ["my/otp.php"];

}
require_once __DIR__ . "/../controller/page.php";