<?php

class PantryRecipe {
    public static $error_map = [
        'PantryRecipeTitleTooShortException' => "RECIPE_TITLE_TOO_SHORT",
        'PantryRecipeTitleTooLongException' => "RECIPE_TITLE_TOO_LONG",
        'PantryRecipeSlugTooShortException' => "RECIPE_SLUG_TOO_SHORT",
        'PantryRecipeSlugTooLongException' => "RECIPE_SLUG_TOO_LONG",
        'PantryRecipeSlugInvalidException' => "RECIPE_SLUG_INVALID",
        'PantryRecipeSlugNotAvailableException' => "RECIPE_SLUG_NOT_AVAILABLE",
        'PantryRecipeBlurbTooShortException' => "RECIPE_BLURB_TOO_SHORT",
        'PantryRecipeBlurbTooLongException' => "RECIPE_BLURB_TOO_LONG",
        'PantryRecipeServingsInvalidException' => "RECIPE_SERVINGS_INVALID",
        'PantryRecipeServingsTooSmallException' => "RECIPE_SERVINGS_TOO_SMALL",
        'PantryRecipeServingsTooBigException' => "RECIPE_SERVINGS_TOO_BIG",
        'PantryRecipePrepTimeInvalidException' => "RECIPE_PREP_TIME_INVALID",
        'PantryRecipePrepTimeTooSmallException' => "RECIPE_PREP_TIME_TOO_SMALL",
        'PantryRecipePrepTimeTooBigException' => "RECIPE_PREP_TIME_TOO_BIG",
        'PantryRecipeCookTimeInvalidException' => "RECIPE_PREP_TIME_INVALID",
        'PantryRecipeCookTimeTooSmallException' => "RECIPE_COOK_TIME_TOO_SMALL",
        'PantryRecipeCookTimeTooBigException' => "RECIPE_COOK_TIME_TOO_BIG",
        'PantryRecipeSourceInvalidException' => "RECIPE_SOURCE_INVALID"
    ];

    private $id;
    private $created;
    private $updated;
    private $title;
    private $slug;
    private $blurb;
    private $description;
    private $servings;
    private $prep_time;
    private $cook_time;
    private $ingredients;
    private $directions;
    private $source;
    private $visibility_level;
    private $default_permission_level;
    private $is_featured;

    /** @var PantryUser $author */
    private $author;
    /** @var PantryCourse $course */
    private $course;
    /** @var PantryCuisine $cuisine */
    private $cuisine;

    /** @var PantryImage $image */
    private $image;

    /**
     * PantryRecipe constructor.
     * @param string|null $recipe_id
     * @throws PantryRecipeNotFoundException
     */
    public function __construct($recipe_id = null) {
        $this->setNull();

        if ($recipe_id) {
            $sql_get_recipe = Pantry::$db->prepare("SELECT id, created, updated, title, slug, blurb, description, servings, prep_time, cook_time, ingredients, directions, source, visibility_level, default_permission_level, featured, author_id, course_id, cuisine_id, image_id FROM recipes WHERE id=:id");
            $sql_get_recipe->bindValue(':id', $recipe_id, PDO::PARAM_STR);
            $sql_get_recipe->execute();

            if ($recipe_row = $sql_get_recipe->fetch(PDO::FETCH_ASSOC)) {
                $this->id = $recipe_row['id'];
                $this->created = $recipe_row['created'];
                $this->updated = $recipe_row['updated'];
                $this->title = $recipe_row['title'];
                $this->slug = $recipe_row['slug'];
                $this->blurb = $recipe_row['blurb'];
                $this->description = $recipe_row['description'];
                $this->servings = intval($recipe_row['servings'], 10);
                $this->prep_time = intval($recipe_row['prep_time'], 10);
                $this->cook_time = intval($recipe_row['cook_time'], 10);
                $this->ingredients = $recipe_row['ingredients'];
                $this->directions = $recipe_row['directions'];
                $this->source = $recipe_row['source'];
                $this->visibility_level = intval($recipe_row['visibility_level'], 10);
                $this->default_permission_level = intval($recipe_row['default_permission_level'], 10);
                $this->is_featured = boolval($recipe_row['featured']);

                if ($recipe_row['author_id']) {
                    try {
                        $this->author = new PantryUser($recipe_row['author_id']);
                    }
                    catch (PantryUserNotFoundException $e) {}
                }

                if ($recipe_row['course_id']) {
                    try {
                        $this->course = new PantryCourse($recipe_row['course_id']);
                    }
                    catch (PantryCourseNotFoundException $e) {}
                }

                if ($recipe_row['cuisine_id']) {
                    try {
                        $this->cuisine = new PantryCuisine($recipe_row['cuisine_id']);
                    }
                    catch (PantryCuisineNotFoundException $e) {}
                }

                if ($recipe_row['image_id']) {
                    try {
                        $this->image = new PantryImage($recipe_row['image_id']);
                    }
                    catch (PantryImageNotFoundException $e) {}
                }
            }
            else {
                throw new PantryRecipeNotFoundException("Recipe not found.");
            }
        }
    }

    /**
     * PantryRecipe alt constructor. Uses URL slug instead.
     * @param string|null $slug
     * @return PantryRecipe
     * @throws PantryRecipeNotFoundException
     */
    public static function constructBySlug($slug = null) {
        if ($slug) {
            $sql_get_recipe_id = Pantry::$db->prepare("SELECT id FROM recipes where slug=:slug");
            $sql_get_recipe_id->bindValue(':slug', $slug, PDO::PARAM_STR);
            $sql_get_recipe_id->execute();

            if ($recipe_id_row = $sql_get_recipe_id->fetch(PDO::FETCH_ASSOC)) {
                return new self($recipe_id_row['id']);
            }

            throw new PantryRecipeNotFoundException("Recipe not found.");
        }

        return new self(null);
    }

    private function setNull() {
        $this->id = null;
        $this->created = null;
        $this->updated = null;
        $this->title = null;
        $this->slug = null;
        $this->blurb = null;
        $this->description = null;
        $this->servings = null;
        $this->prep_time = null;
        $this->cook_time = null;
        $this->ingredients = null;
        $this->directions = null;
        $this->source = null;
        $this->visibility_level = null;
        $this->default_permission_level = null;
        $this->is_featured = null;
        $this->author = null;
        $this->course = null;
        $this->cuisine = null;
        $this->image = null;
    }

    public function getID() {
        return $this->id;
    }

    public function getCreated() {
        return $this->created;
    }

    public function getUpdated() {
        return $this->updated;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getSlug() {
        return $this->slug;
    }

    public function getBlurb() {
        return $this->blurb;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getDescriptionHTML() {
        return Pantry::$parsedown->text($this->description);
    }

    public function getServings() {
        return $this->servings;
    }

    public function getPrepTime() {
        return (int)$this->prep_time;
    }

    public function getCookTime() {
        return (int)$this->cook_time;
    }

    public function getTotalTime() {
        return $this->prep_time + $this->cook_time;
    }

    public function getIngredients() {
        return $this->ingredients;
    }

    public function getIngredientsHTML() {
        return Pantry::$parsedown->text($this->ingredients);
    }

    public function getDirections() {
        return $this->directions;
    }

    public function getDirectionsHTML() {
        return Pantry::$parsedown->text($this->directions);
    }

    public function getSource() {
        return $this->source;
    }

    public function getVisibilityLevel() {
        return $this->visibility_level;
    }

    public function getDefaultPermissionLevel() {
        return $this->default_permission_level;
    }

    public function getIsFeatured() {
        return $this->is_featured;
    }

    public function getAuthor() {
        return $this->author;
    }

    public function getAuthorID() {
        return ($this->author) ? $this->author->getID() : null;
    }

    public function getCourse() {
        return $this->course;
    }

    public function getCourseID() {
        return ($this->course) ? $this->course->getID() : null;
    }

    public function getCuisine() {
        return $this->cuisine;
    }

    public function getCuisineID() {
        return ($this->cuisine) ? $this->cuisine->getID() : null;
    }

    public function getImage() {
        return $this->image;
    }

    public function getImageID() {
        return ($this->image) ? $this->image->getID() : null;
    }

    /**
     * @param $title
     * @param bool $sanitize
     * @throws PantryRecipeTitleTooLongException
     * @throws PantryRecipeTitleTooShortException
     */
    public function setTitle($title, $sanitize = true) {
        if ($sanitize) {
            $title = Pantry::$html_purifier->purify($title);
            $title = trim(preg_replace('/\s+/', " ", $title));
            if (strlen($title) === 0) {
                throw new PantryRecipeTitleTooShortException($title);
            }
            if (strlen($title) > 64) {
                throw new PantryRecipeTitleTooLongException($title);
            }
        }

        $this->title = $title;
    }

    /**
     * @param $slug
     * @param bool $sanitize
     * @throws PantryRecipeSlugInvalidException
     * @throws PantryRecipeSlugTooLongException
     * @throws PantryRecipeSlugTooShortException
     * @throws PantryRecipeSlugNotAvailableException
     */
    public function setSlug($slug, $sanitize = true) {
        if ($sanitize) {
            $slug = Pantry::$html_purifier->purify($slug);
            $slug = trim(preg_replace('/\s+/', " ", $slug));
            if (strlen($slug) < 3) {
                throw new PantryRecipeSlugTooShortException($slug);
            }
            if (strlen($slug) > 40) {
                throw new PantryRecipeSlugTooLongException($slug);
            }
            if (!preg_match('/^[a-z0-9-]{3,40}$/', $slug)) {
                throw new PantryRecipeSlugInvalidException($slug);
            }
        }

        // check availability
        $sql_check_slug = Pantry::$db->prepare("SELECT id FROM recipes WHERE slug=:slug");
        $sql_check_slug->bindValue(':slug', $slug, PDO::PARAM_STR);
        $sql_check_slug->execute();
        if ($sql_check_slug->rowCount() > 0) {
            if ($this->id) {
                $row = $sql_check_slug->fetch(PDO::FETCH_ASSOC);
                if ($row['id'] !== $this->id) {
                    throw new PantryRecipeSlugNotAvailableException($slug);
                }
            }
            else {
                throw new PantryRecipeSlugNotAvailableException($slug);
            }
        }

        $this->slug = $slug;
    }

    /**
     * @param $blurb
     * @param bool $sanitize
     * @throws PantryRecipeBlurbTooLongException
     */
    public function setBlurb($blurb, $sanitize = true) {
        if ($sanitize) {
            $blurb = Pantry::$html_purifier->purify($blurb);
            $blurb = trim(preg_replace('/\s+/', " ", $blurb));
            if (strlen($blurb) > 100) {
                throw new PantryRecipeBlurbTooLongException($blurb);
            }
        }

        $this->blurb = $blurb;
    }

    public function setDescription($description, $sanitize = true) {
        if ($sanitize) {
            $description = Pantry::$html_purifier->purify($description);
        }

        $this->description = $description;
    }

    /**
     * @param $servings
     * @param bool $sanitize
     * @throws PantryRecipeServingsInvalidException
     * @throws PantryRecipeServingsTooBigException
     * @throws PantryRecipeServingsTooSmallException
     */
    public function setServings($servings, $sanitize = true) {
        if ($sanitize) {
            if (filter_var($servings, FILTER_VALIDATE_INT) === false) {
                throw new PantryRecipeServingsInvalidException($servings);
            }
            if ((int)$servings < 0) {
                throw new PantryRecipeServingsTooSmallException($servings);
            }
            if ((int)$servings > 100) {
                throw new PantryRecipeServingsTooBigException($servings);
            }
        }

        $this->servings = (int)$servings;
    }

    /**
     * @param $prep_time
     * @param bool $sanitize
     * @throws PantryRecipePrepTimeInvalidException
     * @throws PantryRecipePrepTimeTooBigException
     * @throws PantryRecipePrepTimeTooSmallException
     */
    public function setPrepTime($prep_time, $sanitize = true) {
        if ($sanitize) {
            if (filter_var($prep_time, FILTER_VALIDATE_INT) === false) {
                throw new PantryRecipePrepTimeInvalidException($prep_time);
            }
            if ((int)$prep_time < 0) {
                throw new PantryRecipePrepTimeTooSmallException($prep_time);
            }
            if ((int)$prep_time > 129600) {
                throw new PantryRecipePrepTimeTooBigException($prep_time);
            }
        }

        $this->prep_time = (int)$prep_time;
    }

    /**
     * @param $cook_time
     * @param bool $sanitize
     * @throws PantryRecipeCookTimeInvalidException
     * @throws PantryRecipeCookTimeTooBigException
     * @throws PantryRecipeCookTimeTooSmallException
     */
    public function setCookTime($cook_time, $sanitize = true) {
        if ($sanitize) {
            if (filter_var($cook_time, FILTER_VALIDATE_INT) === false) {
                throw new PantryRecipeCookTimeInvalidException($cook_time);
            }
            if ((int)$cook_time < 0) {
                throw new PantryRecipeCookTimeTooSmallException($cook_time);
            }
            if ((int)$cook_time > 129600) {
                throw new PantryRecipeCookTimeTooBigException($cook_time);
            }
        }

        $this->cook_time = (int)$cook_time;
    }

    public function setIngredients($ingredients, $sanitize = true) {
        if ($sanitize) {
            $ingredients = Pantry::$html_purifier->purify($ingredients);
        }

        $this->ingredients = $ingredients;
    }

    public function setDirections($directions, $sanitize = true) {
        if ($sanitize) {
            $directions = Pantry::$html_purifier->purify($directions);
        }

        $this->directions = $directions;
    }

    /**
     * @param $source
     * @param bool $sanitize
     * @throws PantryRecipeSourceInvalidException
     */
    public function setSource($source, $sanitize = true) {
        if ($sanitize && !empty($source)) {
            $source = Pantry::$html_purifier->purify($source);
            $source = trim($source);
            $validate_url = filter_var($source, FILTER_VALIDATE_URL,
                FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED);
            if ($validate_url === false || !preg_match('/^https?:\/\//', $source)) {
                throw new PantryRecipeSourceInvalidException($source);
            }
        }

        $this->source = $source;
    }

    public function setVisibilityLevel($visibility_level) {
        $visibility_level = (int)$visibility_level;
        if ($visibility_level < 0 || $visibility_level > 2) {
            $visibility_level = 0;
        }
        $this->visibility_level = $visibility_level;
    }

    public function setDefaultPermissionLevel($default_permission_level) {
        $default_permission_level = (int)$default_permission_level;
        if ($default_permission_level < 0 || $default_permission_level > 3 || $this->visibility_level === 0) {
            $default_permission_level = 0;
        }
        $this->default_permission_level = $default_permission_level;
    }

    public function setIsFeatured($is_featured) {
        $this->is_featured = boolval($is_featured);
    }

    public function setAuthor(PantryUser $author) {
        $this->author = $author;
    }

    public function setCourse(PantryCourse $course) {
        $this->course = $course;
    }

    public function setCuisine(PantryCuisine $cuisine) {
        $this->cuisine = $cuisine;
    }

    public function setImage(PantryImage $image) {
        $this->image = $image;
    }

    public function save() {
        try {
            if ($this->id) {
                $sql_save_recipe = Pantry::$db->prepare("UPDATE recipes SET updated=NOW(), title=:title, slug=:slug, blurb=:blurb, description=:description, servings=:servings, prep_time=:prep_time, cook_time=:cook_time, ingredients=:ingredients, directions=:directions, source=:source, visibility_level=:visibility_level, default_permission_level=:default_permission_level, featured=:featured, author_id=:author_id, course_id=:course_id, cuisine_id=:cuisine_id, image_id=:image_id WHERE id=:id");
                $sql_save_recipe->bindValue(':id', $this->id, PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':title', $this->title, PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':slug', $this->slug, PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':blurb', $this->blurb, PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':description', $this->description, PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':servings', $this->servings, PDO::PARAM_INT);
                $sql_save_recipe->bindValue(':prep_time', $this->prep_time, PDO::PARAM_INT);
                $sql_save_recipe->bindValue(':cook_time', $this->cook_time, PDO::PARAM_INT);
                $sql_save_recipe->bindValue(':ingredients', $this->ingredients, PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':directions', $this->directions, PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':source', $this->source, PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':visibility_level', $this->visibility_level, PDO::PARAM_INT);
                $sql_save_recipe->bindValue(':default_permission_level', $this->default_permission_level, PDO::PARAM_INT);
                $sql_save_recipe->bindValue(':featured', $this->is_featured, PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':author_id', $this->getAuthorID(), PDO::PARAM_STR); //TODO: in form
                $sql_save_recipe->bindValue(':course_id', $this->getCourseID(), PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':cuisine_id', $this->getCuisineID(), PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':image_id', $this->getImageID(), PDO::PARAM_STR);
                if (!$sql_save_recipe->execute()) {
                    throw new PantryRecipeNotSavedException("Recipe {$this->slug} could not be saved.");
                }
            }
            else {
                $this->id = Pantry::generateUUID();
                $sql_save_recipe = Pantry::$db->prepare("INSERT INTO recipes (id, created, updated, title, slug, blurb, description, servings, prep_time, cook_time, ingredients, directions, source, visibility_level, default_permission_level, featured, author_id, course_id, cuisine_id, image_id) VALUES (:id, NOW(), NOW(), :title, :slug, :blurb, :description, :servings, :prep_time, :cook_time, :ingredients, :directions, :source, :visibility_level, :default_permission_level, :featured, :author_id, :course_id, :cuisine_id, :image_id)");
                $sql_save_recipe->bindValue(':id', $this->id, PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':title', $this->title, PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':slug', $this->slug, PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':blurb', $this->blurb, PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':description', $this->description, PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':servings', $this->servings, PDO::PARAM_INT);
                $sql_save_recipe->bindValue(':prep_time', $this->prep_time, PDO::PARAM_INT);
                $sql_save_recipe->bindValue(':cook_time', $this->cook_time, PDO::PARAM_INT);
                $sql_save_recipe->bindValue(':ingredients', $this->ingredients, PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':directions', $this->directions, PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':source', $this->source, PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':visibility_level', $this->visibility_level, PDO::PARAM_INT);
                $sql_save_recipe->bindValue(':default_permission_level', $this->default_permission_level, PDO::PARAM_INT);
                $sql_save_recipe->bindValue(':featured', $this->is_featured, PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':author_id', $this->getAuthorID(), PDO::PARAM_STR); //TODO: in form
                $sql_save_recipe->bindValue(':course_id', $this->getCourseID(), PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':cuisine_id', $this->getCuisineID(), PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':image_id', $this->getImageID(), PDO::PARAM_STR);
                if (!$sql_save_recipe->execute()) {
                    Pantry::$logger->error(print_r($sql_save_recipe->errorInfo(), true));
                    throw new PantryRecipeNotSavedException("Recipe {$this->slug} could not be saved.");
                }
            }
        }
        catch (PantryRecipeNotSavedException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }
    }

    public function delete() {
        try {
            if ($this->id) {
                // purge permissions
                $sql_delete_recipe_permission = Pantry::$db->prepare("DELETE FROM recipes_permissions WHERE recipe_id=:recipe_id");
                $sql_delete_recipe_permission->bindValue(':recipe_id', $this->id, PDO::PARAM_STR);
                if (!$sql_delete_recipe_permission->execute()) {
                    Pantry::$logger->error("Could not delete recipe permission.");
                    Pantry::$logger->error(print_r($sql_delete_recipe_permission->errorInfo(), true));
                    throw new PantryRecipeNotDeletedException("Recipe {$this->slug} could not be deleted.");
                }

                // delete image
                if ($this->getImageID()) {
                    $this->image->delete();
                }

                // delete recipe
                $sql_delete_recipe = Pantry::$db->prepare("DELETE FROM recipes WHERE id=:id");
                $sql_delete_recipe->bindValue(':id', $this->id, PDO::PARAM_STR);
                if (!$sql_delete_recipe->execute()) {
                    Pantry::$logger->error("Could not delete recipe.");
                    Pantry::$logger->error(print_r($sql_delete_recipe->errorInfo(), true));
                    throw new PantryRecipeNotDeletedException("Recipe {$this->slug} could not be deleted.");
                }
                $this->setNull();
            }
            else {
                throw new PantryRecipeNotFoundException("Recipe not found.");
            }
        }
        catch (PantryRecipeNotFoundException | PantryRecipeNotDeletedException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }
    }

    public static function getFeaturedRecipes(PantryUser $user = null) {
        $sql_get_featured = Pantry::$db->prepare("SELECT id FROM recipes WHERE featured=1 ORDER BY title");
        $sql_get_featured->execute();

        $featured_list = [];
        while ($row = $sql_get_featured->fetch(PDO::FETCH_ASSOC)) {
            try {
                $recipe = new self($row['id']);
                if (PantryRecipePermission::getEffectivePermissionLevel($recipe, $user) < 1) {
                    throw new PantryRecipeNotFoundException("No permission for recipe {$recipe->getID()}");
                }
            }
            catch (PantryRecipeNotFoundException $e) {
                continue;
            }

            $featured_list[] = [
                'id' => $recipe->getId(),
                'title' => $recipe->getTitle(),
                'slug' => $recipe->getSlug(),
                'blurb' => $recipe->getblurb(),
                'total_time' => $recipe->getTotalTime(),
                'author' => (is_null($recipe->getAuthor())) ? null : [
                    'username' => $recipe->getAuthor()->getusername(),
                    'first_name' => $recipe->getAuthor()->getFirstName(),
                    'last_name' => $recipe->getAuthor()->getLastName(),
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
                ]
            ];
        }

        return $featured_list;
    }

    public static function getNewRecipes(PantryUser $user = null) {
        $sql_get_new = Pantry::$db->prepare("SELECT id FROM recipes ORDER BY created DESC LIMIT 4");
        $sql_get_new->execute();

        $new_list = [];
        while ($row = $sql_get_new->fetch(PDO::FETCH_ASSOC)) {
            try {
                $recipe = new self($row['id']);
                if (PantryRecipePermission::getEffectivePermissionLevel($recipe, $user) < 1) {
                    throw new PantryRecipeNotFoundException("No permission for recipe {$recipe->getID()}");
                }
            }
            catch (PantryRecipeNotFoundException $e) {
                continue;
            }

            $new_list[] = [
                'id' => $recipe->getId(),
                'title' => $recipe->getTitle(),
                'slug' => $recipe->getSlug(),
                'blurb' => $recipe->getblurb(),
                'total_time' => $recipe->getTotalTime(),
                'author' => (is_null($recipe->getAuthor())) ? null : [
                    'username' => $recipe->getAuthor()->getusername(),
                    'first_name' => $recipe->getAuthor()->getFirstName(),
                    'last_name' => $recipe->getAuthor()->getLastName(),
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
                ]
            ];
        }

        return $new_list;
    }

    public static function getAllRecipes(PantryUser $user = null) {
        $sql_get_all = Pantry::$db->prepare("SELECT id FROM recipes ORDER BY updated DESC");
        $sql_get_all->execute();

        $recipe_list = [];
        while ($row = $sql_get_all->fetch(PDO::FETCH_ASSOC)) {
            try {
                $recipe = new self($row['id']);
                if (PantryRecipePermission::getEffectivePermissionLevel($recipe, $user) < 1) {
                    throw new PantryRecipeNotFoundException("No permission for recipe {$recipe->getID()}");
                }
            }
            catch (PantryRecipeNotFoundException $e) {
                continue;
            }

            $recipe_list[] = [
                'id' => $recipe->getId(),
                'title' => $recipe->getTitle(),
                'slug' => $recipe->getSlug(),
                'blurb' => $recipe->getblurb(),
                'total_time' => $recipe->getTotalTime(),
                'author' => (is_null($recipe->getAuthor())) ? null : [
                    'username' => $recipe->getAuthor()->getusername(),
                    'first_name' => $recipe->getAuthor()->getFirstName(),
                    'last_name' => $recipe->getAuthor()->getLastName(),
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
                ]
            ];
        }

        return $recipe_list;
    }

    public static function purgeUser($user_id, $new_user_id = null) {
        $sql_purge_user = Pantry::$db->prepare("UPDATE recipes SET author_id=:new_user_id WHERE author_id=:user_id");
        $sql_purge_user->bindValue(':user_id', $user_id, PDO::PARAM_STR);
        $sql_purge_user->bindValue(':new_user_id', $new_user_id, PDO::PARAM_STR);
        $sql_purge_user->execute();
    }

    public static function purgeCourse($course_id, $new_course_id = null) {
        $sql_purge_course = Pantry::$db->prepare("UPDATE recipes SET course_id=:new_course_id WHERE course_id=:course_id");
        $sql_purge_course->bindValue(':course_id', $course_id, PDO::PARAM_STR);
        $sql_purge_course->bindValue(':new_course_id', $new_course_id, PDO::PARAM_STR);
        $sql_purge_course->execute();
    }

    public static function purgeCuisine($cuisine_id, $new_cuisine_id = null) {
        $sql_purge_cuisine = Pantry::$db->prepare("UPDATE recipes SET cuisine_id=:new_cuisine_id WHERE course_id=:cuisine_id");
        $sql_purge_cuisine->bindValue(':course_id', $cuisine_id, PDO::PARAM_STR);
        $sql_purge_cuisine->bindValue(':new_course_id', $new_cuisine_id, PDO::PARAM_STR);
        $sql_purge_cuisine->execute();
    }
}
