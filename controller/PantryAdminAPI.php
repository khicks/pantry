<?php

class PantryAdminAPI extends PantryAPI {
    public function __construct() {
        parent::__construct(true);
        if (!$this->current_user->getIsAdmin()) {
            $this->response = new PantryAPIError(401, "NOT_ADMIN", $this->language->get('NOT_ADMIN'));
            $this->response->respond();
        }
    }

    public static function getUserCounts() {
        $pantry = new self();

        $counts = PantryUser::getUserCounts();

        $pantry->response = new PantryAPISuccess('GET_USER_COUNT_SUCCESS', "", [
            'counts' => $counts
        ]);
        $pantry->response->respond();
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

        $pantry->response = new PantryAPISuccess("LIST_USERS_SUCCESS", $pantry->language->get('LIST_USERS_SUCCESS'), [
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
                $pantry->response = new PantryAPIError(422, "NO_USER_ID_USERNAME", $pantry->language->get('NO_USER_ID_USERNAME'));
                $pantry->response->respond();
                die();
            }

            $user = new PantryUser($user_id);
        }
        catch (PantryUserNotFoundException $e) {
            $pantry->response = new PantryAPIError(422, "USER_NOT_FOUND", $pantry->language->get('USER_NOT_FOUND'));
            $pantry->response->respond();
            die();
        }

        $pantry->response = new PantryAPISuccess("GET_USER_SUCCESS", $pantry->language->get('GET_USER_SUCCESS'), [
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
            $pantry->response = new PantryAPISuccess("CHECK_USERNAME_SUCCESS", $pantry->language->get('CHECK_USERNAME_SUCCESS'), [
                'available' => true,
                'message' => $pantry->language->get('ADMIN_USERS_USERNAME_AVAILABLE')
            ]);
        }
        catch (PantryUserValidationException $e) {
            $error_code = PantryUser::$error_map[get_class($e)];
            $pantry->response = new PantryAPISuccess("CHECK_USERNAME_SUCCESS", $pantry->language->get('CHECK_USERNAME_SUCCESS'), [
                'available' => false,
                'message' => $pantry->language->get($error_code)
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
            $pantry->response = new PantryAPIError(422, $error_code, $pantry->language->get($error_code), [
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
            'message' => $pantry->language->get('CREATE_USER_SUCCESS')
        ];

        $pantry->response = new PantryAPISuccess("CREATE_USER_SUCCESS", $pantry->language->get('CREATE_USER_SUCCESS'));
        $pantry->response->respond();
    }

    public function editUser() {
        $pantry = new self();

        $user_id = $_POST['user_id'];
        if (empty($user_id)) {
            $pantry->response = new PantryAPIError(422, "NO_USER_ID", $pantry->language->get('NO_USER_ID'));
            $pantry->response->respond();
        }

        try {
            $user = new PantryUser($user_id);
        }
        catch (PantryUserNotFoundException $e) {
            $pantry->response = new PantryAPIError(422, "USER_NOT_FOUND", $pantry->language->get('USER_NOT_FOUND'));
            $pantry->response->respond();
            die();
        }

        if (Pantry::$config->get('demo_mode') && in_array($user->getUsername(), Pantry::$config->get('demo_protected_users'), true)) {
            $pantry->response = new PantryAPIError(403, "DEMO_PROTECTED_USER", $pantry->language->get('DEMO_PROTECTED_USER'));
            $pantry->response->respond();
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
            $pantry->response = new PantryAPIError(422, $error_code, $pantry->language->get($error_code), [
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
            'message' => $pantry->language->get('SAVE_USER_SUCCESS')
        ];

        $pantry->response = new PantryAPISuccess("SAVE_USER_SUCCESS", $pantry->language->get('SAVE_USER_SUCCESS'));
        $pantry->response->respond();
    }

    public static function deleteUser() {
        $pantry = new self();

        $user_id = $_POST['user_id'];

        if (empty($user_id)) {
            $pantry->response = new PantryAPIError(422, "NO_USER_ID", $pantry->language->get('NO_USER_ID'));
            $pantry->response->respond();
        }

        try {
            $user = new PantryUser($user_id);
        }
        catch (PantryUserNotFoundException $e) {
            $pantry->response = new PantryAPIError(422, "USER_NOT_FOUND", $pantry->language->get('USER_NOT_FOUND'));
            $pantry->response->respond();
            die();
        }

        if (Pantry::$config->get('demo_mode') && in_array($user->getUsername(), Pantry::$config->get('demo_protected_users'), true)) {
            $pantry->response = new PantryAPIError(403, "DEMO_PROTECTED_USER", $pantry->language->get('DEMO_PROTECTED_USER'));
            $pantry->response->respond();
        }

        if ($user->getID() === $pantry->current_user->getID()) {
            $pantry->response = new PantryAPIError(422, "DELETE_OWN_USER", $pantry->language->get('DELETE_OWN_USER'));
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
            'message' => $pantry->language->get('DELETE_USER_SUCCESS')
        ];

        $pantry->response = new PantryAPISuccess("DELETE_USER_SUCCESS", $pantry->language->get('DELETE_USER_SUCCESS'));
        $pantry->response->respond();
    }

    public static function createCourse() {
        $pantry = new self();

        $course = new PantryCourse();

        try {
            $course->setTitle($_POST['title']);
            $course->setSlug($_POST['slug']);
            $course->save();
        }
        catch (PantryCourseValidationException $e) {
            $error_code = PantryCourse::$error_map[get_class($e)];
            $pantry->response = new PantryAPIError(422, $error_code, $pantry->language->get($error_code), [
                'issue' => "validation",
                'field' => $e->getField()
            ]);
            $pantry->response->respond();
        }
        catch (PantryCourseNotSavedException $e) {
            $pantry->response = new PantryAPIError(500, "COURSE_NOT_SAVED", $pantry->language->get('COURSE_NOT_SAVED'));
            $pantry->response->respond();
        }

        $pantry->response = new PantryAPISuccess("CREATE_COURSE_SUCCESS", $pantry->language->get('CREATE_COURSE_SUCCESS'), [
            'id' => $course->getID(),
            'slug' => $course->getSlug(),
        ]);
        $pantry->response->respond();
    }

    public static function editCourse() {
        $pantry = new self();

        try {
            $course = new PantryCourse($_POST['id']);

            if (Pantry::$config->get('demo_mode') && in_array($course->getSlug(), Pantry::$config->get('demo_protected_courses'), true)) {
                $pantry->response = new PantryAPIError(403, "DEMO_PROTECTED_COURSE", $pantry->language->get('DEMO_PROTECTED_COURSE'));
                $pantry->response->respond();
            }

            $course->setTitle($_POST['title']);
            $course->setSlug($_POST['slug']);
            $course->save();
        }
        catch (PantryCourseNotFoundException $e) {
            $pantry->response = new PantryAPIError(404, "COURSE_NOT_FOUND", $pantry->language->get('COURSE_NOT_FOUND'));
            $pantry->response->respond();
        }
        catch (PantryCourseValidationException $e) {
            $error_code = PantryCourse::$error_map[get_class($e)];
            $pantry->response = new PantryAPIError(422, $error_code, $pantry->language->get($error_code), [
                'issue' => "validation",
                'field' => $e->getField()
            ]);
            $pantry->response->respond();
        }
        catch (PantryCourseNotSavedException $e) {
            $pantry->response = new PantryAPIError(500, "COURSE_NOT_SAVED", $pantry->language->get('COURSE_NOT_SAVED'));
            $pantry->response->respond();
        }

        $pantry->response = new PantryAPISuccess();
        $pantry->response->respond();
    }

    public static function deleteCourse() {
        $pantry = new self();

        try {
            $course = new PantryCourse($_POST['id']);

            if (Pantry::$config->get('demo_mode') && in_array($course->getSlug(), Pantry::$config->get('demo_protected_courses'), true)) {
                $pantry->response = new PantryAPIError(403, "DEMO_PROTECTED_COURSE", $pantry->language->get('DEMO_PROTECTED_COURSE'));
                $pantry->response->respond();
            }

            $course->delete($_POST['replace_id']);
        }
        catch (PantryCourseNotFoundException $e) {
            $pantry->response = new PantryAPIError(404, "COURSE_NOT_FOUND", $pantry->language->get('COURSE_NOT_FOUND'));
            $pantry->response->respond();
        }
        catch (PantryCourseDeleteReplacementIsSameException $e) {
            $pantry->response = new PantryAPIError(422, "COURSE_DELETE_REPLACE_IS_SAME", $pantry->language->get('COURSE_DELETE_REPLACE_IS_SAME'));
            $pantry->response->respond();
        }
        catch (PantryCourseNotDeletedException $e) {
            $pantry->response = new PantryAPIError(500, "COURSE_NOT_DELETED", $pantry->language->get('COURSE_NOT_DELETED'));
            $pantry->response->respond();
        }

        $pantry->response = new PantryAPISuccess();
        $pantry->response->respond();
    }

    public static function createCuisine() {
        $pantry = new self();

        $cuisine = new PantryCuisine();

        try {
            $cuisine->setTitle($_POST['title']);
            $cuisine->setSlug($_POST['slug']);
            $cuisine->save();
        }
        catch (PantryCuisineValidationException $e) {
            $error_code = PantryCuisine::$error_map[get_class($e)];
            $pantry->response = new PantryAPIError(422, $error_code, $pantry->language->get($error_code), [
                'issue' => "validation",
                'field' => $e->getField()
            ]);
            $pantry->response->respond();
        }
        catch (PantryCuisineNotSavedException $e) {
            $pantry->response = new PantryAPIError(500, "CUISINE_NOT_SAVED", $pantry->language->get('CUISINE_NOT_SAVED'));
            $pantry->response->respond();
        }

        $pantry->response = new PantryAPISuccess("CREATE_CUISINE_SUCCESS", $pantry->language->get('CREATE_CUISINE_SUCCESS'), [
            'id' => $cuisine->getID(),
            'slug' => $cuisine->getSlug(),
        ]);
        $pantry->response->respond();
    }

    public static function editCuisine() {
        $pantry = new self();

        try {
            $cuisine = new PantryCuisine($_POST['id']);

            if (Pantry::$config->get('demo_mode') && in_array($cuisine->getSlug(), Pantry::$config->get('demo_protected_cuisines'), true)) {
                $pantry->response = new PantryAPIError(403, "DEMO_PROTECTED_CUISINE", $pantry->language->get('DEMO_PROTECTED_CUISINE'));
                $pantry->response->respond();
            }

            $cuisine->setTitle($_POST['title']);
            $cuisine->setSlug($_POST['slug']);
            $cuisine->save();
        }
        catch (PantryCuisineNotFoundException $e) {
            $pantry->response = new PantryAPIError(404, "CUISINE_NOT_FOUND", $pantry->language->get('CUISINE_NOT_FOUND'));
            $pantry->response->respond();
        }
        catch (PantryCuisineValidationException $e) {
            $error_code = PantryCuisine::$error_map[get_class($e)];
            $pantry->response = new PantryAPIError(422, $error_code, $pantry->language->get($error_code), [
                'issue' => "validation",
                'field' => $e->getField()
            ]);
            $pantry->response->respond();
        }
        catch (PantryCuisineNotSavedException $e) {
            $pantry->response = new PantryAPIError(500, "CUISINE_NOT_SAVED", $pantry->language->get('CUISINE_NOT_SAVED'));
            $pantry->response->respond();
        }

        $pantry->response = new PantryAPISuccess();
        $pantry->response->respond();
    }

    public static function deleteCuisine() {
        $pantry = new self();

        try {
            $cuisine = new PantryCuisine($_POST['id']);

            if (Pantry::$config->get('demo_mode') && in_array($cuisine->getSlug(), Pantry::$config->get('demo_protected_cuisines'), true)) {
                $pantry->response = new PantryAPIError(403, "DEMO_PROTECTED_CUISINE", $pantry->language->get('DEMO_PROTECTED_CUISINE'));
                $pantry->response->respond();
            }

            $cuisine->delete($_POST['replace_id']);
        }
        catch (PantryCuisineNotFoundException $e) {
            $pantry->response = new PantryAPIError(404, "CUISINE_NOT_FOUND", $pantry->language->get('CUISINE_NOT_FOUND'));
            $pantry->response->respond();
        }
        catch (PantryCuisineDeleteReplacementIsSameException $e) {
            $pantry->response = new PantryAPIError(422, "CUISINE_DELETE_REPLACE_IS_SAME", $pantry->language->get('CUISINE_DELETE_REPLACE_IS_SAME'));
            $pantry->response->respond();
        }
        catch (PantryCuisineNotDeletedException $e) {
            $pantry->response = new PantryAPIError(500, "CUISINE_NOT_DELETED", $pantry->language->get('CUISINE_NOT_DELETED'));
            $pantry->response->respond();
        }

        $pantry->response = new PantryAPISuccess();
        $pantry->response->respond();
    }
}
