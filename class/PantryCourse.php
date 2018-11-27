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
}
