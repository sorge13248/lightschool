<?php
/**
 * @author Francesco Sorge <contact@francescosorge.com>
 * @link http://www.francescosorge.com/docs/latest/index.html
 */

namespace FrancescoSorge\PHP {

    class Database {
        const NAME = "Database";
        const VERSION = 2.0;
        const AUTHOR = "Francesco Sorge";
        const DOCUMENTATION = "FrancescoSorge\\PHP\\Database";
        const LICENSE = "MIT";

        private $database;

        /**
         * @param PDO $database : PDO parameters of connection to the db
         */
        public function __construct (\PDO $database) {
            $this->database = $database;
            $this->database->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            $this->database->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }

        /**
         * @param PDO $database : PDO parameters of connection to the db; associative array $params (optional) to be used in bindParam
         * @return array when using a SELECT, to be used like $errorReporting->name;
         * @example query('SELECT name FROM user WHERE id = :id', [["name" => "id", "value" => 1, "type" => \PDO::PARAM_INT]])
         */
        public function query ($query, $params = [], $method = null) {
            if (is_string($query)) {
                $query = [$query];
                $params = [$params];
            }

            $result = [];
            foreach ($query as $key => &$single) {
                $stmt = $this->database->prepare($single);
                foreach ($params[$key] as &$param) {
                    $stmt->bindParam(':' . $param["name"], $param["value"], $param["type"]);
                }
                $stmt->execute();
                if (mb_strpos($single, "SELECT") !== false) {
                    if ($method === "fetchAll") $result[$key] = $stmt->fetchAll();
                    else $result[$key] = $stmt->fetchObject();
                } else if (mb_strpos($single, "INSERT") !== false) {
                    $result[$key] = $this->database->lastInsertId();
                } else {
                    $result[$key] = $stmt->rowCount();
                }
            }

            if (count($query) === 1) $result = $result[0];

            return $result;
        }

        public function setTableDotField ($option = null) {
            if ($option === null) {
                $option = true;
            }
            $this->database->setAttribute(\PDO::ATTR_FETCH_TABLE_NAMES, $option);
        }

        /**
         * @return bool: if connection has been closed successful
         */
        public function closeConnection () {
            // sets $database to null
            $this->database = null;

            // if successful, returns true
            if ($this->database === null) {
                return true;
            }
            // if something went wrong, returns false
            return false;
        }
    }
}