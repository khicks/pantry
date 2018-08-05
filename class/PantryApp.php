<?php

class PantryApp {
    /** @var PantryCurrentUser */
    protected $current_user;

    /** @var PantryCurrentUserSession */
    protected $current_session;

    protected $language;

    protected function __construct() {
        Pantry::initialize();
        $this->loadFiles();
        $this->loadLanguage();
        $this->startSession();

        Pantry::$logger->info("App loaded.");
    }

    private function loadFiles() {
        $load_files = [
            "/class/PantryExceptions.php",
            "/class/PantryClamp.php",
            "/class/PantryUser.php",
            "/class/PantryCurrentUser.php",
            "/class/PantryUserSession.php",
            "/class/PantryCurrentUserSession.php",
            "/class/PantryTwoFactorKey.php",
            "/class/PantryTwoFactorLogin.php",
            "/class/PantryTwoFactorSession.php"
        ];

        foreach ($load_files as $load_file) {
            require_once(Pantry::$php_root.$load_file);
        }
    }

    public function loadLanguage() {
        $lang_code = (isset(Pantry::$config['language'])) ? Pantry::$config['language'] : "en_us";
        $lang_file = Pantry::$php_root . "/language/{$lang_code}.php";
        $this->language = (file_exists($lang_file)) ? require_once($lang_file) : require_once(Pantry::$php_root."/language/en_us.php");
    }

    private function startSession() {
        $this->current_session = new PantryCurrentUserSession();

        try {
            $session_user = new PantryCurrentUser($this->current_session->getUserID());
            $session_user_disabled = $session_user->getIsDisabled();

            if ($this->current_session->check() && !$session_user_disabled) {
                $this->current_user = $session_user;
                Pantry::$logger->debug("User {$this->current_user->getUsername()} is logged in.");
                return;
            }

            if ($session_user_disabled) {
                $this->current_session->destroy();
                Pantry::$logger->debug("Disabled user's session was destroyed: {$session_user->getUsername()}");
            }
        }
        catch (PantryUserNotFoundException $e) {
            $this->current_session->destroy();
            Pantry::$logger->debug("Session of unfound user was destroyed.");
            try {
                $this->current_user = new PantryCurrentUser(null);
            }
            catch (PantryUserNotFoundException $e) {
                Pantry::$logger->emergency("User not found on null user (1).");
                die();
            }
        }

        Pantry::$logger->debug("User is not logged in.");
        try {
            $this->current_user = new PantryCurrentUser(null);
        }
        catch (PantryUserNotFoundException $e) {
            Pantry::$logger->emergency("User not found on null user (1).");
            die();
        }
    }
}
