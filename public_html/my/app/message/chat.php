<?php
require_once __DIR__ . "/../../../etc/core.php";

if ($fraUserManagement->isLogged()) {
    $pageTitle = "<span class='pc-md'>Messaggi &rtrif; </span><span class='name_and_surname'>Conversazione</span>";
    if (isset($_GET["min"]) && $_GET["min"] === "1") {
        $bodyPage = ["my/app/message/chat-view.php"];
    } else {
        $headPage = ["my/common.php"];
        $bodyPage = ["my/menu.php", "my/app/message/common.php", "my/app/message/chat-view.php", "my/app/contact/dialog/create.php"];
    }
} else {
    $pageTitle = "Accedi";
    $headPage = ["common.php"];
    $bodyPage = ["my/login.php"];

}
require_once __DIR__ . "/../../../controller/page.php";