<?php
// @TODO convert everything in a JSON file so server is less busy in useless job

if (isset($_GET["header"]) && $_GET["header"] === "json") header('Content-type:application/json;charset=utf-8');

$strings = [
    "LANG_NAME" => "English",
    "LANG_INT_NAME" => "English",
    "LANG_AUTHOR" => "Francesco Sorge <contact@francescosorge.com>",
    "LANG_VER" => 1.0,
    "LANG_REV" => 1,

];

echo(json_encode($strings));
