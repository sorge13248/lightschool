<?php
// @TODO convert everything in a JSON file so server is less busy in useless job

if (isset($_GET["header"]) && $_GET["header"] === "json") header('Content-type:application/json;charset=utf-8');

$strings = [
    "loading" => "Caricamento...",
];
echo(json_encode($strings));