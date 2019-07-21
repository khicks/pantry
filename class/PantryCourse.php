<?php

class PantryCourse {
    private $id;
    private $title;
    private $slug;

    /**
     * PantryCourse constructor.
     * @param $course_id
     * @throws PantryCourseNotFoundException
     */
    public function __construct($course_id) {
        $this->setNull();

        if ($course_id) {
            $sql_get_course = Pantry::$db->prepare("SELECT id, title, slug FROM courses WHERE id=:id");
            $sql_get_course->bindParam(':id', $course_id, PDO::FETCH_ASSOC);
            $sql_get_course->execute();

            if ($course_row = $sql_get_course->fetch(PDO::FETCH_ASSOC)) {
                $this->id = $course_row['id'];
                $this->title = $course_row['title'];
                $this->slug = $course_row['slug'];
            }
            else {
                throw new PantryCourseNotFoundException("Course not found.");
            }
        }
    }

    /**
     * PantryRecipe alt constructor. Uses URL slug instead.
     * @param string|null $slug
     * @return PantryCourse
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

            throw new PantryCourseNotFoundException("Course not found.");
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
        catch (PantryCourseNotSavedException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }
    }

    public function purgeFromRecipes() {
        if ($this->id) {
            $sql_purge_recipes = Pantry::$db->prepare("UPDATE recipes SET course_id=NULL WHERE course_id=:id");
            $sql_purge_recipes->bindValue(':id', $this->id, PDO::PARAM_STR);
            $sql_purge_recipes->execute();
        }
    }

    public static function listCourses($search = "", $sort_by = "title") {
        $sort_map = [
            'title' => "title, slug",
            'slug' => "slug"
        ];
        $sort_query = (array_key_exists($sort_by, $sort_map)) ? $sort_map[$sort_by] : "title";

        $sql_list_courses = Pantry::$db->prepare("SELECT id, title, slug FROM courses WHERE title LIKE :search OR slug LIKE :search ORDER BY {$sort_query}");
        $sql_list_courses->bindValue(':search', "{$search}%", PDO::PARAM_STR);
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
