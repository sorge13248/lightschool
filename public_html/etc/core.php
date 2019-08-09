<?php
// loads Composer autoload
require_once __DIR__ . "/../vendor/autoload.php";

// Set encoding to utf-8
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// loads config files
require_once __DIR__ . "/../config/site.php"; // loads website config
require_once __DIR__ . '/../config/database.php'; // loads database connection credentials
require_once __DIR__ . '/../config/email.php'; // loads email config

if (CONFIG_SITE["debug"]) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// loads Francesco Sorge's libraries
require_once __DIR__ . '/../model/exception.php';
require_once __DIR__ . '/../model/basic.php';
require_once __DIR__ . "/../model/cookie.php";
require_once __DIR__ . '/../model/errorhandler.php';
require_once __DIR__ . "/../model/page.php";
require_once __DIR__ . "/../model/language.php";
require_once __DIR__ . '/../model/database.php';
require_once __DIR__ . '/../model/menu.php';
require_once __DIR__ . '/../model/usermanagement.php';
require_once __DIR__ . '/../model/email.php';
require_once __DIR__ . '/../model/lightschool-user.php';
require_once __DIR__ . '/../model/keyring.php';
require_once __DIR__ . '/../model/crypto.php';
require_once __DIR__ . '/../model/exception-riser.php';

$fraLanguage = new FrancescoSorge\PHP\Language();

// Authentication system by FrancescoSorge\PHP\LightSchool/UserManagement delight-im/PHP-Auth
session_name('lightschool_session');
$fraUserManagement = new FrancescoSorge\PHP\LightSchool\UserManagement();
if ($fraUserManagement->isLogged()) {
    $currentUser = $fraUserManagement->getCurrentUserInfo(["id", "name", "surname", "username", "profile_picture", "taskbar", "taskbar_size", "accent", "theme", "wallpaper", "email"], ["all_users"]);
    $database = new \FrancescoSorge\PHP\Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

    $currentUser->taskbar = $currentUser->taskbar !== null ? ["base" => explode(",", htmlspecialchars($currentUser->taskbar)), "interpreted" => []] : null;
    if ($currentUser->taskbar !== null) {
        $database->setTableDotField(true);
        foreach ($currentUser->taskbar["base"] as $app) {
            $query = $database->query("SELECT app_catalog.unique_name, app_catalog.name_it FROM app_catalog, app_purchase WHERE app_purchase.user = :user_id AND app_purchase.id = :id AND app_purchase.app = app_catalog.unique_name LIMIT 1", [
                [
                    "name" => "id",
                    "value" => $app,
                    "type" => PDO::PARAM_INT,
                ],
                [
                    "name" => "user_id",
                    "value" => $currentUser->id,
                    "type" => PDO::PARAM_INT,
                ],
            ], "fetchAll");

            if (isset($query[0])) {
                $query = $query[0];
                array_push($currentUser->taskbar["interpreted"], ["id" => $app, "unique-name" => $query['app_catalog.unique_name'], "name" => $query['app_catalog.name_it'], "icon" => CONFIG_SITE['baseURL'] . "/my/app/" . $query['app_catalog.unique_name'] . "/icon/white/icon.png", "icon-black" => CONFIG_SITE['baseURL'] . "/my/app/" . $query['app_catalog.unique_name'] . "/icon/black/icon.png", "link" => CONFIG_SITE['baseURL'] . "/my/app/" . $query['app_catalog.unique_name']]);
            }
        }
        $database->setTableDotField(false);
    }

    if ($currentUser->profile_picture !== null) {
        require_once __DIR__ . "/../my/app/file-manager/model.php";
        if (!\FrancescoSorge\PHP\LightSchool\FileManager::checkOwnership((int)$currentUser->profile_picture)) $currentUser->profile_picture = null;
    }

    if ($currentUser->profile_picture === null) {
        $currentUser->profile_picture = ["id" => null, "url" => CONFIG_SITE["baseURL"] . "/upload/mono/white/user.png"];
    } else {
        $currentUser->profile_picture = ["id" => $currentUser->profile_picture, "url" => CONFIG_SITE["baseURL"] . "/controller/provide-file/" . $currentUser->profile_picture];
    }

    $currentUser->school = $database->query("SELECT school FROM users_school WHERE user = :id", [["name" => "id", "value" => $currentUser->id, "type" => \PDO::PARAM_INT]], "fetchAll");
    foreach ($currentUser->school as $index => $item) {
        $currentUser->school[$index] = $item["school"];
    }

    $currentUser->accent = $currentUser->accent ? "#" . $currentUser->accent : "#1E6BC9";
    $currentUser->accent = ["base" => $currentUser->accent, "lighter" => \FrancescoSorge\PHP\Basic::adjustBrightness($currentUser->accent), 20, "lighter2" => \FrancescoSorge\PHP\Basic::adjustBrightness($currentUser->accent, 80), "darker" => \FrancescoSorge\PHP\Basic::adjustBrightness($currentUser->accent, -20)];


    if ($currentUser->theme !== null) {
        $theme = $database->query("SELECT unique_name, t_icon FROM app_catalog WHERE unique_name = :theme LIMIT 1", [["name" => "theme", "value" => "t-" . $currentUser->theme, "type" => \PDO::PARAM_STR]], "fetchAll");
        $theme = isset($theme[0]) ? $theme[0] : null;
        if ($theme === null) {
            $currentUser->theme = ["unique_name" => null, "icon" => "black"];
        } else {
            $currentUser->theme = ["unique_name" => $currentUser->theme, "icon" => $theme["t_icon"]];
        }
        unset($theme);
    } else {
        $currentUser->theme = ["unique_name" => null, "icon" => "black"];
    }

    if ($currentUser->wallpaper !== null) {
        $currentUser->wallpaper = json_decode($currentUser->wallpaper);
        $wallpaper = $database->query("SELECT id FROM file WHERE id = :id AND user_id = :user_id AND type = 'file' LIMIT 1", [["name" => "id", "value" => $currentUser->wallpaper->id, "type" => \PDO::PARAM_INT], ["name" => "user_id", "value" => $currentUser->id, "type" => \PDO::PARAM_INT]], "fetchAll");
        $wallpaper = isset($wallpaper[0]) ? $wallpaper[0] : null;
        if ($wallpaper === null) {
            $currentUser->wallpaper = null;
        } else {
            $currentUser->wallpaper = ["id" => $currentUser->wallpaper->id, "opacity" => $currentUser->wallpaper->opacity, "color" => $currentUser->wallpaper->color];
        }
        unset($wallpaper);
    }
}

define("CONTROLLER", __DIR__ . "/../controller");
define("APP_API", __DIR__ . "/../my/app/api.php");