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
    public static function me() {
        $pantry = new self(false);
        $pantry->response = new PantryAPISuccess("ME_SUCCESS", $pantry->language['ME_SUCCESS'], [
            'logged_in' => $pantry->current_session->isLoggedIn(),
            'csrf_token' => $pantry->current_session->getCSRF(),
            'user_id' => $pantry->current_user->getID(),
            'username' => $pantry->current_user->getUsername(),
            'is_admin' => $pantry->current_user->getIsAdmin(),
            'first_name' => $pantry->current_user->getFirstName(),
            'last_name' => $pantry->current_user->getLastName()
        ]);
        $pantry->response->respond();
    }

    public static function language() {
        $pantry = new self(false);
        $pantry->response = new PantryAPISuccess("LANGUAGE_SUCCESS", $pantry->language['LANGUAGE_SUCCESS'], $pantry->language);
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
                setcookie("pantry_two_factor_session", $two_factor_session->getID(), time()+2592000, Pantry::$cookie_path, null, true, true);
                $two_factor_session_secret = $two_factor_session->getSecret();
            }

            $pantry->current_user->setLastLogin();
            $pantry->current_user->save();

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

    public static function getFeaturedRecipes() {
        $pantry = new self(false);

        $featured = PantryRecipe::getFeaturedRecipes();
        $pantry->response = new PantryAPISuccess("FEATURED_RECIPES_SUCCESS", "", [
            'recipes' => $featured,
            'lang' => [
                'days_short' => $pantry->language['DAYS_SHORT'],
                'hours_short' => $pantry->language['HOURS_SHORT'],
                'minutes_short' => $pantry->language['MINUTES_SHORT'],
            ]
        ]);
        $pantry->response->respond();
    }

    public static function getRecipe($slug) {
        $pantry = new self(false);
        try {
            $recipe = PantryRecipe::constructBySlug($slug);
            $permission = PantryRecipePermission::constructBySubjectAndObject($recipe, $pantry->current_user);
            if ($permission->getLevel() < 1) {
                throw new PantryRecipeNotFoundException("No permission.");
            }
        }
        catch (PantryRecipeNotFoundException $e) {
            $pantry->response = new PantryAPIError(404, "RECIPE_NOT_FOUND", $pantry->language['RECIPE_NOT_FOUND']);
            $pantry->response->respond();
            die();
        }

        $recipe_permission = PantryRecipePermission::constructBySubjectAndObject($recipe, $pantry->current_user);

        $pantry->response = new PantryAPISuccess("RECIPE_SUCCESS", $pantry->language['RECIPE_SUCCESS'], [
            'id' => $recipe->getID(),
            'created' => $recipe->getCreated(),
            'updated' => $recipe->getUpdated(),
            'title' => $recipe->getTitle(),
            'slug' => $recipe->getSlug(),
            'blurb' => $recipe->getBlurb(),
            'description_raw' => $recipe->getDescription(),
            'description_html' => $recipe->getDescriptionHTML(),
            'servings' => $recipe->getServings(),
            'prep_time' => $recipe->getPrepTime(),
            'cook_time' => $recipe->getCookTime(),
            'ingredients_raw' => $recipe->getIngredients(),
            'ingredients_html' => $recipe->getIngredientsHTML(),
            'directions_raw' => $recipe->getDirections(),
            'directions_html' => $recipe->getDirectionsHTML(),
            'source' => $recipe->getSource(),
            'is_public' => $recipe->getIsPublic(),
            'permission_level' => $recipe_permission->getLevel(),
            'author' => (is_null($recipe->getAuthor())) ? null : [
                'username' => $recipe->getAuthor()->getusername(),
                'first_name' => $recipe->getAuthor()->getFirstName(),
                'last_name' => $recipe->getAuthor()->getLastName(),
                'display_name' => $recipe->getAuthor()->getDisplayName()
            ],
            'course' => (is_null($recipe->getCourse())) ? null : [
                'id' => $recipe->getCourse()->getID(),
                'title' => $recipe->getCourse()->getTitle(),
                'slug' => $recipe->getCourse()->getSlug()
            ],
            'cuisine' => (is_null($recipe->getCuisine())) ? null : [
                'id' => $recipe->getCuisine()->getID(),
                'title' => $recipe->getCuisine()->getTitle(),
                'slug' => $recipe->getCuisine()->getSlug()
            ],
            'image' => (is_null($recipe->getImage())) ? null : [
                'path' => $recipe->getImage()->getWebPath($recipe->getSlug())
            ],
            'lang' => [
                'day' => $pantry->language['DAY'],
                'days' => $pantry->language['DAYS'],
                'hour' => $pantry->language['HOUR'],
                'hours' => $pantry->language['HOURS'],
                'minute' => $pantry->language['MINUTE'],
                'minutes' => $pantry->language['MINUTES'],
            ]
        ]);
        $pantry->response->respond();
    }

    public static function getImage($img) {
        $pantry = new self(false);
        $img_elements = explode(".", $img);
        try {
            $recipe = PantryRecipe::constructBySlug($img_elements[0]);
        }
        catch (PantryRecipeNotFoundException $e) {
            $pantry->response = new PantryAPIError(404, "RECIPE_NOT_FOUND", $pantry->language['RECIPE_NOT_FOUND']);
            $pantry->response->respond();
            die();
        }

        $image = $recipe->getImage();

        if (!is_a($image, "PantryImage")) {
            $pantry->response = new PantryAPIError(404, "IMAGE_NOT_FOUND", $pantry->language['IMAGE_NOT_FOUND']);
            $pantry->response->respond();
            die();
        }

        if ($image->getExtension() !== $img_elements[1]) {
            $pantry->response = new PantryAPIError(404, "IMAGE_EXTENSION_INVALID", $pantry->language['IMAGE_EXTENSION_INVALID']);
            $pantry->response->respond();
            die();
        }

        if (isset($_GET['download']) && !in_array(strtolower($_GET['download']), ["0", "false"], true)) {
            $image->download($img);
        }
        else {
            $image->display();
        }
    }

    public static function listCourses() {
        $pantry = new self();

        $search = (!empty($_GET['search'])) ? $_GET['search'] : null;
        $sort_by = (!empty($_GET['sort_by'])) ? $_GET['sort_by'] : "title";
        $courses = PantryCourse::list($search, $sort_by);

        $pantry->response = new PantryAPISuccess("LIST_COURSES_SUCCESS", $pantry->language['LIST_COURSES_SUCCESS'], [
            'courses' => $courses
        ]);
        $pantry->response->respond();
    }

    public static function listCuisines() {
        $pantry = new self();

        $search = (!empty($_GET['search'])) ? $_GET['search'] : null;
        $sort_by = (!empty($_GET['sort_by'])) ? $_GET['sort_by'] : "title";
        $cuisines = PantryCuisine::list($search, $sort_by);

        $pantry->response = new PantryAPISuccess("LIST_CUISINES_SUCCESS", $pantry->language['LIST_CUISINES_SUCCESS'], [
            'cuisines' => $cuisines
        ]);
        $pantry->response->respond();
    }

    public static function listCoursesAndCuisines() {
        $pantry = new self();

        $search = (!empty($_GET['search'])) ? $_GET['search'] : null;
        $sort_by = (!empty($_GET['sort_by'])) ? $_GET['sort_by'] : "title";

        $courses = PantryCourse::list($search, $sort_by);
        $cuisines = PantryCuisine::list($search, $sort_by);

        $pantry->response = new PantryAPISuccess("LIST_COURSES_CUISINES_SUCCESS", $pantry->language['LIST_COURSES_CUISINES_SUCCESS'], [
            'courses' => $courses,
            'cuisines' => $cuisines
        ]);
        $pantry->response->respond();
    }

    public static function editRecipe() {
        $pantry = new self();

        try {
            $recipe = new PantryRecipe($_POST['id']);
            $permission = PantryRecipePermission::constructBySubjectAndObject($recipe, $pantry->current_user);
            if ($permission->getLevel() < 1) {
                throw new PantryRecipeNotFoundException("No read permission.");
            }
            if ($permission->getLevel() < 2) {
                throw new PantryRecipePermissionDeniedException("No write permission.");
            }
        }
        catch (PantryRecipeNotFoundException $e) {
            $pantry->response = new PantryAPIError(404, "RECIPE_NOT_FOUND", $pantry->language['RECIPE_NOT_FOUND']);
            $pantry->response->respond();
            die();
        }
        catch (PantryRecipePermissionDeniedException $e) {
            $pantry->response = new PantryAPIError(403, "ACCESS_DENIED", $pantry->language['ACCESS_DENIED']);
            $pantry->response->respond();
            die();
        }

        if (!PantryRecipe::checkSlugAvailable($_POST['slug'], $_POST['id'])) {
            $pantry->response = new PantryAPIError(422, "RECIPE_SLUG_UNAVAILABLE", $pantry->language['SLUG_UNAVAILABLE']);
            $pantry->response->respond();
        }

        //TODO: Form security
        $recipe->setTitle($_POST['title']);
        $recipe->setSlug($_POST['slug']);
        $recipe->setBlurb($_POST['blurb']);
        $recipe->setDescription($_POST['description']);
        $recipe->setServings($_POST['servings']);
        $recipe->setPrepTime($_POST['prep_time']);
        $recipe->setCookTime($_POST['cook_time']);
        $recipe->setIngredients($_POST['ingredients']);
        $recipe->setDirections($_POST['directions']);
        $recipe->setCourse(new PantryCourse($_POST['course_id']));
        $recipe->setCuisine(new PantryCuisine($_POST['cuisine_id']));
        $recipe->save();

        $pantry->response = new PantryAPISuccess();
        $pantry->response->respond();
    }
}
