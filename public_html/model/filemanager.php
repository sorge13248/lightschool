<?php
/**
 * @author Francesco Sorge <contact@francescosorge.com>
 * @link http://docs.francescosorge.com/
 */

namespace FrancescoSorge\PHP {

    class FileManager {
        const NAME = "FileManager";
        const VERSION = 0.6;
        const AUTHOR = "Francesco Sorge";
        const DOCUMENTATION = "FrancescoSorge\\PHP\\FileManager";
        const LICENSE = "MIT";

        protected $path, $files = [], $filesArray = [], $maxSearchLimit = 128, $trash = null;

        public function __construct ($path = null, $ignoreDotAndDoubleDot = null, $maxSearchLimit = null) {
            if ($ignoreDotAndDoubleDot === null) {
                $ignoreDotAndDoubleDot = false;
            }
            if ($maxSearchLimit !== null) {
                $this->maxSearchLimit = $maxSearchLimit;
            }

            $this->path = $path;
            if (file_exists($path)) {
                $files = scandir($path);

                foreach ($files as $file) {
                    if ($ignoreDotAndDoubleDot && ($file === "." || $file === "..")) {
                        continue;
                    } else {
                        array_push($this->filesArray, $file);
                        if (is_dir($file)) {
                            array_push($this->files, new FileManager\Folder($path . DIRECTORY_SEPARATOR . $file));
                        } else {
                            array_push($this->files, new FileManager\File($path . DIRECTORY_SEPARATOR . $file));
                        }
                    }
                }
            } else {
                throw new \Exception("Directory does not exists.");
            }
        }

        public function path () {
            return $this->path;
        }

        public function getFiles () {
            return $this->files;
        }

        public function getHiddenFiles ($files, $bool = null) {
            if ($bool === null) $bool = true;
            if ($bool === false) {
                foreach ($files as $key => $file) {
                    $value = $file->name();
                    if ($value{0} === "." && (isset($value{1}) === false || (isset($value{1}) && $value{1} !== "."))) {
                        unset($files[$key]);
                    }
                }
            }
            return $files;
        }

        public function find ($name, $extension = null, $recursiveSearch = null, $limit = null) {
            $start = microtime(true);

            if ($recursiveSearch === null) {
                $recursiveSearch = false;
            }
            if ($limit === null) {
                $limit = $this->maxSearchLimit;
            }

            if (is_string($name)) {
                $name = [$name];
            }
            if (is_string($extension)) {
                $extension = [$extension];
            }

            $find = [];
            $i = 0;
            foreach ($this->filesArray as $file) {
                if ($i >= $limit) break; // Reached search limit

                if (is_dir($this->path . DIRECTORY_SEPARATOR . $file)) {
                    $temp = new FileManager\Folder($this->path . DIRECTORY_SEPARATOR . $file);
                } else {
                    $temp = new FileManager\File($this->path . DIRECTORY_SEPARATOR . $file);
                }

                if ($name === null || Basic::inArrayPartial($name, $temp->name()) || Basic::inArrayPartial($name, $temp->name(false))) { // if name (with or without extension) matches, or name is null (so gets every folder and file)
                    if ($extension === null || ($temp::TYPE === "FILE" && is_array($extension) === true && in_array($temp->extension(), $extension))) {
                        $i++;
                        array_push($find, $temp);
                    }
                }

                if (is_dir($this->path . DIRECTORY_SEPARATOR . $file) && $recursiveSearch === true) { // Searches inside subdirectories
                    $tempFileManager = new FileManager($temp->path(), true);
                    $result = $tempFileManager->find($name, $extension, $recursiveSearch, $limit - $i);
                    foreach ($result["files"] as $item) {
                        array_push($find, $item);
                    }
                    array_merge($find, $result["files"]);
                    $i = $i + $result["count"];
                }
            }

            return ["files" => $find, "count" => $i, "executionTime" => microtime(true) - $start];
        }

        public function sort ($by = null, $order = null) {
            if ($by === null) $by = "name";
            if ($order === null) $order = "asc";

            $rawArray = [];
            foreach ($this->files as $key => $item) {
                if ($by === "size") {
                    $rawArray[$key] = $item->size("byte");
                } else if ($by === "extension" || $by === "type") {
                    $rawArray[$key] = strtolower((method_exists($item, "extension") ? $item->extension() : $item::TYPE));
                } else if ($by === "folder-first") {
                    $rawArray[$key] = $item::TYPE === "FOLDER" ? "00000000000000" : $item::TYPE;
                } else {
                    $rawArray[$key] = strtolower($item->name(false));
                }
            }
            asort($rawArray);
            if ($order === "desc") {
                rsort($rawArray);
            }

            $newArray = [];
            foreach ($rawArray as $key => $item) {
                array_push($newArray, $this->files[$key]);
            }

            return $newArray;
        }

        public function defineTrash ($path) {
            if (is_dir($path)) {
                $this->trash = $path;
            } else if ($path === null) {
                $this->trash = null;
            }
        }

        public function moveToTrash ($item) {
            try {
                return $item->move($this->trash);
            } catch (\Exception $e) {
                return $e;
            }
        }
    }
}

namespace FrancescoSorge\PHP\FileManager {

    class Folder {
        const NAME = "FileManager\\Folder";
        const VERSION = 0.6;
        const AUTHOR = "Francesco Sorge";
        const DOCUMENTATION = "FrancescoSorge\\PHP\\FileManager\\Folder";
        const LICENSE = "MIT";

        const TYPE = "FOLDER";

        protected $path = null;

        public function __construct ($path) {
            if (file_exists($path)) {
                $this->path = $path;
            } else {
                throw new \Exception(self::TYPE . " does not exist at path {$path}");
            }
        }

        public function path () {
            return $this->path;
        }

        public function move ($target, $shouldOverwrite = null, $appendName = null, $extension = null) {
            if ($shouldOverwrite === null) $shouldOverwrite = false;
            if ($appendName === null) $appendName = true;

            if ($appendName) {
                if ($extension === null) {
                    $extension = "";
                }
                $target .= DIRECTORY_SEPARATOR . $this->name() . $extension;
            }

            if ($shouldOverwrite === false) {
                if (file_exists($target)) {
                    throw new \Exception("File already exists. Cannot overwrite.");
                }
            }

            return $this->rename($target, $shouldOverwrite);
        }

        public function name () {
            return basename($this->path);
        }

        public function rename ($name, $shouldOverwrite = null, $appendPath = null) {
            if ($shouldOverwrite === null) $shouldOverwrite = false;
            if ($appendPath === null) $appendPath = true;

            if ($appendPath) {
                $name = $this->getDirectory() . DIRECTORY_SEPARATOR . $name;
            }

            if ($shouldOverwrite === false) {
                if (file_exists($name)) {
                    throw new \Exception("File already exists. Cannot overwrite.");
                }
            }

            return rename($this->path, $name);
        }

        public function getDirectory () {
            return dirname($this->path);
        }

        public function copy ($target, $shouldOverwrite = null, $appendName = null, $extension = null) {
            if ($shouldOverwrite === null) $shouldOverwrite = false;
            if ($appendName === null) $appendName = true;

            if ($appendName) {
                if ($extension === null) {
                    $extension = "";
                }
                $target .= DIRECTORY_SEPARATOR . $this->name() . $extension;
            }

            if ($shouldOverwrite === false) {
                if (file_exists($target)) {
                    throw new \Exception("File already exists. Cannot overwrite.");
                }
            }

            return copy($this->path, $target);
        }

        public function size ($unit = null, $appendMisureUnit = null, $precision = null) {
            if ($unit === null) $unit = "auto";
            if ($appendMisureUnit === null) $appendMisureUnit = false;
            if (!is_int($precision)) $precision = 2;

            $size = 0;
            $currentFolder = new \FrancescoSorge\PHP\FileManager($this->path, true);
            foreach ($currentFolder->getFiles() as $file) {
                if (is_dir($file->path())) {
                    $size += (new Folder($file->path()))->size("byte");
                } else {
                    $size += $file->size("byte");
                }
            }

            $size = self::decideMeasureUnit($size, $unit, $precision);

            return $appendMisureUnit === true ? $size["size"] . " " . $size["unit"] : $size["size"];
        }

        public static function decideMeasureUnit ($size, $unit, $precision = null) { // $size passed in byte
            if (!is_int($precision)) $precision = 2;
            switch ($unit) {
                case "byte":
                    $sizeNew = $size;
                    break;
                case "kb":
                    $sizeNew = round($size / 1024, $precision);
                    break;
                case "mb":
                    $sizeNew = round($size / 1024 / 1024, $precision);
                    break;
                case "gb":
                    $sizeNew = round($size / 1024 / 1024 / 1024, $precision);
                    break;
                case "auto":
                default:
                    $unit = "gb";
                    $sizeNew = self::decideMeasureUnit($size, "gb")["size"];
                    if ($sizeNew < 1) {
                        $unit = "mb";
                        $sizeNew = self::decideMeasureUnit($size, "mb")["size"];
                        if ($sizeNew < 1) {
                            $unit = "kb";
                            $sizeNew = self::decideMeasureUnit($size, "kb")["size"];
                            if ($sizeNew < 1) {
                                $unit = "byte";
                                $sizeNew = $size;
                            }
                        }
                    }
                    break;
            }

            return ["size" => $sizeNew, "unit" => $unit];
        }

        public function moveToTrash () {

        }

        public function delete ($confirm = false) {
            if ($confirm === true) {

            } else {
                throw new \Exception("Tried to delete a " . self::TYPE . " without passing 'true' as first parameter (it's a safety check).");
            }
        }
    }

    class File extends Folder {
        const NAME = "FileManager\\File";
        const VERSION = 0.6;
        const AUTHOR = "Francesco Sorge";
        const DOCUMENTATION = "FrancescoSorge\\PHP\\FileManager\\File";
        const LICENSE = "MIT";

        const TYPE = "FILE";

        public function name ($hideExtension = null) {
            if ($hideExtension === null) $hideExtension = true;
            return ($hideExtension ? str_replace(".{$this->extension()}", "", $this->name(false)) : parent::name());
        }

        public function extension () {
            return pathinfo($this->path, PATHINFO_EXTENSION);
        }

        public function move ($target, $shouldOverwrite = null, $appendName = null, $extension = null) {
            if ($appendName === null) $appendName = true;
            return parent::move($target, $shouldOverwrite, $appendName, "." . $this->extension());
        }

        public function copy ($target, $shouldOverwrite = null, $appendName = null, $extension = null) {
            if ($appendName === null) $appendName = true;
            return parent::copy($target, $shouldOverwrite, $appendName, "." . $this->extension());
        }

        public function size ($unit = null, $appendMisureUnit = null, $precision = null) {
            if ($unit === null) $unit = "auto";
            if ($appendMisureUnit === null) $appendMisureUnit = false;
            if (!is_int($precision)) $precision = 2;

            $size = self::decideMeasureUnit(filesize($this->path), $unit, $precision);

            return $appendMisureUnit === true ? $size["size"] . " " . $size["unit"] : $size["size"];
        }

    }
}