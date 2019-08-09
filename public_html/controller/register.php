<?php
require_once __DIR__ . "/../etc/core.php";

if (!$fraUserManagement->isLogged()) {
    header('Content-type:application/json;charset=utf-8');

    $name = isset($_POST["name"]) ? $_POST["name"] : null;
    $surname = isset($_POST["surname"]) ? $_POST["surname"] : null;
    $username = isset($_POST["username"]) ? $_POST["username"] : null;
    $email = isset($_POST["email"]) ? $_POST["email"] : null;
    $password = isset($_POST["password"]) ? $_POST["password"] : null;
    $password_2 = isset($_POST["password-2"]) ? $_POST["password-2"] : null;

    $response = $fraUserManagement->register($name, $surname, $email, $username, $password, $password_2);

    echo(json_encode($response));
} else {
    http_response_code(403);
}