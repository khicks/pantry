<?php

class PantryUserSession {
    protected $id;
    private $created;
    protected $updated;
    private $session_id;
    private $user_id;
    protected $ip_address;

    public function __construct($session_id) {
        self::purgeSessions();

        $this->id = null;
        $this->created = null;
        $this->updated = null;
        $this->session_id = $session_id;
        $this->user_id = null;
        $this->ip_address = null;

        $sql_get_session = Pantry::$db->prepare("SELECT id, created, updated, session_id, user_id, ip_address FROM user_sessions WHERE session_id=:session_id");
        $sql_get_session->bindValue(':session_id', $session_id, PDO::PARAM_STR);
        $sql_get_session->execute();

        if ($session_row = $sql_get_session->fetch(PDO::FETCH_ASSOC)) {
            $this->id = $session_row['id'];
            $this->created = $session_row['created'];
            $this->updated = $session_row['updated'];
            $this->session_id = $session_row['session_id'];
            $this->user_id = $session_row['user_id'];
            $this->ip_address = $session_row['ip_address'];
        }
    }

    private static function purgeSessions() {
        try {
            $sql_purge_sessions = Pantry::$db->prepare("DELETE FROM user_sessions WHERE updated <= NOW() - INTERVAL :seconds SECOND");
            $sql_purge_sessions->bindValue(':seconds', Pantry::$config['session_time'], PDO::PARAM_INT);
            if (!$sql_purge_sessions->execute()) {
                throw new PantrySessionsNotPurgedException("Sessions could not be purged.");
            }
        }
        catch (PantrySessionsNotPurgedException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }
    }

    public function getUserID() {
        return $this->user_id;
    }

    public function create($user_id, $ip_address) {
        $this->id = Pantry::generateUUID();
        $this->created = Pantry::getNow();
        $this->updated = Pantry::getNow();
        $this->user_id = $user_id;
        $this->ip_address = $ip_address;

        try{
            $sql_create_session = Pantry::$db->prepare("INSERT INTO user_sessions (id, created, updated, session_id, user_id, ip_address) VALUES (:id, :created, :updated, :session_id, :user_id, :ip_address)");
            $sql_create_session->bindValue(':id', $this->id, PDO::PARAM_STR);
            $sql_create_session->bindValue(':created', $this->created, PDO::PARAM_STR);
            $sql_create_session->bindValue(':updated', $this->updated, PDO::PARAM_STR);
            $sql_create_session->bindValue(':session_id', $this->session_id, PDO::PARAM_STR);
            $sql_create_session->bindValue(':user_id', $this->user_id, PDO::PARAM_STR);
            $sql_create_session->bindValue(':ip_address', $this->ip_address, PDO::PARAM_STR);
            if (!$sql_create_session->execute()) {
                throw new PantrySessionNotCreatedException("Session could not be created.");
            }
        }
        catch (PantrySessionNotCreatedException $e) {
            Pantry::$logger->critical($e->getMessage());
            die();
        }

        return true;
    }

    public function check() {
        if ($this->id) {
            return true;
        }

        return false;
    }

    public function destroy() {
        $this->id = null;
        $this->created = null;
        $this->updated = null;
        $this->user_id = null;
        $this->ip_address = null;

        try {
            $sql_destroy_session = Pantry::$db->prepare("DELETE FROM user_sessions WHERE session_id=:session_id");
            $sql_destroy_session->bindValue(':session_id', $this->session_id, PDO::PARAM_STR);
            if (!$sql_destroy_session->execute()) {
                throw new PantrySessionNotDestroyedException("Session could not be destroyed.");
            }
        }
        catch (PantrySessionNotDestroyedException $e) {
            Pantry::$logger->critical($e->getMessage());
            die();
        }
    }
}
