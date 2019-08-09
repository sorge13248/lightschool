<?php
if (!isset($headPage)) $headPage = $bodyPage;

$variablesToPages["FraLanguage"] = &$fraLanguage; // will have to provide a limited class without setters.
$variablesToPages["fraUserManagement"] = &$fraUserManagement; // will have to provide a limited class for only user information gathering
$variablesToPages["FraBasic"] = new \FrancescoSorge\PHP\Basic();
$variablesToPages["pageTitle"] = isset($pageTitle) ? $pageTitle : null;

require_once __DIR__ . "/../view/page.php";