<?php

class PantryCourse {
    public static $error_map = [
        'PantryCourseTitleNoneException' => "COURSE_TITLE_NONE",
        'PantryCourseTitleTooLongException' => "COURSE_TITLE_TOO_LONG",
        'PantryCourseSlugNoneException' => "COURSE_SLUG_NONE",
        'PantryCourseSlugTooShortException' => "COURSE_SLUG_TOO_SHORT",
        'PantryCourseSlugTooLongException' => "COURSE_SLUG_TOO_LONG",
        'PantryCourseSlugInvalidException' => "COURSE_SLUG_INVALID",
        'PantryCourseSlugNotAvailableException' => "COURSE_SLUG_NOT_AVAILABLE"
    ];

    private $id;
    private $created;
    private $title;
    private $slug;

    /**
     * PantryCourse constructor.
     * @param $course_id
     * @throws PantryCourseNotFoundException
     */
    public function __construct($course_id = null) {
        $this->setNull();

        if ($course_id) {
            $sql_get_course = Pantry::$db->prepare("SELECT id, created, title, slug FROM courses WHERE id=:id");
            $sql_get_course->bindParam(':id', $course_id, PDO::FETCH_ASSOC);
            $sql_get_course->execute();

            if ($course_row = $sql_get_course->fetch(PDO::FETCH_ASSOC)) {
                $this->id = $course_row['id'];
                $this->created = $course_row['created'];
                $this->title = $course_row['title'];
                $this->slug = $course_row['slug'];
            }
            else {
                throw new PantryCourseNotFoundException("Course not found.");
            }
        }
    }

    /**
     * PantryCourse alt constructor. Uses URL slug instead.
     * @param string|null $slug
     * @return PantryCourse|null
     * @throws PantryCourseNotFoundException
     */
    public static function constructBySlug($slug = null) {
        if ($slug) {
            $sql_get_course_id = Pantry::$db->prepare("SELECT id FROM courses where slug=:slug");
            $sql_get_course_id->bindValue(':slug', $slug, PDO::PARAM_STR);
            $sql_get_course_id->execute();

            if ($course_id_row = $sql_get_course_id->fetch(PDO::FETCH_ASSOC)) {
                return new self($course_id_row['id']);
            }

            throw new PantryCourseNotFoundException($slug);
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
     * @throws PantryCourseTitleNoneException
     * @throws PantryCourseTitleTooLongException
     */
    public function setTitle($title, $sanitize = true) {
        if ($sanitize) {
            $title = Pantry::$html_purifier->purify($title);
            $title = trim(preg_replace('/\s+/', " ", $title));
            if (strlen($title) === 0) {
                throw new PantryCourseTitleNoneException($title);
            }
            if (strlen($title) > 32) {
                throw new PantryCourseTitleTooLongException($title);
            }
        }
        $this->title = $title;
    }

    /**
     * @param $slug
     * @param bool $sanitize
     * @throws PantryCourseSlugNoneException
     * @throws PantryCourseSlugTooLongException
     * @throws PantryCourseSlugTooShortException
     * @throws PantryCourseSlugInvalidException
     * @throws PantryCourseSlugNotAvailableException
     */
    public function setSlug($slug, $sanitize = true) {
        if ($sanitize) {
            $slug = Pantry::$html_purifier->purify($slug);
            $slug = trim(preg_replace('/\s+/', " ", $slug));
            if (strlen($slug) === 0) {
                throw new PantryCourseSlugNoneException($slug);
            }
            if (strlen($slug) < 3) {
                throw new PantryCourseSlugTooShortException($slug);
            }
            if (strlen($slug) > 32) {
                throw new PantryCourseSlugTooLongException($slug);
            }
            if (!preg_match('/^[a-z0-9-]{3,32}$/', $slug)) {
                throw new PantryCourseSlugInvalidException($slug);
            }
        }

        // check availability
        $sql_check_slug = Pantry::$db->prepare("SELECT id FROM courses WHERE slug=:slug");
        $sql_check_slug->bindValue(':slug', $slug, PDO::PARAM_STR);
        $sql_check_slug->execute();
        if ($sql_check_slug->rowCount() > 0) {
            if (!$this->id) {
                throw new PantryCourseSlugNotAvailableException($slug);
            }

            $row = $sql_check_slug->fetch(PDO::FETCH_ASSOC);
            if ($row['id'] !== $this->id) {
                throw new PantryCourseSlugNotAvailableException($slug);
            }
        }

        $this->slug = $slug;
    }

    /**
     * @throws PantryCourseNotSavedException
     */
    public function save() {
        if ($this->id) {
            $sql_save_course = Pantry::$db->prepare("UPDATE courses SET title=:title, slug=:slug WHERE id=:id");
        }
        else {
            $this->id = Pantry::generateUUID();
            $sql_save_course = Pantry::$db->prepare("INSERT INTO courses (id, created, title, slug) VALUES (:id, NOW(), :title, :slug)");
        }

        $sql_save_course->bindValue(':id', $this->id, PDO::PARAM_STR);
        $sql_save_course->bindValue(':title', $this->title, PDO::PARAM_STR);
        $sql_save_course->bindValue(':slug', $this->slug, PDO::PARAM_STR);

        if (!$sql_save_course->execute()) {
            Pantry::$logger->critical("Course {$this->slug} could not be saved.");
            throw new PantryCourseNotSavedException($this->slug);
        }
    }

    /**
     * @param string|null $replace_id
     * @throws PantryCourseNotFoundException
     * @throws PantryCourseDeleteReplacementIsSameException
     * @throws PantryCourseNotDeletedException
     */
    public function delete($replace_id = null) {
        if (!$this->id) {
            Pantry::$logger->critical("Tried to delete non-existent course.");
            throw new PantryCourseNotFoundException("");
        }

        if ($this->id === $replace_id) {
            throw new PantryCourseDeleteReplacementIsSameException("");
        }

        $replace = new self($replace_id);

        // purge from recipes
        $sql_purge_recipe_course = Pantry::$db->prepare("UPDATE recipes SET course_id=:replace_id WHERE course_id=:course_id");
        $sql_purge_recipe_course->bindValue(':course_id', $this->id, PDO::PARAM_STR);
        $sql_purge_recipe_course->bindValue(':replace_id', $replace->getID(), PDO::PARAM_STR);
        if (!$sql_purge_recipe_course->execute()) {
            Pantry::$logger->critical("Could not delete course {$this->id} (purge from recipes).");
            throw new PantryCourseNotDeletedException("Could not delete course {$this->slug}.");
        }

        // delete course
        $sql_delete_course = Pantry::$db->prepare("DELETE FROM courses WHERE id=:id");
        $sql_delete_course->bindValue(':id', $this->id, PDO::PARAM_STR);
        if (!$sql_delete_course->execute()) {
            Pantry::$logger->critical("Could not delete course {$this->id} (delete course).");
            throw new PantryCourseNotDeletedException("Could not delete course {$this->slug}.");
        }

        $this->setNull();
    }

    public static function getCourses() {
        $sql_list_courses = Pantry::$db->prepare("SELECT id, title, slug FROM courses ORDER BY title,slug");
        $sql_list_courses->execute();

        $courses = [];
        while ($course_row = $sql_list_courses->fetch(PDO::FETCH_ASSOC)) {
            $courses[] = [
                'id' => $course_row['id'],
                'title' => $course_row['title'],
                'slug' => $course_row['slug']
            ];
        }

        return $courses;
    }
}
