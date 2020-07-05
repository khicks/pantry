<?php

class PantryCuisine {
    public static $error_map = [
        'PantryCuisineTitleNoneException' => "CUISINE_TITLE_NONE",
        'PantryCuisineTitleTooLongException' => "CUISINE_TITLE_TOO_LONG",
        'PantryCuisineSlugNoneException' => "CUISINE_SLUG_NONE",
        'PantryCuisineSlugTooShortException' => "CUISINE_SLUG_TOO_SHORT",
        'PantryCuisineSlugTooLongException' => "CUISINE_SLUG_TOO_LONG",
        'PantryCuisineSlugInvalidException' => "CUISINE_SLUG_INVALID",
        'PantryCuisineSlugNotAvailableException' => "CUISINE_SLUG_NOT_AVAILABLE"
    ];
    
    private $id;
    private $created;
    private $title;
    private $slug;

    /**
     * PantryCuisine constructor.
     * @param $cuisine_id
     * @throws PantryCuisineNotFoundException
     */
    public function __construct($cuisine_id = null) {
        $this->setNull();

        if ($cuisine_id) {
            $sql_get_cuisine = Pantry::$db->prepare("SELECT id, created, title, slug FROM cuisines WHERE id=:id");
            $sql_get_cuisine->bindParam(':id', $cuisine_id, PDO::FETCH_ASSOC);
            $sql_get_cuisine->execute();

            if ($cuisine_row = $sql_get_cuisine->fetch(PDO::FETCH_ASSOC)) {
                $this->id = $cuisine_row['id'];
                $this->created = $cuisine_row['created'];
                $this->title = $cuisine_row['title'];
                $this->slug = $cuisine_row['slug'];
            }
            else {
                throw new PantryCuisineNotFoundException("Cuisine not found.");
            }
        }
    }

    /**
     * PantryCuisine alt constructor. Uses URL slug instead.
     * @param string|null $slug
     * @return PantryCuisine|null
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

            throw new PantryCuisineNotFoundException($slug);
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

    public function getCreated() {
        return $this->created;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getSlug() {
        return $this->slug;
    }

    /**
     * @param $title
     * @param bool $sanitize
     * @throws PantryCuisineTitleNoneException
     * @throws PantryCuisineTitleTooLongException
     */
    public function setTitle($title, $sanitize = true) {
        if ($sanitize) {
            $title = Pantry::$html_purifier->purify($title);
            $title = trim(preg_replace('/\s+/', " ", $title));
            if (strlen($title) === 0) {
                throw new PantryCuisineTitleNoneException($title);
            }
            if (strlen($title) > 32) {
                throw new PantryCuisineTitleTooLongException($title);
            }
        }
        $this->title = $title;
    }

    /**
     * @param $slug
     * @param bool $sanitize
     * @throws PantryCuisineSlugNoneException
     * @throws PantryCuisineSlugTooLongException
     * @throws PantryCuisineSlugTooShortException
     * @throws PantryCuisineSlugInvalidException
     * @throws PantryCuisineSlugNotAvailableException
     */
    public function setSlug($slug, $sanitize = true) {
        if ($sanitize) {
            $slug = Pantry::$html_purifier->purify($slug);
            $slug = trim(preg_replace('/\s+/', " ", $slug));
            if (strlen($slug) === 0) {
                throw new PantryCuisineSlugNoneException($slug);
            }
            if (strlen($slug) < 3) {
                throw new PantryCuisineSlugTooShortException($slug);
            }
            if (strlen($slug) > 32) {
                throw new PantryCuisineSlugTooLongException($slug);
            }
            if (!preg_match('/^[a-z0-9-]{3,32}$/', $slug)) {
                throw new PantryCuisineSlugInvalidException($slug);
            }
        }

        // check availability
        $sql_check_slug = Pantry::$db->prepare("SELECT id FROM cuisines WHERE slug=:slug");
        $sql_check_slug->bindValue(':slug', $slug, PDO::PARAM_STR);
        $sql_check_slug->execute();
        if ($sql_check_slug->rowCount() > 0) {
            if (!$this->id) {
                throw new PantryCuisineSlugNotAvailableException($slug);
            }

            $row = $sql_check_slug->fetch(PDO::FETCH_ASSOC);
            if ($row['id'] !== $this->id) {
                throw new PantryCuisineSlugNotAvailableException($slug);
            }
        }

        $this->slug = $slug;
    }

    /**
     * @throws PantryCuisineNotSavedException
     */
    public function save() {
        if ($this->id) {
            $sql_save_cuisine = Pantry::$db->prepare("UPDATE cuisines SET title=:title, slug=:slug WHERE id=:id");
        }
        else {
            $this->id = Pantry::generateUUID();
            $sql_save_cuisine = Pantry::$db->prepare("INSERT INTO cuisines (id, created, title, slug) VALUES (:id, NOW(), :title, :slug)");
        }

        $sql_save_cuisine->bindValue(':id', $this->id, PDO::PARAM_STR);
        $sql_save_cuisine->bindValue(':title', $this->title, PDO::PARAM_STR);
        $sql_save_cuisine->bindValue(':slug', $this->slug, PDO::PARAM_STR);

        if (!$sql_save_cuisine->execute()) {
            Pantry::$logger->critical("Cuisine {$this->slug} could not be saved.");
            throw new PantryCuisineNotSavedException($this->slug);
        }
    }

    /**
     * @param string|null $replace_id
     * @throws PantryCuisineNotFoundException
     * @throws PantryCuisineDeleteReplacementIsSameException
     * @throws PantryCuisineNotDeletedException
     */
    public function delete($replace_id = null) {
        if (!$this->id) {
            Pantry::$logger->critical("Tried to delete non-existent cuisine.");
            throw new PantryCuisineNotFoundException("");
        }

        if ($this->id === $replace_id) {
            throw new PantryCuisineDeleteReplacementIsSameException("");
        }

        $replace = new self($replace_id);

        // purge from recipes
        $sql_purge_recipe_cuisine = Pantry::$db->prepare("UPDATE recipes SET cuisine_id=:replace_id WHERE cuisine_id=:cuisine_id");
        $sql_purge_recipe_cuisine->bindValue(':cuisine_id', $this->id, PDO::PARAM_STR);
        $sql_purge_recipe_cuisine->bindValue(':replace_id', $replace->getID(), PDO::PARAM_STR);
        if (!$sql_purge_recipe_cuisine->execute()) {
            Pantry::$logger->critical("Could not delete cuisine {$this->id} (purge from recipes).");
            throw new PantryCuisineNotDeletedException("Could not delete cuisine {$this->slug}.");
        }

        // delete cuisine
        $sql_delete_cuisine = Pantry::$db->prepare("DELETE FROM cuisines WHERE id=:id");
        $sql_delete_cuisine->bindValue(':id', $this->id, PDO::PARAM_STR);
        if (!$sql_delete_cuisine->execute()) {
            Pantry::$logger->critical("Could not delete cuisine {$this->id} (delete cuisine).");
            throw new PantryCuisineNotDeletedException("Could not delete cuisine {$this->slug}.");
        }

        $this->setNull();
    }

    public static function getCuisines() {
        $sql_list_cuisines = Pantry::$db->prepare("SELECT id, title, slug FROM cuisines ORDER BY title,slug");
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
