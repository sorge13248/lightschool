<?php
/**
 * @author Francesco Sorge <contact@francescosorge.com>
 * @link http://docs.francescosorge.com/
 */

namespace FrancescoSorge\PHP {

    use Defuse\Crypto\Key;

    final class Crypto {
        const NAME = "Crypto";
        const VERSION = 1.0;
        const AUTHOR = "Francesco Sorge";
        const DOCUMENTATION = "FrancescoSorge\\PHP\\Crypto";
        const LICENSE = "MIT";

        public static function encrypt($data, $key = null, $userid = null) {
            global $fraUserManagement;

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            if ($key === null) {
                $key = \Defuse\Crypto\Key::createNewRandomKey();
            } else {
                $key = Key::loadFromAsciiSafeString($key);
            }
            $data = \Defuse\Crypto\Crypto::encrypt($data, $key);

            $public = Keyring::get("public", $userid);
            $rsa = new \phpseclib\Crypt\RSA();
            $rsa->setHash("sha512");
            $rsa->loadKey($public);

            $key = $rsa->encrypt($key->saveToAsciiSafeString());

            return ["data" => $data, "key" => $key];
        }

        public static function decrypt($data, $key, $userid = null) {
            global $fraUserManagement;

            $userid = isset($userid) ? $userid : $fraUserManagement->getCurrentUserInfo(["id"], ["users"])->id;

            $private = Keyring::get("private", $userid);
            $rsa = new \phpseclib\Crypt\RSA();
            $rsa->setHash("sha512");
            $rsa->loadKey($private);

            $key = \Defuse\Crypto\Key::loadFromAsciiSafeString($rsa->decrypt($key));

            return \Defuse\Crypto\Crypto::decrypt($data, $key);
        }
    }

}