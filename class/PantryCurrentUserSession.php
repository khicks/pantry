<?php

class PantryCurrentUserSession extends PantryUserSession {
    public function __construct() {
        parent::__construct(session_id());
    }

    public function create($user_id, $ip_address = null) {
        $session_ip_address = ($ip_address) ? $ip_address : $_SERVER['REMOTE_ADDR'];
        parent::create($user_id, $session_ip_address);
    }

    public function check($update = true) {
        if (parent::check()) {
            if ($update) {
                $this->update();
            }
            return true;
        }

        return false;
    }

    public function update() {
        $this->updated = Pantry::getNow();
        $this->ip_address = $_SERVER['REMOTE_ADDR'];

        try {
            $sql_update_session = Pantry::$db->prepare("UPDATE user_sessions SET updated=:updated, ip_address=:ip_address WHERE id=:id");
            $sql_update_session->bindValue(':id', $this->id, PDO::PARAM_STR);
            $sql_update_session->bindValue(':updated', $this->updated, PDO::PARAM_STR);
            $sql_update_session->bindValue(':ip_address', $this->ip_address, PDO::PARAM_STR);
            if (!$sql_update_session->execute()) {
                throw new PantrySessionNotUpdatedException("Session could not be updated.");
            }
        }
        catch (PantrySessionNotUpdatedException $e) {
            Pantry::$logger->critical($e->getMessage());
            die();
        }
    }

    public function destroy() {
        parent::destroy();
        session_destroy();
    }

    public function isLoggedIn() {
        return boolval($this->id);
    }
}
