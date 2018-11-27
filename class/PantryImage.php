<?php

class PantryImage {
    private $id;
    private $mime_type;

    /**
     * PantryImage constructor.
     * @param null $image_id
     * @throws PantryImageNotFoundException
     */
    public function __construct($image_id = null) {
        $this->setNull();

        if ($image_id) {
            $sql_get_image = Pantry::$db->prepare("SELECT id, mime_type FROM images WHERE id=:id");
            $sql_get_image->bindValue(':id', $image_id, PDO::FETCH_ASSOC);
            $sql_get_image->execute();

            if ($image_row = $sql_get_image->fetch(PDO::FETCH_ASSOC)) {
                $this->id = $image_row['id'];
                $this->mime_type = $image_row['mime_type'];
            }
            else {
                throw new PantryImageNotFoundException("Image \"{$image_id}\" not found.");
            }
        }
    }

    private function setNull() {
        $this->id = null;
        $this->mime_type = null;
    }

    public function getID() {
        return $this->id;
    }

    public function getMimeType() {
        return $this->mime_type;
    }

    public function getExtension() {
        $extension_map = [
            'image/bmp' => "bmp",
            'image/jpeg' => "jpg",
            'image/png' => "png"
        ];

        return $extension_map[$this->mime_type];
    }

    public function getFilePath() {
        return Pantry::$php_root . "/upload/images/{$this->getID()}.{$this->getExtension()}";
    }

    public function getWebPath($slug) {
        return Pantry::$web_root . "/image/{$slug}.{$this->getExtension()}";
    }

    public function getFileSize() {
        return filesize($this->getFilePath());
    }

    public function download($file_name) {
        header("content-Description: File Transfer");
        header("Content-Disposition: attachment; filename={$file_name}");
        header("Expires: 0");
        header("Cache-Control: must-revalidate");
        header("Pragma: no-cache");
        header("Content-Length: {$this->getFileSize()}");
        header("Content-Type: {$this->getMimeType()}");
        readFile($this->getFilePath());
    }

    public function display() {
        header("Content-Type: {$this->getMimeType()}");
        readfile($this->getFilePath());
    }
}
