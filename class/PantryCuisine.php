<?php

class PantryCuisine {
    private $id;
    private $title;
    private $slug;

    /**
     * PantryCuisine constructor.
     * @param $cuisine_id
     * @throws PantryCuisineNotFoundException
     */
    public function __construct($cuisine_id) {
        $this->setNull();

        if ($cuisine_id) {
            $sql_get_cuisine = Pantry::$db->prepare("SELECT id, title, slug FROM cuisines WHERE id=:id");
            $sql_get_cuisine->bindParam(':id', $cuisine_id, PDO::FETCH_ASSOC);
            $sql_get_cuisine->execute();

            if ($cuisine_row = $sql_get_cuisine->fetch(PDO::FETCH_ASSOC)) {
                $this->id = $cuisine_row['id'];
                $this->title = $cuisine_row['title'];
                $this->slug = $cuisine_row['slug'];
            }
            else {
                throw new PantryCuisineNotFoundException("Cuisine not found.");
            }
        }
    }

    /**
     * PantryRecipe alt constructor. Uses URL slug instead.
     * @param string|null $slug
     * @return PantryCuisine
     * @throws PantryCuisineNotFoundException
     */
    public static function constructBySlug($slug = null) {
        if ($slug) {
            $sql_get_cuisine_id = Pantry::$db->prepare("SELECT id FROM cuisines where slug=:slug");
            $sql_get_cuisine_id->bindValue(':slug', $slug, PDO::PARAM_STR);
            $sql_get_cuisine_id->execute();

            if ($cuisine_id_row = $sql_get_cuisine_id->fetch(PDO::FETCH_ASSOC)) {
                return new self($cuisine_id_row['id']);
            }

            throw new PantryCuisineNotFoundException("Cuisine not found.");
        }

        return new self(null);
    }

    private function setNull() {
        $this->id = null;
        $this->title = null;
        $this->slug = null;
    }

    public function getID() {
        return $this->id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getSlug() {
        return $this->slug;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function setSlug($slug) {
        $this->slug = $slug;
    }

    public function save() {
        try {
            if ($this->id) {

            }
        }
        catch (PantryCuisineNotSavedException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }
    }

    public function purgeFromRecipes() {
        if ($this->id) {
            $sql_purge_recipes = Pantry::$db->prepare("UPDATE recipes SET cuisine_id=NULL WHERE cuisine_id=:id");
            $sql_purge_recipes->bindValue(':id', $this->id, PDO::PARAM_STR);
            $sql_purge_recipes->execute();
        }
    }

    public static function list($search = "", $sort_by = "title") {
        $sort_map = [
            'title' => "title, slug",
            'slug' => "slug"
        ];
        $sort_query = (array_key_exists($sort_by, $sort_map)) ? $sort_map[$sort_by] : "title";

        $sql_list_cuisines = Pantry::$db->prepare("SELECT id, title, slug FROM cuisines WHERE title LIKE :search OR slug LIKE :search ORDER BY {$sort_query}");
        $sql_list_cuisines->bindValue(':search', "{$search}%", PDO::PARAM_STR);
        $sql_list_cuisines->execute();

        $cuisines = [];
        while ($cuisine_row = $sql_list_cuisines->fetch(PDO::FETCH_ASSOC)) {
            $cuisines[] = [
                'id' => $cuisine_row['id'],
                'title' => $cuisine_row['title'],
                'slug' => $cuisine_row['slug']
            ];
        }

        return $cuisines;
    }
}
