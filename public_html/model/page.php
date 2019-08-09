<?php

namespace FrancescoSorge\PHP {
    class Page {
        const NAME = "Page";
        const VERSION = 1.5;
        const AUTHOR = "Francesco Sorge";
        const DOCUMENTATION = "FrancescoSorge\\PHP\\Page";
        const LICENSE = "MIT";

        protected $pageURL, $variables, $area;

        public function __construct ($pageURL, $variables = [], $area = "body", $show = true) {
            if (!is_array($pageURL)) {
                $pageURL = [$pageURL];
            }
            $this->pageURL = $pageURL;
            $this->variables = $variables;
            $this->area = $area;

            if ($show) $this->show();
        }

        public function show () {
            if ($this->area == "body") {
                foreach ($this->pageURL as $pageURL) {
                    if (file_exists(__DIR__ . "/../page/body/{$pageURL}")) {
                        require_once __DIR__ . "/../page/body/{$pageURL}";
                    } else if (file_exists(__DIR__ . "/../{$pageURL}")) {
                        require_once __DIR__ . "/../{$pageURL}";
                    } else {
                        echo("<div class='content' id='pageContent'>FRAPAGE: could not load page at url {$pageURL}</div>");
                        require __DIR__ . "/../config/database.php";
                        $eFraPage = new ErrorHandler("FraPage missing body", "FraPage: could not load page at url {$pageURL}", "Unique error ID: ", "framessaging_error_report");
                        $eFraPage->reportError();
                    }
                }
            } else if ($this->area == "head") {
                foreach ($this->pageURL as $pageURL) {
                    if (file_exists(__DIR__ . "/../page/head/{$pageURL}")) {
                        include_once __DIR__ . "/../page/head/{$pageURL}";
                    }
                }
            }
        }

        public function getVariables ($name = null) {
            if ($name !== null) return $this->variables[$name];
            else return $this->variables;
        }
    }
}