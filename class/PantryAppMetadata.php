<?php

class PantryAppMetadata {
    private $metadata;

    public function __construct() {
        $this->metadata = require(Pantry::$php_root . "/system/metadata.php");
    }

    public function get($key) {
        return $this->metadata[$key];
    }

    public function getAll() {
        return $this->metadata;
    }
}