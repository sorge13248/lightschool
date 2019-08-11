<?php
if (file_exists(__DIR__ . "/../config/site.php")) {
    header("location: ../");
}
$doInstall = true;
require_once __DIR__ . "/../etc/core.php";

function writeToFile($file, $content) {
    $myfile = fopen($file, "w");
    if (!$myfile) {
        throw new Exception('File open failed.');
    }

    fwrite($myfile, $content);
    fclose($myfile);
}

function rmr($directory)
{
    foreach(glob("{$directory}/*") as $file)
    {
        if(is_dir($file)) {
            rmr($file);
        } else {
            unlink($file);
        }
    }
    rmdir($directory);
}

header('Content-type:application/json;charset=utf-8');
$response = [];

try {
    $database = new \FrancescoSorge\PHP\Database(new \PDO("mysql" . ":host=" . $_POST["host"] . ";dbname=" . $_POST["database"] . ";charset=utf8mb4", $_POST["username"], $_POST["password"]));
} catch(\Exception $e) {
    $response["response"] = "error";
    $response["text"] = $e->getMessage();
    die(json_encode($response));
}

$charset = $database->query("SHOW VARIABLES LIKE \"character_set_database\"", null, "fetchAll");
if ($charset[0]["Value"] !== "utf8mb4") {
    $response["response"] = "error";
    $response["text"] = "Il charset del database non &egrave; impostato su utf8mb4.";
    die(json_encode($response));
}

$tables = $database->query("SHOW TABLES", null, "fetchAll");
if (count($tables) > 0) {
    $response["response"] = "error";
    $response["text"] = "Il database non &egrave; vuoto. Seleziona un database vuoto o svuota quello scelto per continuare.";
    die(json_encode($response));
}

try {
    $database->query(file_get_contents("database.sql"));
} catch(\Exception $e) {
    $response["response"] = "error";
    $response["text"] = $e->getMessage();
    die(json_encode($response));
}

// Generate AES secret key
try {
    writeToFile(__DIR__."/../config/secret.key", (\Defuse\Crypto\Key::createNewRandomKey())->saveToAsciiSafeString());
} catch(\Exception $e) {
    $response["response"] = "error";
    $response["text"] = $e->getMessage();
    die(json_encode($response));
}

// site.php
try {
    $url = addslashes($_POST["url"]);
    $secure = addslashes($_POST["secure"]);
    $upload = addslashes($_POST["upload"]);
    $content = <<<CONTENT
<?php
define("CONFIG_SITE", [
    "title" => "LightSchool",
    "version" => 1.0,
    "isPreview" => false,
    "baseURL" => "{$url}",
    "secureDIR" => "{$secure}",
    "uploadDIR" =>"{$upload}",
    "debug" => false,
]);
CONTENT;

    writeToFile(__DIR__."/../config/site.php", $content);
} catch(\Exception $e) {
    $response["response"] = "error";
    $response["text"] = $e->getMessage();
    die(json_encode($response));
}

// site.js
try {
    $content = <<<CONTENT
const ConfigSite = {"name": "LightSchool", "baseURL": "{$url}"};
CONTENT;

    writeToFile(__DIR__."/../config/site.js", $content);
} catch(\Exception $e) {
    $response["response"] = "error";
    $response["text"] = $e->getMessage();
    die(json_encode($response));
}

// database.php
try {
    $host = addslashes($_POST["host"]);
    $database = addslashes($_POST["database"]);
    $username = addslashes($_POST["username"]);
    $content = <<<CONTENT
<?php
define("CONFIG_DATABASE", [
    "driver" => "mysql",
    "host" => "{$host}",
    "dbname" => "{$database}",
    "user" => "{$username}",
    "password" => "{$_POST["password"]}",
    "charset" => "utf8mb4",
]);
CONTENT;

    writeToFile(__DIR__."/../config/database.php", $content);
} catch(\Exception $e) {
    $response["response"] = "error";
    $response["text"] = $e->getMessage();
    die(json_encode($response));
}

// email.php
try {
    $host2 = addslashes($_POST["host2"]);
    $email = addslashes($_POST["email"]);
    $content = <<<CONTENT
<?php
define("CONFIG_EMAIL", [
    "host" => "{$host2}",
    "no-reply" => [
        "name" => "LightSchool",
        "email" => "{$email}",
        "password" => "{$_POST["password2"]}",
    ],
]);
CONTENT;

    writeToFile(__DIR__."/../config/email.php", $content);
} catch(\Exception $e) {
    $response["response"] = "error";
    $response["text"] = $e->getMessage();
    die(json_encode($response));
}

$response["response"] = "success";
$response["text"] = "LightSchool installato correttamente! ";

try {
    rmr(".");
} catch(\Exception $e) {
    $response["text"] .= $e->getMessage();
}

die(json_encode($response));