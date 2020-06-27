<?php

class PantrySession {
    public function __construct() {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '10');

        session_name("pantry_session");
        session_set_cookie_params(0, Pantry::$cookie_path, null, true, true);
        session_set_save_handler(
            [$this, "open"],
            [$this, "close"],
            [$this, "read"],
            [$this, "write"],
            [$this, "destroy"],
            [$this, "gc"],
            [$this, "create_sid"],
            [$this, "validate_sid"],
            [$this, "update_timestamp"]
        );
        session_start();
    }

    public function open() {
        return true;
    }

    public function close() {
        return true;
    }

    public function read($session_id) {
        $sql_read_session_data = Pantry::$db->prepare("SELECT session_data FROM sessions WHERE id=:id");
        $sql_read_session_data->bindValue(':id', $session_id, PDO::PARAM_STR);
        $sql_read_session_data->execute();

        if ($session_data_row = $sql_read_session_data->fetch(PDO::FETCH_ASSOC)) {
            return $session_data_row['session_data'];
        }

        return "";
    }

    public function write($session_id, $session_data) {
        $sql_write_session_data = Pantry::$db->prepare("REPLACE INTO sessions (id, updated, session_data) VALUES (:id, :updated, :session_data)");
        $sql_write_session_data->bindValue(':id', $session_id, PDO::PARAM_STR);
        $sql_write_session_data->bindValue(':updated', Pantry::getNow(), PDO::PARAM_STR);
        $sql_write_session_data->bindValue(':session_data', $session_data, PDO::PARAM_STR);
        $sql_write_session_data->execute();

        if ($sql_write_session_data->execute()) {
            return true;
        }

        return false;
    }

    public function destroy($session_id) {
        Pantry::$logger->info("Destroying session: $session_id");
        $sql_destroy_session = Pantry::$db->prepare("DELETE FROM sessions WHERE id=:id");
        $sql_destroy_session->bindValue(':id', $session_id, PDO::PARAM_STR);

        if ($sql_destroy_session->execute()) {
            Pantry::$logger->info("Session destroyed: {$sql_destroy_session->rowCount()}");
            setcookie("pantry_session", null, -1, Pantry::$cookie_path, null, true, true);
            return true;
        }

        Pantry::$logger->warning("Session destroy failed: $session_id");
        return false;
    }

    public function gc($max_lifetime) {
        Pantry::$logger->debug("Garbage collecting sessions.");
        $sql_gc_sessions = Pantry::$db->prepare("DELETE FROM sessions WHERE updated <= NOW() - INTERVAL :seconds SECOND");
        $sql_gc_sessions->bindValue(':seconds', $max_lifetime, PDO::PARAM_INT);

        if ($sql_gc_sessions->execute()) {
            return true;
        }

        return false;
    }

    public function create_sid() {
        return Pantry::generateUUID();
    }

    public function validate_sid($session_id) {
        $expression = "/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/";

        if (preg_match($expression, $session_id)) {
            return true;
        }

        return false;
    }

    public function update_timestamp($session_id) {
        $sql_update_session_time = Pantry::$db->prepare("UPDATE sessions SET updated=:updated WHERE id=:id");
        $sql_update_session_time->bindValue(':id', $session_id, PDO::PARAM_STR);
        $sql_update_session_time->bindValue(':updated', date("Y-m-d H:i:s"));

        if ($sql_update_session_time->execute()) {
            return true;
        }

        return false;
    }
}
