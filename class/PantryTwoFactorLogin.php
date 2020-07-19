<?php

class PantryTwoFactorLogin {
    private function __construct() {}

    private static function purgeTwoFactorLogins() {
        try {
            $sql_purge_two_factor_sessions = Pantry::$db->prepare("DELETE FROM two_factor_logins WHERE created <= :expires");
            $sql_purge_two_factor_sessions->bindValue(':expires', Pantry::getNow(-300), PDO::PARAM_STR);
            if (!$sql_purge_two_factor_sessions->execute()) {
                throw new PantryTwoFactorLoginsNotPurgedException("Two factor logins could not be purged.");
            }
        }
        catch (PantryTwoFactorLoginsNotPurgedException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }
    }

    public static function checkAndRecord($user_id, $verification_code) {
        self::purgeTwoFactorLogins();
        Pantry::$logger->debug("Checking two-factor login for $user_id with code $verification_code.");

        try {
            $sql_check_two_factor_login = Pantry::$db->prepare("SELECT id FROM two_factor_logins WHERE user_id=:user_id AND verification_code=:verification_code");
            $sql_check_two_factor_login->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sql_check_two_factor_login->bindValue(':verification_code', $verification_code, PDO::PARAM_STR);

            if (!$sql_check_two_factor_login->execute()) {
                throw new PantryTwoFactorLoginsNotCheckedException("Two factor logins could not be checked.");
            }

            if ($two_factor_login_row = $sql_check_two_factor_login->fetch()) {
                return false;
            }
        }
        catch (PantryTwoFactorLoginsNotCheckedException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }

        try {
            $sql_insert_two_factor_login = Pantry::$db->prepare("INSERT INTO two_factor_logins (id, created, user_id, verification_code) VALUES (:id, :created, :user_id, :verification_code)");
            $sql_insert_two_factor_login->bindValue(':id', Pantry::generateUUID(), PDO::PARAM_STR);
            $sql_insert_two_factor_login->bindValue(':created', date("Y-m-d H:i:s"), PDO::PARAM_STR);
            $sql_insert_two_factor_login->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sql_insert_two_factor_login->bindValue(':verification_code', $verification_code, PDO::PARAM_STR);

            if (!$sql_insert_two_factor_login->execute()) {
                throw new PantryTwoFactorLoginNotRecordedException("Two factor login could not be recorded.");
            }

            return true;
        }
        catch (PantryTwoFactorLoginNotRecordedException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }
    }

    public static function purgeUser($user_id) {
        $sql_purge_user = Pantry::$db->prepare("DELETE FROM two_factor_logins WHERE user_id=:user_id");
        $sql_purge_user->bindValue(':user_id', $user_id, PDO::PARAM_STR);
        $sql_purge_user->execute();
    }
}
