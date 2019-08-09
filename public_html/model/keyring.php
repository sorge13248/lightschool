<?php

namespace FrancescoSorge\PHP {

    final class Keyring {
        
        public static function set ($userid = null) { // TODO set correct path
            global $fraUserManagement;
            
            $path = CONFIG_SITE["secureDIR"] . "\\keyring\\" . ($userid === null ? $fraUserManagement->getCurrentUserInfo(["id"], ["users_expanded"])->id : $userid);
            mkdir($path);

            $rsa = new \phpseclib\Crypt\RSA();
            $rsa->setHash("sha512");
            extract($rsa->createKey(2048));

            if (isset($publickey) && isset($privatekey)) {
                $fp = fopen($path . "\\public.key", 'w');
                fwrite($fp, $publickey);
                fclose($fp);

                $fp = fopen($path . "\\private.key", 'w');
                fwrite($fp, $privatekey);
                fclose($fp);
            } else {
                throw new \Exception("Cannot create RSA key pair. Contact support center.");
            }
        }

        public static function get ($type, $userid = null) { // TODO set correct path
            global $fraUserManagement;
            
            if ($fraUserManagement->isLogged() || $userid !== null) {
                if ($type !== "public" && $type !== "private") {
                    throw new \Exception("Invalid key requested.");
                }

                $file = CONFIG_SITE["secureDIR"] . "\\keyring\\" . ($userid === null ? $fraUserManagement->getCurrentUserInfo(["id"], ["users_expanded"])->id : $userid) . "\\$type.key";
                if (file_exists($file)) {
                    return file_get_contents($file);
                } else {
                    throw new \Exception("Fatal error. Key not found. Contact support center.");
                }
            } else {
                throw new \Exception("Not authorized.");
            }
        }
    }
}