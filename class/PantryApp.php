<?php

class PantryApp {
    /** @var PantryCurrentUser $current_user */
    protected $current_user;

    /** @var PantryCurrentUserSession $current_session */
    protected $current_session;

    /** @var PantryLanguage $language */
    protected $language;

    protected function __construct() {
        Pantry::initialize();
        $this->loadFiles();
        $this->loadLanguage();

        if (Pantry::$installer->getIsInstalled()) {
            $this->startSession();
        }

        Pantry::$logger->debug("App loaded.");
    }

    private function loadFiles() {
        $load_files = [
            "/class/PantryExceptions.php",
            "/class/PantryLanguage.php",
            "/class/PantryClamp.php",
            "/class/PantryUser.php",
            "/class/PantryCurrentUser.php",
            "/class/PantryUserSession.php",
            "/class/PantryCurrentUserSession.php",
            "/class/PantryTwoFactorKey.php",
            "/class/PantryTwoFactorLogin.php",
            "/class/PantryTwoFactorSession.php",
            "/class/PantryRecipe.php",
            "/class/PantryCourse.php",
            "/class/PantryCuisine.php",
            "/class/PantryImage.php",
            "/class/PantryRecipePermission.php"
        ];

        foreach ($load_files as $load_file) {
            require_once(Pantry::$php_root.$load_file);
        }
    }

    public function loadLanguage() {
        $lang_code = Pantry::$session->getTempLanguage() ?? Pantry::$config->get('app_language');
        try {
            $this->language = new PantryLanguage($lang_code);
        }
        catch (PantryLanguageNotFoundException $e) {
            Pantry::$logger->critical("Language code $lang_code not found.");
            die();
        }
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
        $this->current_user = new PantryCurrentUser(null);
    }
}
