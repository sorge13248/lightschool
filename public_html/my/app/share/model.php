<?php

namespace FrancescoSorge\PHP\LightSchool {

    use FrancescoSorge\PHP\Basic;
    use FrancescoSorge\PHP\Database;

    final class Share {
        private $userManagement, $database;

        public function __construct () {
            global $fraUserManagement;


            $this->userManagement = &$fraUserManagement;
            $this->database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));
        }

        public static function authorized ($id, $userid = null) {
            global $fraUserManagement;


            $database = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            if (!is_int($id)) {
                return ["response" => "error", "text" => "Invalid id"];
            }

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $query = "SELECT sender FROM share WHERE file = :file_id AND receiving = :user_id AND deleted = 0 LIMIT 1";
            $param = [
                [
                    "name" => "file_id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
            ];
            $items = $database->query($query, $param, "fetchAll");

            if (isset($items[0])) {
                return $items[0]["sender"];
            } else {
                $authorized = false;

                do {
                    $query = "SELECT user_id, folder FROM file WHERE id = :file_id LIMIT 1";
                    $param = [
                        [
                            "name" => "file_id",
                            "value" => isset($folder) ? $folder : $id,
                            "type" => \PDO::PARAM_INT,
                        ],
                    ];
                    $qualcosa = $database->query($query, $param, "fetchAll");

                    if (isset($qualcosa[0])) {
                        $folder = $qualcosa[0]["folder"];
                        $shared = $database->query("SELECT sender FROM share WHERE receiving = :user_id AND file = :folder LIMIT 1", [
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

                        if (isset($qualcosa[0]["folder"])) {
                            $folder = $qualcosa[0]["folder"];
                        } else {
                            $folder = null;
                        }

                        if (isset($shared[0])) {
                            return $shared[0]["sender"];
                        }
                    } else {
                        return false;
                    }
                } while ($authorized === false && $folder !== null);
            }

            return false;
        }

        public function all ($limit = null, $userid = null) {
            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            if (!is_int($limit)) {
                $limit = 0;
            }

            $limit = $limit !== null ? $limit : 0;
            $query = "SELECT IF(sender != :user_id, sender, receiving) AS userid, profile_picture, username, name, surname, COUNT(*) AS count FROM share, users, users_expanded WHERE share.deleted = 0 AND IF(sender != :user_id, receiving = :user_id, sender = :user_id) AND IF(sender != :user_id, sender = users.id, receiving = users.id) AND users.id = users_expanded.id GROUP BY userid LIMIT {$limit}, 20";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
            ];

            $items = $this->database->query($query, $param, "fetchAll");

            foreach ($items as $key => &$item) {
                $item['profile_picture'] = ($item['profile_picture'] === null ? CONFIG_SITE["baseURL"] . "/upload/mono/black/user.png" : $item['profile_picture']);
            }

            return $items;
        }

        public function shared ($limit = null, $userid = null) {
            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            if (!is_int($limit)) {
                $limit = 0;
            }

            $limit = $limit !== null ? $limit : 0;
            $query = "SELECT sender AS userid, profile_picture, username, name, surname, COUNT(*) AS count FROM share, users, users_expanded WHERE receiving = :user_id AND share.deleted = 0 AND sender = users.id AND users.id = users_expanded.id GROUP BY sender LIMIT {$limit}, 20";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
            ];

            $items = $this->database->query($query, $param, "fetchAll");

            foreach ($items as $key => &$item) {
                $item['profile_picture'] = ($item['profile_picture'] === null ? CONFIG_SITE["baseURL"] . "/upload/mono/black/user.png" : $item['profile_picture']);
            }

            return $items;
        }

        public function sharing ($limit = null, $userid = null) {
            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            if (!is_int($limit)) {
                $limit = 0;
            }

            $limit = $limit !== null ? $limit : 0;
            $query = "SELECT receiving AS userid, profile_picture, username, name, surname, COUNT(*) AS count FROM share, users, users_expanded WHERE sender = :user_id AND share.deleted = 0 AND receiving = users.id AND users.id = users_expanded.id GROUP BY receiving LIMIT {$limit}, 20";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
            ];

            $items = $this->database->query($query, $param, "fetchAll");

            foreach ($items as $key => &$item) {
                $item['profile_picture'] = ($item['profile_picture'] === null ? CONFIG_SITE["baseURL"] . "/upload/mono/black/user.png" : $item['profile_picture']);
            }

            return $items;
        }

        public function sharedUser ($sender, $limit = null, $userid = null) {
            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            if (!is_int($limit)) {
                $limit = 0;
            }
            if (!is_int($sender)) {
                return ["response" => "error", "text" => "Invalid id"];
            }

            $limit = $limit !== null ? $limit : 0;
            $query = "SELECT file.id AS fileid, file.name, file.type, file.icon, file.file_url, file.file_type, file.diary_date, file.diary_type FROM share, file WHERE sender = :sender AND receiving = :user_id AND share.deleted = 0 AND sender = file.user_id AND share.file = file.id LIMIT {$limit}, 20";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "sender",
                    "value" => $sender,
                    "type" => \PDO::PARAM_INT,
                ],
            ];

            $items = $this->database->query($query, $param, "fetchAll");

            require_once __DIR__ . "/../file-manager/model.php";

            foreach ($items as $key => &$item) {
                $item['icon'] = FileManager::getIcon($item["fileid"], $item["name"], $item["icon"], $item["type"], $item["file_url"], $item["file_type"]);
                switch ($item["type"]) {
                    case "folder":
                        $item["second-row"] = "Cartella";
                        break;
                    case "notebook":
                        $item["second-row"] = "Quaderno";
                        break;
                    case "file":
                        $item["second-row"] = "File";
                        break;
                    case "diary":
                        $item["name"] = $item["diary_type"] . " di " . $item["name"];
                        $item["second-row"] = Basic::timestampToHuman($item["diary_date"], "d/m/Y");
                        break;
                    default:
                        $item["second-row"] = $item["type"];
                        break;
                }
            }

            return $items;
        }

        public function sharingUser ($receiving, $limit = null, $userid = null) {
            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            if (!is_int($limit)) {
                $limit = 0;
            }
            if (!is_int($receiving)) {
                return ["response" => "error", "text" => "Invalid id"];
            }

            $limit = $limit !== null ? $limit : 0;
            $query = "SELECT file.id AS fileid, file.name, file.type, file.icon, file.file_url, file.file_type, file.diary_date, file.diary_type FROM share, file WHERE receiving = :receiving AND sender = :user_id AND share.deleted = 0 AND sender = file.user_id AND file = file.id LIMIT {$limit}, 20";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "receiving",
                    "value" => $receiving,
                    "type" => \PDO::PARAM_INT,
                ],
            ];

            $items = $this->database->query($query, $param, "fetchAll");

            require_once __DIR__ . "/../file-manager/model.php";

            foreach ($items as $key => &$item) {
                $item['icon'] = FileManager::getIcon($item["fileid"], $item["name"], $item["icon"], $item["type"], $item["file_url"], $item["file_type"]);
                switch ($item["type"]) {
                    case "folder":
                        $item["second-row"] = "Cartella";
                        break;
                    case "notebook":
                        $item["second-row"] = "Quaderno";
                        break;
                    case "file":
                        $item["second-row"] = "File";
                        break;
                    case "diary":
                        $item["name"] = $item["diary_type"] . " di " . $item["name"];
                        $item["second-row"] = Basic::timestampToHuman($item["diary_date"], "d/m/Y");
                        break;
                    default:
                        $item["second-row"] = $item["type"];
                        break;
                }
            }

            return $items;
        }

        public function fileShared ($id, $user_id = null) {
            if (!is_int($id)) {
                return ["response" => "error", "text" => "invalid_id"];
            }

            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $query = "SELECT receiving, timestamp FROM share WHERE file = :file_id AND sender = :user_id AND deleted = 0";
            $param = [
                [
                    "name" => "file_id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
            ];
            $items = $this->database->query($query, $param, "fetchAll");

            foreach ($items as &$item) {
                $query = "SELECT name, surname FROM contact WHERE user_id = :user_id AND contact_id = :receiving AND deleted = 0 LIMIT 1";
                $param = [
                    [
                        "name" => "user_id",
                        "value" => $userid,
                        "type" => \PDO::PARAM_INT,
                    ],
                    [
                        "name" => "receiving",
                        "value" => $item["receiving"],
                        "type" => \PDO::PARAM_INT,
                    ],
                ];
                $contact = $this->database->query($query, $param, "fetchAll");

                $userProfilePicture = User::get(["profile_picture"], $item["receiving"])["profile_picture"];

                if (isset($contact[0])) {
                    $item["user"] = ["name" => $contact[0]["name"], "surname" => $contact[0]["surname"], "profile_picture" => $userProfilePicture];
                } else {
                    $item["user"] = User::get(["name", "surname"], $item["receiving"]);
                    $item["user"]["profile_picture"] = $userProfilePicture;
                }

                $item["timestamp"] = $item["timestamp"] === null ? null : Basic::timestampToHuman($item["timestamp"]);
            }

            return $items;
        }

        public function add ($file_id, $receiving, $userid = null) {
            if (!is_int($file_id)) {
                return ["response" => "error", "text" => "Invalid file id"];
            }

            if (!FileManager::checkOwnership($file_id, $userid)) {
                return ["response" => "error", "text" => "ownership"];
            }

            $receiving = $receiving !== null ? User::get(["id"], null, $receiving) : null;

            if ($receiving === null) {
                return ["response" => "error", "text" => "username"];
            }

            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            if ($receiving["id"] === $userid) {
                return ["response" => "error", "text" => "same"];
            }

            $privacy = (int)(User::get(["privacy_share_documents"], $receiving["id"])["privacy_share_documents"]);
            if ($privacy === 0) {
                return ["response" => "error", "text" => "privacy"];
            } else if ($privacy === 1) {
                $result = $this->database->query("SELECT id FROM contact WHERE user_id = :user AND contact_id = :contact AND trash = 0 AND deleted = 0 LIMIT 1", [
                    [
                        "name" => "user",
                        "value" => $receiving["id"],
                        "type" => \PDO::PARAM_INT,
                    ],
                    [
                        "name" => "contact",
                        "value" => $userid,
                        "type" => \PDO::PARAM_INT,
                    ]
                ], "fetchAll");

                if (!isset($result[0])) {
                    return ["response" => "error", "text" => "privacy"];
                }
            }

            $query = "SELECT id FROM share WHERE file = :file_id AND sender = :user_id AND deleted = 0 AND receiving = :receiving LIMIT 1";
            $param = [
                [
                    "name" => "file_id",
                    "value" => $file_id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "receiving",
                    "value" => $receiving["id"],
                    "type" => \PDO::PARAM_INT,
                ],
            ];
            $items = $this->database->query($query, $param, "fetchAll");

            if (isset($items[0])) {
                return ["response" => "error", "text" => "already"];
            }

            $query = "INSERT INTO share(sender, receiving, file) VALUES (:user_id, :id, :file_id)";
            $param = [
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "id",
                    "value" => $receiving["id"],
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "file_id",
                    "value" => $file_id,
                    "type" => \PDO::PARAM_INT,
                ],
            ];
            $this->database->query($query, $param, "fetchAll");

            return ["response" => "success", "text" => "ok"];
        }

        public function delete ($id, $file_id, $userid = null) {
            if (!is_int($id)) {
                return ["response" => "error", "text" => "Invalid id"];
            }
            if (!is_int($file_id)) {
                return ["response" => "error", "text" => "Invalid file id"];
            }

            $userid = isset($userid) ? $userid : $this->userManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $query = "UPDATE share SET deleted = 1 WHERE sender = :user_id AND receiving = :id AND file = :file_id";
            $param = [
                [
                    "name" => "id",
                    "value" => $id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "file_id",
                    "value" => $file_id,
                    "type" => \PDO::PARAM_INT,
                ],
                [
                    "name" => "user_id",
                    "value" => $userid,
                    "type" => \PDO::PARAM_INT,
                ],
            ];
            $this->database->query($query, $param, "fetchAll");

            return ["response" => "success", "text" => "ok"];
        }
    }
}