<?php

class PantryAPI extends PantryApp {
    /** @var PantryAPISuccess|PantryAPIError $response */
    public $response;

    public function __construct($csrf_required = true) {
        parent::__construct();

        $this->loadFiles();

        if ($csrf_required && !$this->current_session->checkCSRF()) {
            $this->response = new PantryAPIError(401, "CSRF_FAILED", $this->language['CSRF_FAILED']);
            $this->response->respond();
        }
    }

    private function loadFiles() {
        $load_files = [
            "/class/PantryAPIResponse.php",
            "/class/PantryAPISuccess.php",
            "/class/PantryAPIError.php",
        ];

        foreach ($load_files as $load_file) {
            require_once(Pantry::$php_root.$load_file);
        }
    }

    private function requireLogin() {
        if (!$this->current_session->isLoggedIn()) {
            $this->response = new PantryAPIError(401, "NOT_LOGGED_IN", $this->language['NOT_LOGGED_IN']);
            $this->response->respond();
        }
    }

    private function requireLogout() {
        if ($this->current_session->isLoggedIn()) {
            $this->response = new PantryAPIError(401, "NOT_LOGGED_OUT", $this->language['NOT_LOGGED_OUT']);
            $this->response->respond();
        }
    }

    // ========================================
    // Entry points
    // ========================================
    public static function test() {
    }

    public static function me() {
        $pantry = new self(false);
        $pantry->response = new PantryAPISuccess("ME_SUCCESS", $pantry->language['ME_SUCCESS'], [
            'logged_in' => $pantry->current_session->isLoggedIn(),
            'csrf_token' => $pantry->current_session->getCSRF(),
            'user_id' => $pantry->current_user->getID(),
            'username' => $pantry->current_user->getUsername()
        ]);
        $pantry->response->respond();
    }

    public static function login() {
        $pantry = new self();
        $pantry->requireLogout();

        $username = (isset($_POST['username'])) ? $_POST['username'] : null;
        $password = (isset($_POST['password'])) ? $_POST['password'] : null;
        $verification = (isset($_POST['verification'])) ? $_POST['verification'] : null;
        $remember = (isset($_POST['remember'])) ? $_POST['remember'] : null;
        $two_factor_session_secret = (isset($_POST['two_factor_session_secret'])) ? $_POST['two_factor_session_secret'] : null;

        if (!$username || !$password) {
            $pantry->response = new PantryAPIError(422, "MISSING_USERNAME_PASSWORD", $pantry->language['MISSING_USERNAME_PASSWORD']);
            $pantry->response->respond();
        }

        $clamp = new PantryClamp();

        if ($pantry->current_session->isLoggedIn()) {
            $pantry->response = new PantryAPIError(401, "NOT_LOGGED_OUT", $pantry->language['NOT_LOGGED_OUT']);
            $clamp->wait(500);
            $pantry->response->respond();
        }

        $check_login = PantryUser::checkLogin($username, $password, $verification, $two_factor_session_secret);

        if ($check_login === "fail") {
            $pantry->response = new PantryAPIError(401, "BAD_USERNAME_PASSWORD", $pantry->language['BAD_USERNAME_PASSWORD']);
        }
        elseif ($check_login === "disabled") {
            $pantry->response = new PantryAPIError(401, "USER_DISABLED", $pantry->language['USER_DISABLED']);
        }
        elseif ($check_login === "two_factor_required") {
            $pantry->response = new PantryAPIError(401, "TWO_FACTOR_REQUIRED", $pantry->language['TWO_FACTOR_REQUIRED']);
        }
        elseif ($check_login === "two_factor_incorrect") {
            $pantry->response = new PantryAPIError(401, "TWO_FACTOR_INCORRECT", $pantry->language['TWO_FACTOR_INCORRECT']);
        }
        elseif ($check_login === "success") {
            $user_id = PantryUser::lookupUsername($username);
            $pantry->current_session->create($user_id);

            try {
                $pantry->current_user = new PantryCurrentUser($user_id);
            }
            catch (PantryUserNotFoundException $e) {
                Pantry::$logger->emergency($e->getMessage());
                $pantry->response = new PantryAPIError(500, "INTERNAL_ERROR", $pantry->language['API_INTERNAL_ERROR']);
                $clamp->wait(500);
                $pantry->response->respond();
            }

            $two_factor_session_secret = null;
            if (in_array($remember, ["true", "1"], true)) {
                Pantry::$logger->debug("Attempting to remember session");
                $two_factor_session = new PantryTwoFactorSession();
                $two_factor_session->create($user_id);
                setcookie("pantry_two_factor_session", $two_factor_session->getID(), time()+2592000, Pantry::$web_root, null, true, true);
                $two_factor_session_secret = $two_factor_session->getSecret();
            }

            $pantry->response = new PantryAPISuccess("LOGIN_SUCCESS", $pantry->language['LOGIN_SUCCESS'], [
                'logged_in' => $pantry->current_session->isLoggedIn(),
                'csrf_token' => $pantry->current_session->getCSRF(),
                'user_id' => $pantry->current_user->getID(),
                'username' => $pantry->current_user->getUsername(),
                'is_admin' => $pantry->current_user->getIsAdmin(),
                'two_factor_session_secret' => $two_factor_session_secret
            ]);
        }
        else {
            Pantry::$logger->critical("Could not log in user.");
            $pantry->response = new PantryAPIError(500, "INTERNAL_ERROR", $pantry->language['API_INTERNAL_ERROR']);
        }

        $clamp->wait(500);
        $pantry->response->respond();
    }

    public static function logout() {
        $pantry = new self();
        $pantry->requireLogin();

        $pantry->current_session->destroy();

        $pantry->response = new PantryAPISuccess("LOGOUT_SUCCESS", $pantry->language['LOGOUT_SUCCESS']);
        $pantry->response->respond();
    }
}
