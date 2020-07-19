<?php

class PantryTwoFactorSession {
    private $id;
    private $created;
    private $user_id;
    private $secret;

    public function __construct($two_factor_session_id = null) {
        self::purgeTwoFactorSessions();

        $this->id = null;
        $this->created = null;
        $this->user_id = null;
        $this->secret = null;

        if ($two_factor_session_id) {
            $sql_get_two_factor_session = Pantry::$db->prepare("SELECT id, created, user_id, secret FROM two_factor_sessions WHERE id=:id");
            $sql_get_two_factor_session->bindValue(':id', $two_factor_session_id, PDO::PARAM_STR);
            $sql_get_two_factor_session->execute();

            if ($two_factor_session_row = $sql_get_two_factor_session->fetch(PDO::PARAM_STR)) {
                $this->id = $two_factor_session_row['id'];
                $this->created = $two_factor_session_row['created'];
                $this->user_id = $two_factor_session_row['user_id'];
                $this->secret = $two_factor_session_row['secret'];
            }
        }
    }

    private static function purgeTwoFactorSessions() {
        try {
            $sql_purge_sessions = Pantry::$db->prepare("DELETE FROM two_factor_sessions WHERE created <= :exp");
            $sql_purge_sessions->bindValue(':exp', Pantry::getNow(-2592000));
            if (!$sql_purge_sessions->execute()) {
                throw new PantryTwoFactorSessionsNotPurgedException("Two factor sessions could not be purged.");
            }
        }
        catch (PantryTwoFactorSessionsNotPurgedException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }
    }

    public function getID() {
        return $this->id;
    }

    public function getCreated() {
        return $this->created;
    }

    public function getUserID() {
        return $this->user_id;
    }

    public function getSecret() {
        return $this->secret;
    }

    public function create($user_id) {
        //generate session contents and secure secret
        try {
            $this->id = Pantry::generateUUID();
            $this->created = date("Y-m-d H:i:s");
            $this->user_id = $user_id;
            $this->secret = bin2hex(openssl_random_pseudo_bytes(64, $random_is_secure));

            if (!$random_is_secure) {
                throw new PantryTwoFactorSessionSecretNotSecureException("Two factor session secret is not cryptographically secure.");
            }
        }
        catch (PantryTwoFactorSessionSecretNotSecureException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }

        //save session
        try {
            $sql_insert_two_factor_session = Pantry::$db->prepare("INSERT INTO two_factor_sessions (id, created, user_id, secret) VALUES (:id, :created, :user_id, :secret)");
            $sql_insert_two_factor_session->bindValue(':id', $this->id, PDO::PARAM_STR);
            $sql_insert_two_factor_session->bindValue(':created', $this->created, PDO::PARAM_STR);
            $sql_insert_two_factor_session->bindValue(':user_id', $this->user_id, PDO::PARAM_STR);
            $sql_insert_two_factor_session->bindValue(':secret', $this->secret, PDO::PARAM_STR);
            if (!$sql_insert_two_factor_session->execute()) {
                Pantry::$logger->debug(print_r($sql_insert_two_factor_session->errorInfo(), true));
                throw new PantryTwoFactorSessionNotCreatedException("Two factor session could not be created.");
            }
        }
        catch (PantryTwoFactorSessionNotCreatedException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }

        //delete old sessions if the user has more than 10
        try {
            $sql_limit_two_factor_sessions_count = Pantry::$db->prepare("SELECT id FROM two_factor_sessions WHERE user_id=:user_id");
            $sql_limit_two_factor_sessions_count->bindValue(':user_id', $this->user_id, PDO::PARAM_STR);
            if (!$sql_limit_two_factor_sessions_count->execute()) {
                throw new PantryTwoFactorSessionsNotLimitedException("Two factor sessions could not be limited on count phase.");
            }

            $count = count($sql_limit_two_factor_sessions_count->fetchAll());
            if ($count > 10) {
                $sql_limit_two_factor_sessions_delete = Pantry::$db->prepare("DELETE FROM two_factor_sessions ORDER BY created LIMIT :num_delete");
                $sql_limit_two_factor_sessions_delete->bindValue(':num_delete', $count-10, PDO::PARAM_INT);
                if (!$sql_limit_two_factor_sessions_delete->execute()) {
                    throw new PantryTwoFactorSessionsNotLimitedException("Two factor sessions could not be limited on delete phase.");
                }
            }
        }
        catch (PantryTwoFactorSessionsNotLimitedException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }
    }

    public function destroy() {
        try {
            $sql_destroy_two_factor_session = Pantry::$db->prepare("DELETE FROM two_factor_sessions WHERE id=:id");
            $sql_destroy_two_factor_session->bindValue(':id', $this->id, PDO::PARAM_STR);
            if (!$sql_destroy_two_factor_session->execute()) {
                throw new PantryTwoFactorSessionNotDestroyedException("Could not destroy two factor session");
            }
        }
        catch (PantryTwoFactorSessionNotDestroyedException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }

        $this->id = null;
        $this->created = null;
        $this->user_id = null;
        $this->secret = null;
    }

    public static function checkTwoFactorSession($id, $user_id, $secret) {
        $sql_check_two_factor_session = Pantry::$db->prepare("SELECT id FROM two_factor_sessions WHERE id=:id AND user_id=:user_id AND secret=:secret");
        $sql_check_two_factor_session->bindValue(':id', $id, PDO::PARAM_STR);
        $sql_check_two_factor_session->bindValue(':user_id', $user_id, PDO::PARAM_STR);
        $sql_check_two_factor_session->bindValue(':secret', $secret, PDO::PARAM_STR);
        $sql_check_two_factor_session->execute();

        if ($check_session_row = $sql_check_two_factor_session->fetch()) {
            return true;
        }

        return false;
    }

    public static function purgeUser($user_id) {
        $sql_purge_user = Pantry::$db->prepare("DELETE FROM two_factor_sessions WHERE user_id=:user_id");
        $sql_purge_user->bindValue(':user_id', $user_id, PDO::PARAM_STR);
        $sql_purge_user->execute();
    }
}
