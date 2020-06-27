<?php

class PantryImage {
    private static $mime_map = [
        'image/bmp' => "bmp",
        'image/jpeg' => "jpg",
        'image/png' => "png"
    ];

    private $id;
    private $mime_type;
    private $file_path;

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

    /**
     * @param $file_path
     * @return string
     * @throws PantryFileNotFoundException
     * @throws PantryImageTypeNotAllowedException
     */
    public function import($file_path) {
        if (file_exists($file_path)) {
            $image_info = getimagesize($file_path);
            if (array_key_exists($image_info['mime'], self::$mime_map)) {
                $this->id = Pantry::generateUUID();
                $this->mime_type = $image_info['mime'];
                move_uploaded_file($file_path, Pantry::$php_root . "/" . Pantry::$config['upload_dir'] . "/{$this->id}.{$this->getExtension()}");
                chmod($this->getFilePath(), 0664);

                $sql_add_image = Pantry::$db->prepare("INSERT INTO images (id, created, mime_type) VALUES (:id, NOW(), :mime_type)");
                $sql_add_image->bindValue(':id', $this->id, PDO::PARAM_STR);
                $sql_add_image->bindValue(':mime_type', $this->mime_type, PDO::PARAM_STR);
                $sql_add_image->execute();

                return $this->id;
            }
            throw new PantryImageTypeNotAllowedException($file_path);
        }
        throw new PantryFileNotFoundException($file_path);
    }

    public function getID() {
        return $this->id;
    }

    public function getMimeType() {
        return $this->mime_type;
    }

    public function getExtension() {
        return self::$mime_map[$this->mime_type];
    }

    public function getFilePath() {
        return Pantry::$php_root . "/". Pantry::$config['upload_dir'] ."/{$this->id}.{$this->getExtension()}";
    }

    public function getWebPath($slug) {
        return Pantry::$web_root . "/image/{$slug}.{$this->getExtension()}";
    }

    public function getFileSize() {
        return filesize($this->getFilePath());
    }

    public function download($file_name) {
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename={$file_name}");
        header("Expires: 0");
        header("Cache-Control: must-revalidate");
        header("Pragma: no-cache");
        header("Content-Length: {$this->getFileSize()}");
        header("Content-Type: {$this->mime_type}");
        readFile($this->getFilePath());
    }

    public function display() {
        header("Content-Type: {$this->mime_type}");
        readfile($this->getFilePath());
    }

    public function delete() {
        unlink($this->getFilePath());

        $sql_delete_image = Pantry::$db->prepare("DELETE FROM images WHERE id=:id");
        $sql_delete_image->bindValue(':id', $this->id, PDO::PARAM_STR);
        $sql_delete_image->execute();

        $this->setNull();
    }
}
