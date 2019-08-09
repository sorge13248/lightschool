<?php
require_once __DIR__ . "/../etc/core.php";

$fraLanguage->set($_GET["lang"]);
header("location: {$_GET["redirect"]}");