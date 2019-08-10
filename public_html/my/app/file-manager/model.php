<?php

namespace FrancescoSorge\PHP\LightSchool {

    use FrancescoSorge\PHP\Basic;
    use FrancescoSorge\PHP\Cookie;
    use FrancescoSorge\PHP\Database;

    final class FileManager {
        private $userManagement, $database;

        public function __construct () {
            global $fraUserManagement;


            $this->userManagement = &$fraUserManagement;
            $this->database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));
        }

        public static function getHumanSize ($size, $decimals = 2) {
            $sz = 'BKMGTP';
            $factor = floor((strlen($size) - 1) / 3);
            return sprintf("%.{$decimals}f", $size / pow(1024, $factor)) . @$sz[$factor];
        }

        public static function setBypass ($id, $userid = null) {
            if (!is_int($id)) { // id not valid
                return ["response" => "error", "text" => "invalid_id"];
            }

            global $fraUserManagement;


            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            if (!FileManager::checkOwnership($id, $userid) && !Share::authorized($id, $userid)) {
                return ["response" => "error", "text" => "not_authorized"];
            } else if (Share::authorized($id, $userid)) {
                $userid = (new \FrancescoSorge\PHP\LightSchool\Share())->authorized((int)$id, $userid);
            }

            $result = $database->query("UPDATE file SET bypass = NOW() WHERE id = :id AND user_id = :user_id AND type = 'file'", [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            if ($result === 1) {
                return ["response" => "success", "text" => "ok"];
            } else {
                return ["response" => "error", "text" => "bypass"];
            }
        }

        public static function getOwner($id) {
            if (!is_int($id)) { // id not valid
                return ["response" => "error", "text" => "invalid_id"];
            }

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $owner = $database->query("SELECT user_id FROM file WHERE id = :id AND deleted is NULL LIMIT 1", [
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            return isset($owner[0]) ? $owner[0]["user_id"] : null;
        }

        public static function checkOwnership ($id, $userid = null) {
            if (!is_int($id)) { // id not valid
                return ["response" => "error", "text" => "invalid_id"];
            }

            global $fraUserManagement;

            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $folder = $database->query("SELECT id FROM file WHERE id = :id AND user_id = :user_id AND deleted is NULL LIMIT 1", [
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            return isset($folder[0]);
        }

        public static function move ($id, $folder, $userid = null) {
            if (!is_int($id)) { // id not valid
                return ["response" => "error", "text" => "invalid_id"];
            }
            if (!is_int($folder) && $folder !== null) { // id not valid
                return ["response" => "error", "text" => "invalid_folder"];
            }

            global $fraUserManagement;


            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $database->query("UPDATE file SET folder = :folder WHERE id = :id AND user_id = :user_id AND deleted IS NULL LIMIT 1", [
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "folder",
                    "value" => $folder,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            return ["response" => "success", "text" => "ok"];
        }

        public static function upload ($file, $folder, $userid = null) {
            if (!is_int($folder) && $folder !== null) { // id not valid
                return ["response" => "error", "text" => "invalid_folder"];
            }

            global $fraUserManagement;
            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $file_path = $file['tmp_name'];
            $file_name = $file['name'];
            $file_size = $file['size'];
            $file_type = $file['type'];
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

            $result = $database->query("SELECT id FROM file WHERE user_id = :user_id AND name = :name AND folder " . ($folder === null ? "IS" : "=") . " :folder AND trash = 0 AND deleted IS NULL LIMIT 1", [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "name",
                    "value" => $file_name,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "folder",
                    "value" => $folder,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            if (isset($result[0])) {
                return ["response" => "error", "text" => "already", "file_name" => $file_name];
            }

            $path = CONFIG_SITE["uploadDIR"] . DIRECTORY_SEPARATOR . md5($userid) . DIRECTORY_SEPARATOR . date("Y-m-d");
            if (!file_exists($path)) {
                mkdir($path, 066, true);
            }

            $allowed = ['png', 'jpg', 'jpeg', 'bmp', 'gif', 'tiff', 'mp3', 'mp4', 'mov', 'wav', 'pdf', 'xps', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'accdb', 'odt', 'ods', 'odp', 'odb', 'java', 'class', 'cpp', 'h', 'js', 'html', 'htm', 'css', 'sass', 'scss', 'txt', 'rtf', 'go', 'py'];

            $account = $database->query("SELECT plan.disk_space, users.id FROM users, (SELECT disk_space FROM users_expanded, plan WHERE users_expanded.id = :user_id AND users_expanded.plan = plan.id LIMIT 1) AS plan WHERE users.id = :user_id LIMIT 1", [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            if ($file_size >= 1048576 * $account[0]["disk_space"] || !in_array($file_extension, $allowed)) {
                return ["response" => "error", "text" => "max_or_ext", "file_name" => $file_name];
            } else {
                $completePath = $path . DIRECTORY_SEPARATOR . $file_name;
                if (move_uploaded_file($file_path, $completePath)) {
                    $database->query("INSERT INTO file(user_id, type, name, file_url, file_type, file_size, folder) VALUES (:user_id, 'file', :name, :url, :type, :size, :folder)", [
                    [
                        "name" => "user_id",
                        "value" => $userid,
                        "type" => \PDO::PARAM_INT,
                    ],
                    [
                        "name" => "name",
                        "value" => $file_name,
                        "type" => \PDO::PARAM_STR,
                    ],
                    [
                        "name" => "url",
                        "value" => md5($userid) . DIRECTORY_SEPARATOR . date("Y-m-d") . DIRECTORY_SEPARATOR . $file_name,
                        "type" => \PDO::PARAM_STR,
                    ],
                    [
                        "name" => "type",
                        "value" => $file_type,
                        "type" => \PDO::PARAM_STR,
                    ],
                    [
                        "name" => "size",
                        "value" => $file_size,
                        "type" => \PDO::PARAM_INT,
                    ],
                    [
                        "name" => "folder",
                        "value" => $folder,
                        "type" => \PDO::PARAM_INT,
                    ],
                ], "fetchAll");

                    return ["response" => "success", "text" => "ok"];
                } else {
                    return ["response" => "error", "text" => "move_uploaded_file"];
                }
            }
        }

        public function listFolder ($id = null, $userid = null, $limit = null) {
            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            if ($id !== "trash") {
                if ($limit !== null && is_int($limit) && $limit > 1) {
                    $limit = "LIMIT {$limit}";
                } else {
                    $limit = "";
                }
            }

            if ($id === null) {
                $query = "SELECT id, name, type, icon, file_type, file_url, fav FROM file WHERE user_id = :user_id AND (type = 'folder' OR type = 'notebook' OR type = 'file') AND folder IS NULL AND history IS NULL AND trash = 0 AND deleted IS NULL ORDER BY FIELD(type,'folder','notebook','file'), name {$limit}";
                $param = [
                    [
                        "name" => "user_id",
                        "value" => $userid,
                        "type" => \PDO::PARAM_INT,
                    ],
                ];
            } else if ($id === "desktop") {
                $query = "SELECT id, name, type, icon, diary_type, diary_date, diary_color, file_type, file_url, TRUE as fav FROM desktop WHERE user_id = :user_id AND deleted IS NULL ORDER BY name {$limit}";
                $param = [
                    [
                        "name" => "user_id",
                        "value" => $userid,
                        "type" => \PDO::PARAM_INT,
                    ],
                ];
            } else if ($id === "trash") {
                $limit = $limit !== null ? $limit : 0;
                $query = "SELECT id, name, type, icon, diary_type, diary_date, diary_color, file_type, file_url, fav FROM file WHERE user_id = :user_id AND trash = 1 AND (type = 'folder' OR type = 'notebook' OR type = 'file' OR type = 'diary') AND deleted IS NULL ORDER BY CASE type WHEN 'folder' THEN 1 WHEN 'notebook' THEN 2 WHEN 'file' THEN 2 WHEN 'diary' THEN 2 END, name LIMIT {$limit}, 20";
                $param = [
                    [
                        "name" => "user_id",
                        "value" => $userid,
                        "type" => \PDO::PARAM_INT,
                    ],
                ];
            } else {
                $query = "SELECT id, name, type, icon, diary_type, diary_date, diary_color, file_type, file_url, fav FROM file WHERE user_id = :user_id AND (type = 'folder' OR type = 'notebook' OR type = 'file') AND folder = :folder AND history IS NULL AND trash = 0 AND deleted IS NULL ORDER BY FIELD(type,'folder','notebook','file'), name {$limit}";
                $param = [
                    [
                        "name" => "user_id",
                        "value" => $userid,
                        "type" => \PDO::PARAM_INT,
                    ],
                    [
                        "name" => "folder",
                        "value" => $_GET["folder"],
                        "type" => \PDO::PARAM_INT,
                    ],
                ];
            }

            $items = $this->database->query($query, $param, "fetchAll");

            foreach ($items as $key => &$item) {
                if ($id === "desktop") {
                    $item["fav"] = 1;
                }

                if (isset($item["create_date"])) {
                    $item["create_date"] = Basic::timestampToHuman($item["create_date"]);
                }

                if (isset($item["last_view"])) {
                    $item["last_view"] = Basic::timestampToHuman($item["last_view"]);
                }

                if (isset($item["last_edit"])) {
                    $item["last_edit"] = Basic::timestampToHuman($item["last_edit"]);
                }

                $item["style"] = "";
                $item["link"] = CONFIG_SITE["baseURL"];
                $secondRow = null;

                if ($item["type"] === "folder") {
                    $item["link"] .= "/my/app/file-manager/";
                    $count = $folderView = $this->database->query("SELECT COUNT(id) AS count FROM file WHERE user_id = :user_id AND folder = :folder AND history IS NULL AND trash = 0 AND deleted IS NULL", [
                        [
                            "name" => "user_id",
                            "value" => $userid,
                            "type" => \PDO::PARAM_INT,
                        ],
                        [
                            "name" => "folder",
                            "value" => $item["id"],
                            "type" => \PDO::PARAM_INT,
                        ],
                    ], "fetchAll")[0]["count"];
                    $secondRow = ["text" => "{$count} elementi"];
                } else if ($item["type"] === "notebook") {
                    $item["link"] .= "/my/app/reader/notebook/";
                    $secondRow = ["text" => "Quaderno"];
                } else if ($item["type"] === "file") {
                    $item["link"] .= "/my/app/reader/file/";
                    $secondRow = ["text" => "File"];
                } else if ($item["type"] === "diary") {
                    $item['name'] = $item['diary_type'] . " di " . $item['name'];
                    $item["link"] .= "/my/app/reader/diary/";
                    $secondRow = ["text" => Basic::timestampToHuman($item['diary_date'], "d/m/Y")];
                } else if ($item["type"] === "contact") {
                    $item['name'] = "{$item['name']} {$item['surname']}";
                    $item["link"] .= "/my/app/reader/contact/";
                    $secondRow = ["text" => $item["username"]];
                    $item["style"] = "border-radius: 50%";
                }
                if ($item["icon"] === null) {
                    if ($item["type"] === "file") {
                        if ($item["file_url"] !== null && file_exists(CONFIG_SITE["uploadDIR"] . "/" . $item["file_url"])) {
                            if (strpos($item["file_type"], 'image/') !== false) {
                                $item["style"] = "max-height: 40px; width: auto";
                            }
                        } else {
                            $secondRow = ["text" => "FILE MANCANTE", "style" => "color: red"];
                        }
                    }
                }

                $item["icon"] = self::getIcon($item["id"], $item["name"], $item["icon"], $item["type"], isset($item["file_url"]) ? $item["file_url"] : null, isset($item["file_type"]) ? $item["file_type"] : null);

                if (isset($secondRow)) {
                    $item["secondRow"] = str_replace(["REPLACE", "STYLE"], [$secondRow["text"], isset($secondRow["style"]) ? $secondRow["style"] : ""], "<small class='second-row'>REPLACE</small>");
                } else {
                    $item["secondRow"] = "";
                }

                $item["link"] .= $item["id"];
            }

            return $items;
        }

        public static function getIcon ($id, $name, $icon, $type, $file_url = null, $file_type = null) {
            if ($type === "contact") {
                return $icon === null ? CONFIG_SITE["baseURL"] . "/upload/mono/black/user.png" : $icon;
            }

            if ($icon === null) {
                if ($type === "file") {
                    if (file_exists(CONFIG_SITE["uploadDIR"] . "/" . $file_url) && $file_url !== null) {
                        if (strpos($file_type, 'image/') !== false) {
                            return CONFIG_SITE['baseURL'] . "/controller/provide-file.php?id={$id}";
                        } else {
                            if (strpos($name, '.txt') !== false) {
                                $fileType = "txt";
                            } else if (strpos($file_type, 'pdf') !== false) {
                                $fileType = "pdf";
                            } else if (strpos($name, '.doc') !== false || strpos($name, '.docx') !== false) {
                                $fileType = "word";
                            } else if (strpos($name, '.xls') !== false || strpos($name, '.xlsx') !== false) {
                                $fileType = "excel";
                            } else if (strpos($name, '.ppt') !== false || strpos($name, '.pptx') !== false) {
                                $fileType = "powerpoint";
                            } else if (strpos($file_type, 'vnd.oasis.ope') !== false && strpos($name, '.odt') !== false) {
                                $fileType = "odt";
                            } else if (strpos($file_type, 'vnd.oasis.ope') !== false && strpos($name, '.ods') !== false) {
                                $fileType = "ods";
                            } else if (strpos($file_type, 'vnd.oasis.ope') !== false && strpos($name, '.odp') !== false) {
                                $fileType = "odp";
                            } else {
                                $fileType = "file-unknown";
                            }
                            return CONFIG_SITE['baseURL'] . "/upload/mono/black/{$fileType}.png";
                        }
                    } else {
                        return CONFIG_SITE['baseURL'] . "/upload/mono/black/file-error.png";
                    }
                } else {
                    return CONFIG_SITE['baseURL'] . "/upload/mono/black/{$type}.png";
                }
            } else {
                if ($type !== "contact") {
                    return CONFIG_SITE['baseURL'] . "/upload/color/" . $icon;
                }
            }
        }

        public function createFolder ($name, $folder = null, $userid = null) {
            $name = str_replace(["\\", "/", ":", "*", "?", "\"", "<", ">", "|", "&"], " ", $name); // replaces invalid chars with whitespaces

            if (strlen($name) > 255) { // name too long
                return ["response" => "error", "text" => "too_long"];
            }
            if (strlen($name) === 0) { // name too short
                return ["response" => "error", "text" => "too_short"];
            }

            $folder = $folder === "" ? null : $folder;
            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            if ($folder !== null && !$this->checkOwnership($folder, $userid)) {
                return ["response" => "error", "text" => "not_authorized"];
            }

            $query = "SELECT id FROM file WHERE user_id = :user_id AND name = :name AND folder " . ($folder === null ? "IS NULL" : "= :folder") . " AND deleted IS NULL AND trash = 0 AND type != 'diary' LIMIT 1";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "name",
                    "value" => $name,
                    "type" => \PDO::PARAM_STR,
                ],
            ];

            if ($folder !== null) {
                array_push($param, [
                    "name" => "folder",
                    "value" => $folder,
                    "type" => \PDO::PARAM_INT,
                ]);
            }

            $items = $this->database->query($query, $param, "fetchAll");

            if (isset($items[0])) { // a file already exists with $name
                return ["response" => "error", "text" => "already_exists"];
            }

            $this->database->query("INSERT INTO file(user_id, type, name, folder) VALUES (:user_id, 'folder', :name, :folder)", [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "name",
                    "value" => $name,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "folder",
                    "value" => $folder === null ? null : $folder,
                    "type" => $folder === null ? \PDO::PARAM_NULL : \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            return ["response" => "success", "text" => "folder_created"];
        }

        public function rename ($id, $name, $folder = null, $userid = null) {
            if (!is_int($id)) { // id not valid
                return ["response" => "error", "text" => "invalid_id"];
            }

            $name = str_replace(["\\", "/", ":", "*", "?", "\"", "<", ">", "|", "&"], " ", $name); // replaces invalid chars with whitespaces

            if (strlen($name) > 255) { // name too long
                return ["response" => "error", "text" => "too_long"];
            }
            if (strlen($name) === 0) { // name too short
                return ["response" => "error", "text" => "too_short"];
            }

            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            if ($id !== null && !$this->checkOwnership($id, $userid)) {
                return ["response" => "error", "text" => "not_authorized"];
            }

            if ($folder === "") $folder = null;

            $query = "SELECT id FROM file WHERE user_id = :user_id AND name = :name AND folder " . ($folder === null ? "IS" : "=") . " :folder AND id != :id AND deleted IS NULL AND trash = 0 AND type != 'diary' LIMIT 1";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "name",
                    "value" => $name,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "folder",
                    "value" => $folder,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ];

            $items = $this->database->query($query, $param, "fetchAll");

            if (isset($items[0])) { // a file already exists with $name
                return ["response" => "error", "text" => "already_exists"];
            }

            $query = "SELECT name, file_url FROM file WHERE user_id = :user_id AND id = :id AND deleted IS NULL LIMIT 1";
            $result = $this->database->query($query, [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");
            if (isset($result[0]["file_url"]) && $result[0]["file_url"] !== null) {
                $path = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "my" . DIRECTORY_SEPARATOR . "user" . DIRECTORY_SEPARATOR . (str_replace($result[0]["name"], "", $result[0]["file_url"]));
                rename($path . $result[0]["name"], $path . $name);

                $file_url = str_replace($result[0]["name"], $name, $result[0]["file_url"]);
                $this->database->query("UPDATE file SET file_url = :url WHERE id = :id AND user_id = :user_id", [
                    [
                        "name" => "user_id",
                        "value" => $userid,
                        "type" => \PDO::PARAM_INT,
                    ],
                    [
                        "name" => "url",
                        "value" => $file_url,
                        "type" => \PDO::PARAM_STR,
                    ],
                    [
                        "name" => "id",
                        "value" => $id,
                        "type" => \PDO::PARAM_INT,
                    ],
                ], "fetchAll");

            }

            $this->database->query("UPDATE file SET name = :name WHERE id = :id AND user_id = :user_id", [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "name",
                    "value" => $name,
                    "type" => \PDO::PARAM_STR,
                ],
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            return ["response" => "success", "text" => "renamed"];
        }

        public function delete ($id, $type = null, $userid = null) {
            if (!is_int($id)) { // id not valid
                return ["response" => "error", "text" => "invalid_id"];
            }
            if ($type === null) {
                $type = "move_to_trash";
            }

            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $file = $this->database->query("SELECT id, type, file_url FROM file WHERE id = :id AND user_id = :user_id LIMIT 1", [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            if (isset($file[0])) {
                if ($type === "delete_completely") {
                    $query = "UPDATE file SET deleted = NOW() WHERE user_id = :user_id AND id = :id AND deleted IS NULL LIMIT 1";
                } else {
                    $query = "UPDATE file SET trash = 1 WHERE user_id = :user_id AND id = :id AND deleted IS NULL LIMIT 1";
                }

                $this->database->query($query, [
                    [
                        "name" => "user_id",
                        "value" => $userid,
                        "type" => \PDO::PARAM_INT,
                    ],
                    [
                        "name" => "id",
                        "value" => $id,
                        "type" => \PDO::PARAM_INT,
                    ],
                ], "fetchAll");

                if ($type === "delete_completely" && $file[0]["type"] === "file" && $file[0]["file_url"]) {
                    $path = CONFIG_SITE["uploadDIR"] . DIRECTORY_SEPARATOR;
                    unlink($path . $file[0]["file_url"]);
                }

                if ($type === "delete_completely") {
                    $files = $this->database->query("SELECT id, type FROM file WHERE folder = :id AND user_id = :user_id", [
                        [
                            "name" => "user_id",
                            "value" => $userid,
                            "type" => \PDO::PARAM_INT,
                        ],
                        [
                            "name" => "id",
                            "value" => $id,
                            "type" => \PDO::PARAM_INT,
                        ],
                    ], "fetchAll");

                    foreach($files as &$item) {
                        $this->delete((int)$item["id"], $type, $userid);
                    }
                }

                return ["response" => "success", "text" => $type];
            } else {
                return ["response" => "error", "text" => "invalid_id"];
            }
        }

        public function fav ($id, $userid = null) {
            if (!is_int($id)) { // id not valid
                return ["response" => "error", "text" => "invalid_id"];
            }

            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $this->database->query("UPDATE file SET fav = !fav WHERE user_id = :user_id AND id = :id", [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            $result = $this->database->query("SELECT fav FROM file WHERE user_id = :user_id AND id = :id LIMIT 1", [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            return ["response" => "success", "text" => $result[0]["fav"] == 1 ? "added" : "removed"];
        }

        public function setProfilePicture ($id, $userid = null) {
            if (!is_int($id)) { // id not valid
                return ["response" => "error", "text" => "invalid_id"];
            }

            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            if ($this->checkOwnership($id, $userid)) {
                $this->database->query("UPDATE users_expanded SET profile_picture = :profile_picture WHERE id = :user_id", [
                    [
                        "name" => "user_id",
                        "value" => $userid,
                        "type" => \PDO::PARAM_INT,
                    ],
                    [
                        "name" => "profile_picture",
                        "value" => $id,
                        "type" => \PDO::PARAM_INT,
                    ],
                ], "fetchAll");

                return ["response" => "success", "text" => "ok"];
            } else {
                return ["response" => "error", "text" => "missing_ownership"];
            }
        }

        public function setWallpaper ($id, $userid = null) {
            if (!is_int($id)) { // id not valid
                return ["response" => "error", "text" => "invalid_id"];
            }

            $user = $this->userManagement->getCurrentUserInfo(["id", "wallpaper"], ["all_users"]);
            $userid = isset($userid) ? $userid : $user->id;

            if ($this->checkOwnership($id, $userid)) {
                if (isset($user->wallpaper)) {
                    $wallpaper = json_decode($user->wallpaper);
                    $wallpaper->id = $id;
                } else {
                    $wallpaper = ["id" => $id, "opacity" => 0.5, "color" => "255, 255, 255"];
                }

                $this->database->query("UPDATE users_expanded SET wallpaper = :wallpaper WHERE id = :user_id", [
                    [
                        "name" => "user_id",
                        "value" => $userid,
                        "type" => \PDO::PARAM_INT,
                    ],
                    [
                        "name" => "wallpaper",
                        "value" => json_encode($wallpaper),
                        "type" => \PDO::PARAM_STR,
                    ],
                ], "fetchAll");

                return ["response" => "success", "text" => "ok"];
            } else {
                return ["response" => "error", "text" => "missing_ownership"];
            }
        }

        public function restore ($id, $userid = null) {
            if (!is_int($id)) { // id not valid
                return ["response" => "error", "text" => "invalid_id"];
            }

            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $this->database->query("UPDATE file SET trash = 0 WHERE user_id = :user_id AND id = :id AND deleted IS NULL AND trash = 1 LIMIT 1", [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            return ["response" => "success", "text" => "restored"];
        }

        public function empty ($userid = null) {
            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $this->database->query("UPDATE file SET deleted = NOW() WHERE user_id = :user_id AND trash = 1 AND deleted IS NULL", [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            return ["response" => "success", "text" => "emptied"];
        }

        public function getDetails ($id, $fields = null, $userid = null, $force = null) {
            if (!is_int($id)) { // id not valid
                return ["response" => "error", "text" => "invalid_id"];
            }

            if ($fields === null) {
                $fields = ["type", "name", "icon", "file_url", "file_type"];
            }

            if ($force === null) $force = false;

            if (\FrancescoSorge\PHP\LightSchool\WhiteBoard::isFileProjecting($id, Cookie::get("whiteboard_code"))) {
                $userid = \FrancescoSorge\PHP\LightSchool\FileManager::getOwner($id);
            } else if ($this->userManagement->isLogged()) {
                $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;
            } else {
                return ["response" => "error", "text" => "not_authorized"];
            }

            if (!FileManager::checkOwnership($id, $userid) && !Share::authorized($id, $userid)) {
                return ["response" => "error", "text" => "not_authorized"];
            } else if (Share::authorized($id, $userid)) {
                $userid = (new \FrancescoSorge\PHP\LightSchool\Share())->authorized((int)$id, $userid);
            }

            if (in_array("html", $fields)) array_push($fields, "n_ver");

            $file = $this->database->query("SELECT " . implode(", ", $fields) . " FROM file WHERE id = :file AND user_id = :user AND deleted is NULL LIMIT 1", [
                [
                    "name" => "file",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "user",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
            ], "fetchAll");

            if (isset($file[0])) {
                $file = $file[0];
                if (in_array("icon", $fields)) {
                    $file["icon"] = self::getIcon($id, $file["name"], $file["icon"], $file["type"], isset($file["file_url"]) ? $file["file_url"] : null, isset($file["file_type"]) ? $file["file_type"] : null);
                }
                if (!$force) {
                    unset($file["user_id"]);
                    unset($file["file_url"]);
                }

                if (isset($file["create_date"])) {
                    $file["create_date"] = Basic::timestampToHuman($file["create_date"]);
                }
                if (isset($file["last_view"])) {
                    $file["last_view"] = Basic::timestampToHuman($file["last_view"]);
                }
                if (isset($file["last_edit"])) {
                    $file["last_edit"] = Basic::timestampToHuman($file["last_edit"]);
                }
                if (isset($file["diary_date"])) {
                    $file["diary_date"] = Basic::timestampToHuman($file["diary_date"], "d/m/Y");
                }
                if (isset($file["diary_reminder"])) {
                    $file["diary_reminder"] = Basic::timestampToHuman($file["diary_reminder"], "d/m/Y");
                }

                if (in_array("html", $fields) && isset($file["type"]) && ($file["type"] === "notebook" || $file["type"] === "diary")) {
                    $file["html"] = Notebook::decrypt($id, $userid)["notebook"];
                    if ($file["n_ver"] == 2) $file["html"] = base64_decode($file["html"]);
                } else {
                    unset($file["html"]);
                }

                $data = ["response" => "success"];
                $data["file"] = $file;

                return $data;
            } else {
                return ["response" => "error", "text" => "not_found"];
            }
        }
    }
}