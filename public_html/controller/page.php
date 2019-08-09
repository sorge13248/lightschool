<?php
if (!isset($headPage)) $headPage = $bodyPage;

$variablesToPages["FraLanguage"] = &$fraLanguage;
$variablesToPages["fraUserManagement"] = &$fraUserManagement;
$variablesToPages["currentUser"] = &$currentUser;
$variablesToPages["FraBasic"] = new \FrancescoSorge\PHP\Basic();
$variablesToPages["pageTitle"] = isset($pageTitle) ? $pageTitle : null;
$variablesToPages["allowOnce"] = isset($allowOnce) ? $allowOnce : null;

if (isset($_GET["min"]) && $_GET["min"] === "1") {
    require_once __DIR__ . "/../view/min.php";
} else {
    require_once __DIR__ . "/../view/page.php";
}