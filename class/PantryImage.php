<?php

class PantryImage {
    private static $mime_map = [
        'image/bmp' => "bmp",
        'image/jpeg' => "jpg",
        'image/png' => "png"
    ];

    private $id;
    private $mime_type;

    private $width;
    private $height;

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
        $this->width = null;
        $this->height = null;
    }

    private function calculateHeight($new_width) {
        return floor(($this->height/$this->width)*$new_width);
    }

    /**
     * @throws PantryImageTypeNotAllowedException
     */
    private function createReducedFiles() {
        switch ($this->getExtension()) {
            case "bmp":
                [$create_func, $write_func] = ['imagecreatefrombmp', 'imagepng'];
                break;
            case "jpg":
                [$create_func, $write_func] = ['imagecreatefromjpeg', 'imagejpeg'];
                break;
            case "png":
                [$create_func, $write_func] = ['imagecreatefrompng', 'imagepng'];
                break;
            default:
                throw new PantryImageTypeNotAllowedException($this->getFilePath());
        }

        $image_obj = $create_func($this->getFilePath());

        // medium
        if ($this->width > 720) {
            $md_image = imagescale($image_obj, 720);
            $write_func($md_image, $this->getFilePath("md", true));
        }

        // small
        if ($this->width > 400) {
            $sm_image = imagescale($image_obj, 400);
            $write_func($sm_image, $this->getFilePath("sm", true));
        }

    }

    /**
     * @param $file_path
     * @return string
     * @throws PantryFileNotFoundException
     * @throws PantryImageFileSizeTooBigException
     * @throws PantryImageTypeNotAllowedException
     */
    public function import($file_path) {
        if (!file_exists($file_path)) {
            throw new PantryFileNotFoundException($file_path);
        }

        if (filesize($file_path) > Pantry::$config['max_file_size']) {
            throw new PantryImageFileSizeTooBigException($file_path);
        }

        $this->mime_type = mime_content_type($file_path);
        if (!array_key_exists($this->mime_type, self::$mime_map)) {
            throw new PantryImageTypeNotAllowedException($file_path);
        }

        // TODO: file size restrictions
        $image_info = getimagesize($file_path);
        $this->width = $image_info[0];
        $this->height = $image_info[1];

        // object properties
        $this->id = Pantry::generateUUID();
        $this->mime_type = $image_info['mime'];

        // move to data directory
        move_uploaded_file($file_path, $this->getFilePath());
        chmod($this->getFilePath(), 0664);

        // resize
        $this->createReducedFiles();

        // save to database
        $sql_add_image = Pantry::$db->prepare("INSERT INTO images (id, created, mime_type) VALUES (:id, NOW(), :mime_type)");
        $sql_add_image->bindValue(':id', $this->id, PDO::PARAM_STR);
        $sql_add_image->bindValue(':mime_type', $this->mime_type, PDO::PARAM_STR);
        $sql_add_image->execute();

        return $this->id;
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

    /**
     * @param string|null $size (md, sm)
     * @param bool $force
     * @return string
     */
    public function getFilePath($size = null, $force = false) {
        if ($size && in_array($size, ['md', 'sm'], true)) {
            $path = Pantry::$php_root . "/". Pantry::$config['upload_dir'] ."/{$this->id}_{$size}.{$this->getExtension()}";
            if (file_exists($path) || $force) {
                return $path;
            }
        }

        return Pantry::$php_root . "/". Pantry::$config['upload_dir'] ."/{$this->id}.{$this->getExtension()}";
    }

    public function getWebPath($slug, $size = null) {
        if ($size && in_array($size, ['md', 'sm'], true)) {
            return Pantry::$web_root . "/image/{$size}/{$slug}.{$this->getExtension()}";
        }
        return Pantry::$web_root . "/image/{$slug}.{$this->getExtension()}";
    }

    public function getFileSize() {
        return filesize($this->getFilePath());
    }

    public function download($file_name, $size = null) {
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename={$file_name}");
        header("Expires: 0");
        header("Cache-Control: must-revalidate");
        header("Pragma: no-cache");
        header("Content-Length: {$this->getFileSize()}");
        header("Content-Type: {$this->mime_type}");
        readFile($this->getFilePath($size));
    }

    public function display($size = null) {
        header("Content-Type: {$this->mime_type}");
        readfile($this->getFilePath($size));
    }

    public function delete() {
        unlink($this->getFilePath());
        if (file_exists($this->getFilePath("md", true))) {
            unlink($this->getFilePath("md"));
        }
        if (file_exists($this->getFilePath("sm", true))) {
            unlink($this->getFilePath("sm"));
        }

        $sql_delete_image = Pantry::$db->prepare("DELETE FROM images WHERE id=:id");
        $sql_delete_image->bindValue(':id', $this->id, PDO::PARAM_STR);
        $sql_delete_image->execute();

        $this->setNull();
    }
}
