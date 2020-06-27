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

        try {
            PantryUser::checkUsername($username);
            $pantry->response = new PantryAPISuccess("CHECK_USERNAME_SUCCESS", $pantry->language['CHECK_USERNAME_SUCCESS'], [
                'available' => true,
                'message' => $pantry->language['ADMIN_USERS_USERNAME_AVAILABLE']
            ]);
        }
        catch (PantryUserValidationException $e) {
            $error_code = PantryUser::$error_map[get_class($e)];
            $pantry->response = new PantryAPISuccess("CHECK_USERNAME_SUCCESS", $pantry->language['CHECK_USERNAME_SUCCESS'], [
                'available' => false,
                'message' => $pantry->language[$error_code]
            ]);
            $pantry->response->respond();
        }

        $pantry->response->respond();
    }

    public static function createUser() {
        $pantry = new self();

        $new_user = new PantryUser(null);

        try {
            $new_user->setUsername($_POST['username']);
            $new_user->setFirstName($_POST['first_name']);
            $new_user->setLastName($_POST['last_name']);
            $new_user->setPassword($_POST['password']);
        }
        catch (PantryUserValidationException $e) {
            $error_code = PantryUser::$error_map[get_class($e)];
            $pantry->response = new PantryAPIError(422, $error_code, $pantry->language[$error_code], [
                'issue' => "validation",
                'field' => $e->getField()
            ]);
            $pantry->response->respond();
        }

        $admin = (!empty($_POST['admin']) && in_array($_POST['admin'], ["true", "1"], true));
        $new_user->setIsAdmin($admin);

        $disabled = (!empty($_POST['disabled']) && in_array($_POST['disabled'], ["true", "1"], true));
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

    public function editUser() {
        $pantry = new self();

        $user_id = $_POST['user_id'];
        if (empty($user_id)) {
            $pantry->response = new PantryAPIError(422, "NO_USER_ID", $pantry->language['NO_USER_ID']);
            $pantry->response->respond();
        }

        try {
            $user = new PantryUser($user_id);
        }
        catch (PantryUserNotFoundException $e) {
            $pantry->response = new PantryAPIError(422, "USER_NOT_FOUND", $pantry->language['USER_NOT_FOUND']);
            $pantry->response->respond();
            die();
        }

        try {
            if (isset($_POST['username'])) {
                $user->setUsername($_POST['username']);
            }
            if (isset($_POST['first_name'])) {
                $user->setFirstName($_POST['first_name']);
            }
            if (isset($_POST['last_name'])) {
                $user->setLastName($_POST['last_name']);
            }
            if (isset($_POST['password']) && !empty($_POST['password'])) {
                $user->setPassword($_POST['password']);
                PantryUserSession::purgeUser($user->getID());
            }
        }
        catch (PantryUserValidationException $e) {
            $error_code = PantryUser::$error_map[get_class($e)];
            $pantry->response = new PantryAPIError(422, $error_code, $pantry->language[$error_code], [
                'issue' => "validation",
                'field' => $e->getField()
            ]);
            $pantry->response->respond();
        }

        if (isset($_POST['admin']) && $user_id !== $pantry->current_user->getID()) {
            $admin = (!empty($_POST['admin']) && in_array($_POST['admin'], ["true", "1"], true));
            $user->setIsAdmin($admin);
        }
        if (isset($_POST['disabled']) && $user_id !== $pantry->current_user->getID()) {
            $disabled = (!empty($_POST['disabled']) && in_array($_POST['disabled'], ["true", "1"], true));
            $user->setIsDisabled($disabled);
            if ($user->getIsDisabled()) {
                PantryUserSession::purgeUser($user->getID());
            }
        }

        $user->save();
        Pantry::$logger->debug("User {$user_id} edited.");

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

        $user_id = $_POST['user_id'];

        if (empty($user_id)) {
            $pantry->response = new PantryAPIError(422, "NO_USER_ID", $pantry->language['NO_USER_ID']);
            $pantry->response->respond();
        }

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
        PantryRecipe::purgeUser($user->getID());
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
