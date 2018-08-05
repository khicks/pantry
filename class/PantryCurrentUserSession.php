<?php

class PantryCurrentUserSession extends PantryUserSession {
    private $csrf_token;
    private $page_tracker;

    public function __construct() {
        parent::__construct(session_id());
        $this->prepare();
        $this->load();
    }

    private function prepare() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
            Pantry::$logger->debug("New CSRF token: {$_SESSION['csrf_token']}");
        }

        if (!isset($_SESSION['page_tracker'])) {
            $_SESSION['page_tracker'] = null;
        }
    }

    private function load() {
        $this->csrf_token = $_SESSION['csrf_token'];
        $this->page_tracker = $_SESSION['page_tracker'];
    }

    private function save() {
        $_SESSION['csrf_token'] = $this->csrf_token;
        $_SESSION['page_tracker'] = $this->page_tracker;
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

    public function getCSRF() {
        return $this->csrf_token;
    }

    public function getPageTracker() {
        return $this->page_tracker;
    }

    public function checkCSRF() {
        if (!Pantry::$config['csrf_required']) {
            Pantry::$logger->debug("CSRF not required.");
            return true;
        }

        if (!isset($_REQUEST['csrf_token'])) {
            Pantry::$logger->debug("Request did not contain CSRF token.");
            return false;
        }

        if ($_REQUEST['csrf_token'] === $this->csrf_token) {
            return true;
        }

        Pantry::$logger->debug("CSRF failed.");
        Pantry::$logger->debug("Expected: {$this->csrf_token}");
        Pantry::$logger->debug("Received: {$_REQUEST['csrf_token']}");
        return false;
    }

    public function trackPage() {
        $this->page_tracker = $_SERVER['REQUEST_URI'];
        $this->save();
    }
}
