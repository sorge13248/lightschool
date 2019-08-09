<?php
require_once __DIR__ . "/../etc/core.php";

if (isset($_GET["file"])) {
    $file = "{$_GET["file"]}-{$fraLanguage->getLanguage()}";
} else {
    $file = $fraLanguage->getLanguage();
}

header("location: {$file}.php");