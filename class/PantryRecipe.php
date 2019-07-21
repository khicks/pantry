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
        return $this->prep_time;
    }

    public function getCookTime() {
        return $this->cook_time;
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
}
