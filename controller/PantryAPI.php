<?php

class PantryAPI extends PantryApp {
    /** @var PantryAPISuccess|PantryAPIError $response */
    public $response;

    public function __construct($csrf_required = true, $install_required = true, $update_required = true) {
        parent::__construct();
        $this->loadFiles();

        if ($install_required && !Pantry::$installer->getIsInstalled()) {
            Pantry::$logger->warning("API call attempted before installation.");
            $this->response = new PantryAPIError(403, "PANTRY_NOT_INSTALLED", $this->language->get('PANTRY_NOT_INSTALLED'));
            $this->response->respond();
        }

        if ($install_required && $update_required && !Pantry::$updater->getIsUpdated()) {
            Pantry::$logger->warning("API call attempted before update.");
            $this->response = new PantryAPIError(403, "PANTRY_NOT_UPDATED", $this->language->get('PANTRY_NOT_UPDATED'));
            $this->response->respond();
        }

        if ($csrf_required && !Pantry::$session->checkCSRF()) {
            $this->response = new PantryAPIError(401, "CSRF_FAILED", $this->language->get('CSRF_FAILED'));
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
            $this->response = new PantryAPIError(401, "NOT_LOGGED_IN", $this->language->get('NOT_LOGGED_IN'));
            $this->response->respond();
        }
    }

    private function requireLogout() {
        if ($this->current_session->isLoggedIn()) {
            $this->response = new PantryAPIError(401, "NOT_LOGGED_OUT", $this->language->get('NOT_LOGGED_OUT'));
            $this->response->respond();
        }
    }

    private function requireNotInstalled() {
        if (Pantry::$installer->getIsInstalled()) {
            $this->response = new PantryAPIError(403, "PANTRY_ALREADY_INSTALLED", $this->language->get('PANTRY_ALREADY_INSTALLED'));
            $this->response->respond();
        }
    }

    private function requireNotUpdated() {
        if (Pantry::$updater->getIsUpdated()) {
            $this->response = new PantryAPIError(403, "PANTRY_ALREADY_UPDATED", $this->language->get('PANTRY_ALREADY_UPDATED'));
            $this->response->respond();
        }
    }

    // ========================================
    // Entry points - installation
    // ========================================
    public static function install() {
        $pantry = new self(true, false);
        $pantry->requireNotInstalled();

        try {
            Pantry::$installer->install();
        }
        catch (PantryInstallationException $e) {
            $pantry->response = new PantryAPIError($e->getResponseCode(), $e->getMessage(), $pantry->language->get($e->getMessage()));
            $pantry->response->respond();
        }

        $pantry->response = new PantryAPISuccess("INSTALL_SUCCESS", $pantry->language->get('INSTALL_SUCCESS'));
        $pantry->response->respond();
    }

    public static function getSupportedDatabases() {
        $pantry = new self(false, false);
        $pantry->requireNotInstalled();

        $pantry->response = new PantryAPISuccess("INSTALL_DATABASE_TYPES_SUCCESS", $pantry->language->get('INSTALL_DATABASE_TYPES_SUCCESS'),
            Pantry::$installer->getSupportedDatabases()
        );
        $pantry->response->respond();
    }

    public static function checkInstallKey() {
        $pantry = new self(true, false);

        try {
            Pantry::$installer->checkInstallKey($_POST['key']);
        }
        catch (PantryInstallationClientException $e) {
            $pantry->response = new PantryAPIError($e->getResponseCode(), $e->getMessage(), $pantry->language->get($e->getMessage()));
            $pantry->response->respond();
        }

        $pantry->response = new PantryAPISuccess("INSTALL_KEY_CHECK_SUCCESS", $pantry->language->get('INSTALL_KEY_CHECK_SUCCESS'));
        $pantry->response->respond();
    }

    public static function setInstallLanguage() {
        $pantry = new self(true, false);
        $pantry->requireNotInstalled();

        try {
            new PantryLanguage($_POST['lang_code']);
        }
        catch (PantryLanguageNotFoundException $e) {
            $pantry->response = new PantryAPIError(422, "LANGUAGE_NOT_FOUND");
            $pantry->response->respond();
        }

        Pantry::$session->setTempLanguage($_POST['lang_code']);
    }

    public static function update() {
        $pantry = new self(true, true, false);
        $pantry->requireNotUpdated();

        try {
            Pantry::$updater->update();
        }
        catch (PantryInstallationException $e) {
            $pantry->response = new PantryAPIError($e->getResponseCode(), $e->getMessage(), $pantry->language->get($e->getMessage()));
            $pantry->response->respond();
        }

        $pantry->response = new PantryAPISuccess("UPDATE_SUCCESS", $pantry->language->get('UPDATE_SUCCESS'));
        $pantry->response->respond();
    }

    public static function getUpdateVersion() {
        $pantry = new self(false, true, false);
        $pantry->requireNotUpdated();

        $versions = [];
        try {
            $versions = [
                'app' => [
                    'current_version' => Pantry::$app_metadata->get('app_version')
                ],
                'db' => [
                    'current_version' => Pantry::$db_metadata->get('db_version'),
                    'new_version' => Pantry::$app_metadata->get('db_version')
                ]
            ];
        }
        catch (PantryDBMetadataException $e) {
            Pantry::$logger->critical("Could not determine db_version from database.");
            $pantry->response = new PantryAPIError(500, "UPDATE_GET_VERSION_ERROR");
            $pantry->response->respond();
        }

        $pantry->response = new PantryAPISuccess("UPDATE_GET_VERSION_SUCCESS", "", $versions);
        $pantry->response->respond();
    }

    // ========================================
    // Entry points - application
    // ========================================
    public static function me() {
        $pantry = new self(false);
        $pantry->response = new PantryAPISuccess("ME_SUCCESS", $pantry->language->get('ME_SUCCESS'), [
            'csrf_token' => Pantry::$session->getCSRF(),
            'logged_in' => $pantry->current_session->isLoggedIn(),
            'user_id' => $pantry->current_user->getID(),
            'username' => $pantry->current_user->getUsername(),
            'is_admin' => $pantry->current_user->getIsAdmin(),
            'first_name' => $pantry->current_user->getFirstName(),
            'last_name' => $pantry->current_user->getLastName()
        ]);
        $pantry->response->respond();
    }

    public static function language() {
        $pantry = new self(false, false);
        $pantry->response = new PantryAPISuccess("LANGUAGE_SUCCESS", $pantry->language->get('LANGUAGE_SUCCESS'), $pantry->language->getAll());
        $pantry->response->respond();
    }

    public static function listLanguages() {
        $pantry = new self(false, false);
        $pantry->response = new PantryAPISuccess("LANGUAGES_SUCCESS", $pantry->language->get('LANGUAGES_SUCCESS'), [
            'current' => [
                'code' => $pantry->language->getCode(),
                'description' => $pantry->language->get('LANGUAGE_DESC')
            ],
            'list' => PantryLanguage::list()
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
            $pantry->response = new PantryAPIError(422, "MISSING_USERNAME_PASSWORD", $pantry->language->get('MISSING_USERNAME_PASSWORD'));
            $pantry->response->respond();
        }

        $clamp = new PantryClamp();

        if ($pantry->current_session->isLoggedIn()) {
            $pantry->response = new PantryAPIError(401, "NOT_LOGGED_OUT", $pantry->language->get('NOT_LOGGED_OUT'));
            $clamp->wait(500);
            $pantry->response->respond();
        }

        $check_login = PantryUser::checkLogin($username, $password, $verification, $two_factor_session_secret);

        if ($check_login === "fail") {
            $pantry->response = new PantryAPIError(401, "BAD_USERNAME_PASSWORD", $pantry->language->get('BAD_USERNAME_PASSWORD'));
        }
        elseif ($check_login === "disabled") {
            $pantry->response = new PantryAPIError(401, "USER_DISABLED", $pantry->language->get('USER_DISABLED'));
        }
        elseif ($check_login === "two_factor_required") {
            $pantry->response = new PantryAPIError(401, "TWO_FACTOR_REQUIRED", $pantry->language->get('TWO_FACTOR_REQUIRED'));
        }
        elseif ($check_login === "two_factor_incorrect") {
            $pantry->response = new PantryAPIError(401, "TWO_FACTOR_INCORRECT", $pantry->language->get('TWO_FACTOR_INCORRECT'));
        }
        elseif ($check_login === "success") {
            $user_id = PantryUser::lookupUsername($username);
            $pantry->current_session->create($user_id);

            try {
                $pantry->current_user = new PantryCurrentUser($user_id);
            }
            catch (PantryUserNotFoundException $e) {
                Pantry::$logger->alert($e->getMessage());
                $pantry->response = new PantryAPIError(500, "INTERNAL_ERROR", $pantry->language->get('API_INTERNAL_ERROR'));
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

            $pantry->response = new PantryAPISuccess("LOGIN_SUCCESS", $pantry->language->get('LOGIN_SUCCESS'), [
                'csrf_token' => Pantry::$session->getCSRF(),
                'logged_in' => $pantry->current_session->isLoggedIn(),
                'user_id' => $pantry->current_user->getID(),
                'username' => $pantry->current_user->getUsername(),
                'is_admin' => $pantry->current_user->getIsAdmin(),
                'two_factor_session_secret' => $two_factor_session_secret
            ]);
        }
        else {
            Pantry::$logger->critical("Could not log in user.");
            $pantry->response = new PantryAPIError(500, "INTERNAL_ERROR", $pantry->language->get('API_INTERNAL_ERROR'));
        }

        $clamp->wait(500);
        $pantry->response->respond();
    }

    public static function logout() {
        $pantry = new self();
        $pantry->requireLogin();

        $pantry->current_session->destroy();

        $pantry->response = new PantryAPISuccess("LOGOUT_SUCCESS", $pantry->language->get('LOGOUT_SUCCESS'));
        $pantry->response->respond();
    }

    public static function account() {
        $pantry = new self();
        $pantry->requireLogin();

        try {
            if (isset($_POST['first_name'])) {
                $pantry->current_user->setFirstName($_POST['first_name']);
            }
            if (isset($_POST['last_name'])) {
                $pantry->current_user->setLastName($_POST['last_name']);
            }
            if (isset($_POST['password']) && !empty($_POST['password'])) {
                PantryUser::checkPassword($pantry->current_user->getUsername(), $_POST['old_password']);
                $pantry->current_user->setPassword($_POST['password']);
                PantryUserSession::purgeUser($pantry->current_user->getID(), $pantry->current_session);
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
        catch (PantryUserNotFoundException $e) {
            $pantry->response = new PantryAPIError(500, "ACCOUNT_USER_NOT_FOUND");
            $pantry->response->respond();
        }

        $pantry->current_user->save();
    }

    public static function getFeaturedRecipes() {
        $pantry = new self(false);

        $featured = PantryRecipe::getFeaturedRecipes($pantry->current_user);
        $pantry->response = new PantryAPISuccess("FEATURED_RECIPES_SUCCESS", "", [
            'recipes' => $featured,
            'lang' => [
                'days_short' => $pantry->language->get('DAYS_SHORT'),
                'hours_short' => $pantry->language->get('HOURS_SHORT'),
                'minutes_short' => $pantry->language->get('MINUTES_SHORT'),
            ]
        ]);
        $pantry->response->respond();
    }

    public static function getNewRecipes() {
        $pantry = new self(false);

        $featured = PantryRecipe::getNewRecipes($pantry->current_user);
        $pantry->response = new PantryAPISuccess("LIST_NEW_RECIPES_SUCCESS", "", [
            'recipes' => $featured,
            'lang' => [
                'days_short' => $pantry->language->get('DAYS_SHORT'),
                'hours_short' => $pantry->language->get('HOURS_SHORT'),
                'minutes_short' => $pantry->language->get('MINUTES_SHORT'),
            ]
        ]);
        $pantry->response->respond();
    }

    public static function getAllRecipes() {
        $pantry = new self(false);

        $all_recipes = PantryRecipe::getAllRecipes($pantry->current_user);
        $pantry->response = new PantryAPISuccess("LIST_ALL_RECIPES_SUCCESS", "", [
            'recipes' => $all_recipes,
            'lang' => [
                'days_short' => $pantry->language->get('DAYS_SHORT'),
                'hours_short' => $pantry->language->get('HOURS_SHORT'),
                'minutes_short' => $pantry->language->get('MINUTES_SHORT'),
            ]
        ]);
        $pantry->response->respond();
    }

    public static function getRecipe($slug) {
        $pantry = new self(false);
        try {
            $recipe = PantryRecipe::constructBySlug($slug);
            $permission_level = PantryRecipePermission::getEffectivePermissionLevel($recipe, $pantry->current_user);
            if ($permission_level < PantryRecipePermission::$permission_level_map['READ']) {
                throw new PantryRecipeNotFoundException("No read permission.");
            }
        }
        catch (PantryRecipeNotFoundException $e) {
            $pantry->response = new PantryAPIError(404, "RECIPE_NOT_FOUND", $pantry->language->get('RECIPE_NOT_FOUND'));
            $pantry->response->respond();
            die();
        }

        $pantry->response = new PantryAPISuccess("RECIPE_SUCCESS", $pantry->language->get('RECIPE_SUCCESS'), [
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
            'visibility_level' => $recipe->getVisibilityLevel(),
            'default_permission_level' => $recipe->getDefaultPermissionLevel(),
            'featured' => $recipe->getIsFeatured(),
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
                'path' => $recipe->getImage()->getWebPath($recipe->getSlug()),
                'md_path' => $recipe->getImage()->getWebPath($recipe->getSlug(), "md"),
                'sm_path' => $recipe->getImage()->getWebPath($recipe->getSlug(), "sm")
            ],
            //'permission_level' => $recipe_permission->getLevel(),
            'effective_permission_level' => PantryRecipePermission::getEffectivePermissionLevel($recipe, $pantry->current_user),
            'lang' => [
                'day' => $pantry->language->get('DAY'),
                'days' => $pantry->language->get('DAYS'),
                'hour' => $pantry->language->get('HOUR'),
                'hours' => $pantry->language->get('HOURS'),
                'minute' => $pantry->language->get('MINUTE'),
                'minutes' => $pantry->language->get('MINUTES'),
            ]
        ]);
        $pantry->response->respond();
    }

    public static function getImage($img, $size = null) {
        $pantry = new self(false);
        $img_elements = explode(".", $img);
        try {
            $recipe = PantryRecipe::constructBySlug($img_elements[0]);
        }
        catch (PantryRecipeNotFoundException $e) {
            $pantry->response = new PantryAPIError(404, "RECIPE_NOT_FOUND", $pantry->language->get('RECIPE_NOT_FOUND'));
            $pantry->response->respond();
            die();
        }

        $image = $recipe->getImage();

        if (!is_a($image, "PantryImage")) {
            $pantry->response = new PantryAPIError(404, "IMAGE_NOT_FOUND", $pantry->language->get('IMAGE_NOT_FOUND'));
            $pantry->response->respond();
            die();
        }

        if ($image->getExtension() !== $img_elements[1]) {
            $pantry->response = new PantryAPIError(404, "IMAGE_EXTENSION_INVALID", $pantry->language->get('IMAGE_EXTENSION_INVALID'));
            $pantry->response->respond();
            die();
        }

        if (isset($_GET['download']) && !in_array(strtolower($_GET['download']), ["0", "false"], true)) {
            $image->download($img, $size);
        }
        else {
            $image->display($size);
        }
    }

    public static function getImageSize($size, $img) {
        self::getImage($img, $size);
    }

    public static function listCourses() {
        $pantry = new self(false);

        $courses = PantryCourse::getCourses();

        $pantry->response = new PantryAPISuccess("LIST_COURSES_SUCCESS", $pantry->language->get('LIST_COURSES_SUCCESS'), [
            'courses' => $courses
        ]);
        $pantry->response->respond();
    }

    public static function listCuisines() {
        $pantry = new self(false);

        $cuisines = PantryCuisine::getCuisines();

        $pantry->response = new PantryAPISuccess("LIST_CUISINES_SUCCESS", $pantry->language->get('LIST_CUISINES_SUCCESS'), [
            'cuisines' => $cuisines
        ]);
        $pantry->response->respond();
    }

    public static function listCoursesAndCuisines() {
        $pantry = new self(false);

        $courses = PantryCourse::getCourses();
        $cuisines = PantryCuisine::getCuisines();

        $pantry->response = new PantryAPISuccess("LIST_COURSES_CUISINES_SUCCESS", $pantry->language->get('LIST_COURSES_CUISINES_SUCCESS'), [
            'courses' => $courses,
            'cuisines' => $cuisines
        ]);
        $pantry->response->respond();
    }

    public static function createRecipe() {
        $pantry = new self();
        $pantry->requireLogin();

        $recipe = new PantryRecipe();

        // set "throwable" fields
        try {
            $recipe->setTitle($_POST['title']);
            $recipe->setSlug($_POST['slug']);
            $recipe->setBlurb($_POST['blurb']);
            $recipe->setServings($_POST['servings']);
            $recipe->setPrepTime($_POST['prep_time']);
            $recipe->setCookTime($_POST['cook_time']);
            $recipe->setSource($_POST['source']);
        }
        catch (PantryRecipeValidationException $e) {
            $error_code = PantryRecipe::$error_map[get_class($e)];
            $pantry->response = new PantryAPIError(422, $error_code, $pantry->language->get($error_code), [
                'issue' => "validation",
                'field' => $e->getField()
            ]);
            $pantry->response->respond();
        }

        // set "unthrowable" fields
        $recipe->setDescription($_POST['description']);
        $recipe->setIngredients($_POST['ingredients']);
        $recipe->setDirections($_POST['directions']);
        $recipe->setVisibilityLevel($_POST['visibility_level']);
        $recipe->setDefaultPermissionLevel($_POST['default_permission_level']);
        $recipe->setIsFeatured(in_array($_POST['featured'], ["true", "1"], true));

        //TODO: these fields
        $recipe->setIsFeatured(false);
        $recipe->setAuthor($pantry->current_user);


        // set image
        $recipe->setImage(new PantryImage(null));
        if ($_FILES['image']) {
            try {
                $image = new PantryImage();
                $image->import($_FILES['image']['tmp_name']);
                $recipe->setImage($image);
            }
            catch (PantryImageTypeNotAllowedException $e) {
                $pantry->response = new PantryAPIError(415, "RECIPE_IMAGE_NOT_ALLOWED", $pantry->language->get('IMAGE_NOT_ALLOWED'), [
                    'issue' => "validation",
                    'field' => "image"
                ]);
                $pantry->response->respond();
            }
            catch (PantryFileNotFoundException $e) {
                $pantry->response = new PantryAPIError(500, "INTERNAL_IMAGE_UPLOAD_FAILED", $pantry->language->get('IMAGE_UPLOAD_FAILED'), [
                    'issue' => "validation",
                    'field' => "image"
                ]);
                $pantry->response->respond();
            }
        }

        try {
            $recipe->setCourse(new PantryCourse($_POST['course_id']));
        }
        catch (PantryCourseNotFoundException $e) {
            $pantry->response = new PantryAPIError(422, "RECIPE_COURSE_NOT_FOUND", $pantry->language->get('COURSE_NOT_FOUND'), ['field' => "course"]);
            $pantry->response->respond();
        }

        try {
            $recipe->setCuisine(new PantryCuisine($_POST['cuisine_id']));
        }
        catch (PantryCuisineNotFoundException $e) {
            $pantry->response = new PantryAPIError(422, "RECIPE_CUISINE_NOT_FOUND", $pantry->language->get('CUISINE_NOT_FOUND'), ['field' => "cuisine"]);
            $pantry->response->respond();
        }

        try {
            $recipe->save();
        }
        catch (PantryRecipeNotSavedException $e) {
            $pantry->response = new PantryAPIError(500, "RECIPE_NOT_SAVED", $pantry->language->get('RECIPE_NOT_SAVED'));
            $pantry->response->respond();
        }

        $pantry->response = new PantryAPISuccess("CREATE_RECIPE_SUCCESS", $pantry->language->get('CREATE_RECIPE_SUCCESS'), [
            'id' => $recipe->getID(),
            'slug' => $recipe->getSlug()
        ]);
        $pantry->response->respond();
    }

    public static function editRecipe() {
        $pantry = new self();
        $pantry->requireLogin();

        // check for read/edit permissions
        $recipe = null;
        try {
            $recipe = new PantryRecipe($_POST['id']);
            $permission_level = PantryRecipePermission::getEffectivePermissionLevel($recipe, $pantry->current_user);
            if ($permission_level < PantryRecipePermission::$permission_level_map['READ']) {
                throw new PantryRecipeNotFoundException("No read permission.");
            }
            if ($permission_level < PantryRecipePermission::$permission_level_map['WRITE']) {
                throw new PantryRecipePermissionDeniedException("No write permission.");
            }
        }
        catch (PantryRecipeNotFoundException $e) {
            $pantry->response = new PantryAPIError(404, "RECIPE_NOT_FOUND", $pantry->language->get('RECIPE_NOT_FOUND'));
            $pantry->response->respond();
        }
        catch (PantryRecipePermissionDeniedException $e) {
            $pantry->response = new PantryAPIError(403, "ACCESS_DENIED", $pantry->language->get('ACCESS_DENIED'));
            $pantry->response->respond();
        }

        // Set "throwable" fields
        try {
            $recipe->setTitle($_POST['title']);
            $recipe->setSlug($_POST['slug']);
            $recipe->setBlurb($_POST['blurb']);
            $recipe->setServings($_POST['servings']);
            $recipe->setPrepTime($_POST['prep_time']);
            $recipe->setCookTime($_POST['cook_time']);
            $recipe->setSource($_POST['source']);
            //TODO: author (admin only)
            //$recipe->setAuthor($pantry->current_user);
        }
        catch (PantryRecipeValidationException $e) {
            $error_code = PantryRecipe::$error_map[get_class($e)];
            $pantry->response = new PantryAPIError(422, $error_code, $pantry->language->get($error_code), [
                'issue' => "validation",
                'field' => $e->getField()
            ]);
            $pantry->response->respond();
        }

        // set "unthrowable" fields
        $recipe->setDescription($_POST['description']);
        $recipe->setIngredients($_POST['ingredients']);
        $recipe->setDirections($_POST['directions']);
        $recipe->setIsFeatured(in_array($_POST['featured'], ["true", "1"], true));

        // set permissions if admin
        if (PantryRecipePermission::getEffectivePermissionLevel($recipe, $pantry->current_user) === PantryRecipePermission::$permission_level_map['ADMIN']) {
            $recipe->setVisibilityLevel($_POST['visibility_level']);
            $recipe->setDefaultPermissionLevel($_POST['default_permission_level']);
        }

        // set uploaded image
        if ($_FILES['image']) {
            if ($recipe->getImage()) {
                try {
                    $old_image = new PantryImage($recipe->getImage()->getID());
                }
                catch (PantryImageNotFoundException $e) {}
            }

            try {
                $image = new PantryImage();
                $image->import($_FILES['image']['tmp_name']);
                $recipe->setImage($image);
            }
            catch (PantryImageTypeNotAllowedException $e) {
                $pantry->response = new PantryAPIError(415, "RECIPE_IMAGE_NOT_ALLOWED", $pantry->language->get('IMAGE_NOT_ALLOWED'), [
                    'issue' => "validation",
                    'field' => "image"
                ]);
                $pantry->response->respond();
            }
            catch (PantryImageFileSizeTooBigException $e) {
                $pantry->response = new PantryAPIError(413, "RECIPE_IMAGE_FILE_SIZE_TOO_BIG", $pantry->language->get('IMAGE_FILE_SIZE_TOO_BIG'), [
                    'issue' => "validation",
                    'field' => "image"
                ]);
                $pantry->response->respond();
            }
            catch (PantryFileNotFoundException $e) {
                $pantry->response = new PantryAPIError(500, "INTERNAL_IMAGE_UPLOAD_FAILED", $pantry->language->get('IMAGE_UPLOAD_FAILED'), [
                    'issue' => "validation",
                    'field' => "image"
                ]);
                $pantry->response->respond();
            }
        }
        elseif ($_POST['image_clear'] === "true") {
            if ($recipe->getImage()->getID()) {
                try {
                    $old_image = new PantryImage($recipe->getImage()->getID());
                }
                catch (PantryImageNotFoundException $e) {}
            }
            $recipe->setImage(new PantryImage(null));
        }

        // set course and cuisine
        try {
            $recipe->setCourse(new PantryCourse($_POST['course_id']));
        }
        catch (PantryCourseNotFoundException $e) {
            $pantry->response = new PantryAPIError(422, "RECIPE_COURSE_NOT_FOUND", $pantry->language->get('COURSE_NOT_FOUND'), [
                'issue' => "validation",
                'field' => "course"
            ]);
            $pantry->response->respond();
        }

        try {
            $recipe->setCuisine(new PantryCuisine($_POST['cuisine_id']));
        }
        catch (PantryCuisineNotFoundException $e) {
            $pantry->response = new PantryAPIError(422, "RECIPE_CUISINE_NOT_FOUND", $pantry->language->get('CUISINE_NOT_FOUND'), [
                'issue' => "validation",
                'field' => "cuisine"
            ]);
            $pantry->response->respond();
        }

        // save
        try {
            $recipe->save();
        }
        catch (PantryRecipeNotSavedException $e) {
            $pantry->response = new PantryAPIError(500, "RECIPE_NOT_SAVED", $pantry->language->get('RECIPE_NOT_SAVED'));
            $pantry->response->respond();
        }

        // delete old image if new or clear
        if (isset($old_image)) {
            $old_image->delete();
        }

        // respond
        $pantry->response = new PantryAPISuccess();
        $pantry->response->respond();
    }

    public static function deleteRecipe() {
        $pantry = new self();
        $pantry->requireLogin();

        try {
            $recipe = new PantryRecipe($_POST['id']);

            $permission_level = PantryRecipePermission::getEffectivePermissionLevel($recipe, $pantry->current_user);
            if ($permission_level < PantryRecipePermission::$permission_level_map['READ']) {
                throw new PantryRecipeNotFoundException("No read permission.");
            }
            if ($permission_level < PantryRecipePermission::$permission_level_map['ADMIN']) {
                throw new PantryRecipePermissionDeniedException("No admin permission.");
            }

            $recipe->delete();
        }
        catch (PantryRecipeNotFoundException $e) {
            $pantry->response = new PantryAPIError(404, "RECIPE_NOT_FOUND", $pantry->language->get('RECIPE_NOT_FOUND'));
            $pantry->response->respond();
        }
        catch (PantryRecipePermissionDeniedException $e) {
            $pantry->response = new PantryAPIError(403, "ACCESS_DENIED", $pantry->language->get('ACCESS_DENIED'));
            $pantry->response->respond();
        }
        catch (PantryRecipeNotDeletedException $e) {
            $pantry->response = new PantryAPIError(500, "RECIPE_NOT_DELETED", $pantry->language->get('RECIPE_NOT_DELETED'));
            $pantry->response->respond();
        }

        $pantry->response = new PantryAPISuccess();
        $pantry->response->respond();
    }
}
