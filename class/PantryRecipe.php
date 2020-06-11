<?php

class PantryRecipe {
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
    private $is_public;

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
            $sql_get_recipe = Pantry::$db->prepare("SELECT id, created, updated, title, slug, blurb, description, servings, prep_time, cook_time, ingredients, directions, source, public, author_id, course_id, cuisine_id, image_id FROM recipes WHERE id=:id");
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
                $this->is_public = boolval($recipe_row['public']);

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
        $this->is_public = null;
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

    public function getIsPublic() {
        return $this->is_public;
    }

    public function getAuthor() {
        return $this->author;
    }

    public function getCourse() {
        return $this->course;
    }

    public function getCuisine() {
        return $this->cuisine;
    }

    public function getImage() {
        return $this->image;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function setSlug($slug) {
        $this->slug = $slug;
    }

    public function setBlurb($blurb) {
        $this->blurb = $blurb;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function setServings($servings) {
        $this->servings = (int)$servings;
    }

    public function setPrepTime($prep_time) {
        $this->prep_time = (int)$prep_time;
    }

    public function setCookTime($cook_time) {
        $this->cook_time = (int)$cook_time;
    }

    public function setIngredients($ingredients) {
        $this->ingredients = $ingredients;
    }

    public function setDirections($directions) {
        $this->directions = $directions;
    }

    public function setCourse(PantryCourse $course) {
        $this->course = $course;
    }

    public function setCuisine(PantryCuisine $cuisine) {
        $this->cuisine = $cuisine;
    }

    public function save() {
        try {
            if ($this->id) {
                $sql_save_recipe = Pantry::$db->prepare("UPDATE recipes SET updated=NOW(), title=:title, slug=:slug, blurb=:blurb, description=:description, servings=:servings, prep_time=:prep_time, cook_time=:cook_time, ingredients=:ingredients, directions=:directions, course_id=:course_id, cuisine_id=:cuisine_id WHERE id=:id");
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
                $sql_save_recipe->bindValue(':course_id', $this->course->getID(), PDO::PARAM_STR);
                $sql_save_recipe->bindValue(':cuisine_id', $this->cuisine->getID(), PDO::PARAM_STR);
                if (!$sql_save_recipe->execute()) {
                    throw new PantryRecipeNotSavedException("Recipe {$this->slug} could not be saved.");
                }
            }
            else {
                throw new PantryRecipeNotSavedException("not implemented yet");
            }
        }
        catch (PantryRecipeNotSavedException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }
    }

    public static function getValidationExpression($var_name) {
        switch ($var_name) {
            case 'id':
                return "/^[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}$/";
            case 'title':
                return '/^[\p{L}!@#$%&*()\'":?,._ -]{1,64}$/';
            case 'slug':
                return '/^[a-z][a-z0-9-]{0,30}[a-z0-9]$/';
            case 'blurb':
                return '/^[\p{L}!@#$%&*()\'":?,._ -]{1,100}$/';
            default:
                return '/^$/';
        }
    }

    public static function checkSlugAvailable($slug, $id = null) {
        $sql_check_slug = Pantry::$db->prepare("SELECT id FROM recipes WHERE slug=:slug");
        $sql_check_slug->bindValue(':slug', $slug, PDO::PARAM_STR);
        $sql_check_slug->execute();
        if ($sql_check_slug->rowCount() === 0) {
            return true;
        }

        if ($id) {
            $row = $sql_check_slug->fetch(PDO::FETCH_ASSOC);
            return ($row['id'] === $id);
        }

        return false;
    }

    public static function getFeaturedRecipes() {
        $sql_get_featured = Pantry::$db->prepare("SELECT id FROM recipes WHERE featured=1 AND public=1");
        $sql_get_featured->execute();

        $featured_list = [];
        while($row = $sql_get_featured->fetch(PDO::FETCH_ASSOC)) {
            try {
                $recipe = new self($row['id']);
            }
            catch (PantryRecipeNotFoundException $e) {
                Pantry::$logger->emergency($e->getMessage());
                die();
            }

            $featured_list[] = [
                'id' => $recipe->getId(),
                'title' => $recipe->getTitle(),
                'slug' => $recipe->getSlug(),
                'blurb' => $recipe->getblurb(),
                'total_time' => $recipe->getTotalTime(),
                'author' => [
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
                    'path' => $recipe->getImage()->getWebPath($recipe->getSlug())
                ],
            ];
        }

        return $featured_list;
    }
}
