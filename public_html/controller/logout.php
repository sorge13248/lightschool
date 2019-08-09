<?php
require_once __DIR__ . "/../etc/core.php";

if ($fraUserManagement->isLogged()) {
    $fraUserManagement->logout();
    \FrancescoSorge\PHP\Cookie::delete("drafts");
    header("location: " . CONFIG_SITE["baseURL"] . "/my");
} else {
    http_response_code(403);
}