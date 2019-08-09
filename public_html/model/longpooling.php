<?php
/**
 * @author Francesco Sorge <contact@francescosorge.com>
 * @link http://docs.francescosorge.com/
 */

namespace FrancescoSorge\PHP {

    final class LongPooling {
        const NAME = "LongPooling";
        const VERSION = 0.5;
        const AUTHOR = "Francesco Sorge";
        const DOCUMENTATION = "FrancescoSorge\\PHP\\LongPooling";
        const LICENSE = "MIT";

        protected $file;

        public function __construct ($file) {
            $this->file = $file;
        }

        public function execute () {
            // set php runtime to unlimited
            set_time_limit(0);

            // if ajax request has send a timestamp, then $last_ajax_call = timestamp, else $last_ajax_call = null
            $last_ajax_call = isset($_GET['timestamp']) ? (int)$_GET['timestamp'] : null;

            // PHP caches file data, like requesting the size of a file, by default. clearstatcache() clears that cache
            clearstatcache();
            // get timestamp of when file has been changed the last time
            $last_change_in_data_file = filemtime($this->file);


            // get content of data.txt
            $data = file_get_contents($this->file);

            // put data.txt's content and timestamp of last data.txt change into array
            $result = [
                'data_from_file' => $data,
                'timestamp' => $last_change_in_data_file,
            ];

            // encode to JSON, render the result (for AJAX)
            $json = json_encode($result);
            echo $json;
        }
    }
}