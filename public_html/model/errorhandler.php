<?php
/**
 * @author Francesco Sorge <contact@francescosorge.com>
 * @link http://www.francescosorge.com/docs/latest/index.html
 */

namespace FrancescoSorge\PHP {

    class ErrorHandler {
        protected $type;
        protected $description;
        protected $detail;
        protected $uniqueErrorID;

        /**
         * @param string $type : which text to show as error type
         */
        public function __construct (string $type, string $description, string $detail) {
            $this->type = $type;
            $this->description = $description;
            $basic = new Basic;
            $this->errorID = $basic->generateRandomID();
            $this->detail = $detail . $this->errorID;
        }

        /**
         * @return string $errorID
         */
        public function getErrorID () : string {
            return $this->errorID;
        }

        /**
         * @return void: shows the error to the client
         */
        public function showError () : void {
            echo("<h1>{$this->getType()}</h1>
			  <h3>{$this->getDescription()}</h3>
			  <p>{$this->getDetail()}</p>
			 ");
        }

        /**
         * @return string $type
         */
        public function getType () : string {
            return $this->type;
        }

        /**
         * @return string $description
         */
        public function getDescription () : string {
            return $this->description;
        }

        /**
         * @return string $description
         */
        public function getDetail () : string {
            return $this->detail;
        }

        /**
         * @return void: reports the error to the webmaster
         */
        public function reportError () : void {
            $errorReporting = new Database(new \PDO(CONFIG_DATABASE['driver'] . ":host=" . CONFIG_DATABASE['host'] . ";dbname=" . CONFIG_DATABASE['dbname'] . ";charset=" . CONFIG_DATABASE['charset'], CONFIG_DATABASE["user"], CONFIG_DATABASE["password"]));

            $errorReporting->query('INSERT INTO error_report (type, description, detail)
								VALUES (:type, :description, :detail)',
                [
                    ["name" => "type", "value" => $this->getType(), "type" => \PDO::PARAM_STR],
                    ["name" => "description", "value" => $this->getDescription(), "type" => \PDO::PARAM_STR],
                    ["name" => "detail", "value" => $this->getDetail(), "type" => \PDO::PARAM_STR],
                ]
            );
            $errorReporting->closeConnection();
            unset($errorReporting);
        }
    }
}