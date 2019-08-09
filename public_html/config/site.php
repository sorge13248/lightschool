<?php
define("CONFIG_SITE", [
    "title" => "LightSchool",
    "version" => 1.0,
    "isPreview" => false,
    "baseURL" => "//{$_SERVER['SERVER_NAME']}",
    "secureDIR" => __DIR__ . "/../../secure",
    "uploadDIR" => __DIR__ . "/../../secure/upload",
    "debug" => false,
]);