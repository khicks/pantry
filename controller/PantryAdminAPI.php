<?php

class PantryAdminAPI extends PantryAPI {
    public function __construct() {
        parent::__construct(true);
        if (!$this->current_user->getIsAdmin()) {
            $this->response = new PantryAPIError(401, "NOT_ADMIN", $this->language['NOT_ADMIN']);
            $this->response->respond();
        }
    }

    public static function getUsers() {
        $pantry = new self();

        $search = (!empty($_GET['search'])) ? $_GET['search'] : null;
        $sort_by = (!empty($_GET['sort_by'])) ? $_GET['sort_by'] : "username";
        $users = PantryUser::listUsers($search, $sort_by);

        foreach ($users as &$user) {
            if ($user['id'] === $pantry->current_user->getID()) {
                $user['is_self'] = true;
                break;
            }
        }

        $pantry->response = new PantryAPISuccess("LIST_USERS_SUCCESS", $pantry->language['LIST_USERS_SUCCESS'], [
            'users' => $users
        ]);
        $pantry->response->respond();
    }

    public static function getUser() {
        $pantry = new self();

        try {
            if (!empty($_GET['user_id'])) {
                $user_id = $_GET['user_id'];
            }
            elseif (!empty($_GET['username'])) {
                $username = $_GET['username'];
                $user_id = PantryUser::lookupUsername($username);
                if ($user_id === false) {
                    throw new PantryUserNotFoundException("User not found.");
                }
            }
            else {
                $pantry->response = new PantryAPIError(422, "NO_USER_ID_USERNAME", $pantry->language['NO_USER_ID_USERNAME']);
                $pantry->response->respond();
                die();
            }

            $user = new PantryUser($user_id);
        }
        catch (PantryUserNotFoundException $e) {
            $pantry->response = new PantryAPIError(422, "USER_NOT_FOUND", $pantry->language['USER_NOT_FOUND']);
            $pantry->response->respond();
            die();
        }

        $pantry->response = new PantryAPISuccess("GET_USER_SUCCESS", $pantry->language['GET_USER_SUCCESS'], [
            'user' => [
                'id' => $user->getID(),
                'created' => $user->getCreated(),
                'username' => $user->getUsername(),
                'is_admin' => $user->getIsAdmin(),
                'is_disabled' => $user->getIsDisabled(),
                'last_login' => $user->getLastLogin(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'display_name' => $user->getDisplayName(),
                'two_factor' => $user->twoFactorRequired(),
                'is_self' => ($user->getID() === $pantry->current_user->getID())
            ]
        ]);
        $pantry->response->respond();
    }

    public static function checkUsername() {
        $pantry = new self();
        $username = $_GET['username'];

        $user_check = PantryUser::checkUsername($username);
        $available = false;

        if ($user_check === "short") {
            $message = $pantry->language['ADMIN_USERS_ERROR_USERNAME_SHORT'];
        }
        elseif ($user_check === "long") {
            $message = $pantry->language['ADMIN_USERS_ERROR_USERNAME_LONG'];
        }
        elseif ($user_check === "invalid") {
            $message = $pantry->language['ADMIN_USERS_ERROR_USERNAME_INVALID'];
        }
        elseif ($user_check === "taken") {
            $message = $pantry->language['ADMIN_USERS_ERROR_USERNAME_TAKEN'];
        }
        elseif ($user_check === "available") {
            $available = true;
            $message = $pantry->language['ADMIN_USERS_USERNAME_AVAILABLE'];
        }
        else {
            $pantry->response = new PantryAPIError(500, "INTERNAL_ERROR", $pantry->language['INTERNAL_ERROR']);
            $pantry->response->respond();
            die();
        }

        $pantry->response = new PantryAPISuccess("CHECK_USERNAME_SUCCESS", $pantry->language['CHECK_USERNAME_SUCCESS'], [
            'available' => $available,
            'message' => $message
        ]);
        $pantry->response->respond();
    }

    public static function createUser() {
        $pantry = new self();
        $errors = [];
        $first_name = $last_name = $password = null;

        // Check username
        if (empty($_POST['username'])) {
            $errors['username'] = [
                'code' => "none",
                'message' => $pantry->language['ADMIN_USERS_ERROR_USERNAME_NONE']
            ];
        }
        else {
            $check_user = PantryUser::checkUsername($_POST['username']);
            if (in_array($check_user, ["none", "short", "long", "invalid", "taken"], true)) {
                $errors['username'] = [
                    'code' => $check_user,
                    'message' => $pantry->language['ADMIN_USERS_ERROR_USERNAME_'.strtoupper($check_user)]
                ];
            }
        }

        // Check first name
        if (!empty($_POST['first_name'])) {
            $first_name = trim($_POST['first_name']);
            if (strlen($first_name) > 32) {
                $errors['first_name'] = [
                    'code' => "long",
                    'message' => $pantry->language['ADMIN_USERS_ERROR_FIRSTNAME_LONG']
                ];
            }
            elseif (!preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ0-9-'\"\\s]*$/", $first_name)) {
                $errors['first_name'] = [
                    'code' => "invalid",
                    'message' => $pantry->language['ADMIN_USERS_ERROR_FIRSTNAME_INVALID']
                ];
            }
        }

        // Check last name
        if (!empty($_POST['last_name'])) {
            $last_name = trim($_POST['last_name']);
            if (strlen($last_name) > 32) {
                $errors['last_name'] = [
                    'code' => "long",
                    'message' => $pantry->language['ADMIN_USERS_ERROR_LASTNAME_LONG']
                ];
            }
            elseif (!preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ0-9-'\"\\s]*$/", $last_name)) {
                $errors['last_name'] = [
                    'code' => "invalid",
                    'message' => $pantry->language['ADMIN_USERS_ERROR_LASTNAME_INVALID']
                ];
            }
        }

        // Check password
        if (empty($_POST['password'])) {
            $errors['password1'] = [
                'code' => "none",
                'message' => $pantry->language['ADMIN_USERS_ERROR_PASSWORD1_NONE']
            ];
        }

        // Respond to errors
        if (!empty($errors)) {
            $pantry->response = new PantryAPIError(422, "CREATE_USER_FAILED", $pantry->language['CREATE_USER_FAILED'], $errors);
            $pantry->response->respond();
            die();
        }

        // Create user
        $admin = (!empty($_POST['admin']) && in_array($_POST['admin'], ["true", "1"], true));
        $disabled = (!empty($_POST['disabled']) && in_array($_POST['disabled'], ["true", "1"], true));

        try {
            $new_user = new PantryUser(null);
        }
        catch (PantryUserNotFoundException $e) {
            Pantry::$logger->emergency("New user could not be instantiated.");
            die();
        }
        $new_user->setUsername($_POST['username']);
        $new_user->setFirstName($first_name);
        $new_user->setLastName($last_name);
        $new_user->setPassword($_POST['password']);
        $new_user->setIsAdmin($admin);
        $new_user->setIsDisabled($disabled);
        $new_user->save();
        Pantry::$logger->debug("User {$_POST['username']} created.");

        $_SESSION['alert'] = [
            'type' => "success",
            'icon' => "check",
            'message' => $pantry->language['CREATE_USER_SUCCESS']
        ];

        $pantry->response = new PantryAPISuccess("CREATE_USER_SUCCESS", $pantry->language['CREATE_USER_SUCCESS']);
        $pantry->response->respond();
    }

    public static function editUser() {
        $pantry = new self();
        $errors = [];

        if (empty($_POST['user_id'])) {
            $pantry->response = new PantryAPIError(422, "NO_USER_ID", $pantry->language['NO_USER_ID']);
            $pantry->response->respond();
        }

        $user_id = $_POST['user_id'];
        try {
            $user = new PantryUser($user_id);
        }
        catch (PantryUserNotFoundException $e) {
            $pantry->response = new PantryAPIError(422, "USER_NOT_FOUND", $pantry->language['USER_NOT_FOUND']);
            $pantry->response->respond();
            die();
        }

        // Check username
        if (isset($_POST['username'])) {
            if (empty($_POST['username'])) {
                $errors['username'] = [
                    'code' => "none",
                    'message' => $pantry->language['ADMIN_USERS_ERROR_USERNAME_NONE']
                ];
            }
            elseif ($_POST['username'] !== $user->getUsername()) {
                $check_user = PantryUser::checkUsername($_POST['username']);
                if (in_array($check_user, ["none", "short", "long", "invalid", "taken"], true)) {
                    $errors['username'] = [
                        'code' => $check_user,
                        'message' => $pantry->language['ADMIN_USERS_ERROR_USERNAME_'.strtoupper($check_user)]
                    ];
                }
                else {
                    $username = $_POST['username'];
                }
            }
        }

        // Check first name
        if (isset($_POST['first_name'])) {
            $first_name = preg_replace('/\s+/', " ", $_POST['first_name']);
            if (strlen($first_name) > 32) {
                $errors['first_name'] = [
                    'code' => "long",
                    'message' => $pantry->language['ADMIN_USERS_ERROR_FIRSTNAME_LONG']
                ];
            }
            elseif (!preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ0-9-'\"\\s]*$/", $first_name)) {
                $errors['first_name'] = [
                    'code' => "invalid",
                    'message' => $pantry->language['ADMIN_USERS_ERROR_FIRSTNAME_INVALID']
                ];
            }
        }

        // Check last name
        if (isset($_POST['last_name'])) {
            $last_name = preg_replace('/\s+/', " ", $_POST['last_name']);
            if (strlen($last_name) > 32) {
                $errors['last_name'] = [
                    'code' => "long",
                    'message' => $pantry->language['ADMIN_USERS_ERROR_LASTNAME_LONG']
                ];
            }
            elseif (!preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ0-9-'\"\\s]*$/", $last_name)) {
                $errors['last_name'] = [
                    'code' => "invalid",
                    'message' => $pantry->language['ADMIN_USERS_ERROR_LASTNAME_INVALID']
                ];
            }
        }

        // Check password
        if (isset($_POST['password']) && !empty($_POST['password'])) {
            $password = $_POST['password'];
        }

        // Check admin
        if (isset($_POST['admin']) && $user_id !== $pantry->current_user->getID()) {
            $admin = (!empty($_POST['admin']) && in_array($_POST['admin'], ["true", "1"], true));
        }

        // Check disabled
        if (isset($_POST['disabled']) && $user_id !== $pantry->current_user->getID()) {
            $disabled = (!empty($_POST['disabled']) && in_array($_POST['disabled'], ["true", "1"], true));
        }

        // Check disable two factor auth
        if (isset($_POST['disable_two_factor_auth'])) {
            $disable_two_factor_auth = (!empty($_POST['disable_two_factor_auth']) && in_array($_POST['disable_two_factor_auth'], ["true", "1"], true));
        }

        // Respond to errors
        if (!empty($errors)) {
            $pantry->response = new PantryAPIError(422, "CREATE_USER_FAILED", $pantry->language['CREATE_USER_FAILED'], $errors);
            $pantry->response->respond();
            die();
        }

        if (isset($username)) {
            $user->setUsername($username);
        }
        if (isset($first_name)) {
            $user->setFirstName($first_name);
        }
        if (isset($last_name)) {
            $user->setLastName($last_name);
        }
        if (isset($password)) {
            $user->setPassword($password);
            PantryUserSession::purgeUser($user->getID());
        }
        if (isset($admin)) {
            $user->setIsAdmin($admin);
        }
        if (isset($disabled)) {
            $user->setIsDisabled($disabled);
            if ($disabled) {
                PantryUserSession::purgeUser($user->getID());
            }
        }
        $user->save();

        if (isset($disable_two_factor_auth) && $disable_two_factor_auth) {
            $user->disableTwoFactorAuth();
        }

        $_SESSION['alert'] = [
            'type' => "success",
            'icon' => "check",
            'message' => $pantry->language['SAVE_USER_SUCCESS']
        ];

        $pantry->response = new PantryAPISuccess("SAVE_USER_SUCCESS", $pantry->language['SAVE_USER_SUCCESS']);
        $pantry->response->respond();
    }

    public static function deleteUser() {
        $pantry = new self();

        if (empty($_POST['user_id'])) {
            $pantry->response = new PantryAPIError(422, "NO_USER_ID", $pantry->language['NO_USER_ID']);
            $pantry->response->respond();
        }

        $user_id = $_POST['user_id'];
        try {
            $user = new PantryUser($user_id);
        }
        catch (PantryUserNotFoundException $e) {
            $pantry->response = new PantryAPIError(422, "USER_NOT_FOUND", $pantry->language['USER_NOT_FOUND']);
            $pantry->response->respond();
            die();
        }

        if ($user->getID() === $pantry->current_user->getID()) {
            $pantry->response = new PantryAPIError(422, "DELETE_OWN_USER", $pantry->language['DELETE_OWN_USER']);
            $pantry->response->respond();
        }

        PantryTwoFactorSession::purgeUser($user->getID());
        PantryTwoFactorLogin::purgeUser($user->getID());
        PantryTwoFactorKey::purgeUser($user->getID());
        PantryUserSession::purgeUser($user->getID());
        $user->delete();

        $_SESSION['alert'] = [
            'type' => "success",
            'icon' => "check",
            'message' => $pantry->language['DELETE_USER_SUCCESS']
        ];

        $pantry->response = new PantryAPISuccess("DELETE_USER_SUCCESS", $pantry->language['DELETE_USER_SUCCESS']);
        $pantry->response->respond();
    }
}
