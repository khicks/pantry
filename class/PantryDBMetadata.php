<?php

class PantryDBMetadata {
    public function __construct() {}

    /**
     * @param string $name
     * @param string|null $default
     * @return string
     * @throws PantryDBMetadataNameEmptyException
     * @throws PantryDBMetadataNameNotFoundException
     */
    public function get(string $name, string $default = null) {
        if (!$name) {
            throw new PantryDBMetadataNameEmptyException("Value for metadata name is empty.");
        }

        $sql_get_metadata = Pantry::$db->prepare("SELECT data FROM app_metadata WHERE name=:name");
        $sql_get_metadata->bindValue(':name', $name, PDO::PARAM_STR);
        $sql_get_metadata->execute();

        if ($sql_get_metadata->rowCount() === 0) {
            throw new PantryDBMetadataNameNotFoundException("Name '$name' not found in DB metadata.");
        }

        $row = $sql_get_metadata->fetch(PDO::FETCH_ASSOC);
        return $row['data'];
    }

    /**
     * @param $name
     * @param $data
     * @throws PantryDBMetadataNameEmptyException
     */
    public function set(string $name, string $data) {
        if (!$name) {
            throw new PantryDBMetadataNameEmptyException("Value for metadata name is empty.");
        }

        Pantry::$logger->debug("Setting DB metadata '$name' to '$data'.");
        $sql_set_metadata = Pantry::$db->prepare("REPLACE INTO app_metadata (name, data) VALUES (:name, :data)");
        $sql_set_metadata->bindValue(':name', $name, PDO::PARAM_STR);
        $sql_set_metadata->bindValue(':data', $data, PDO::PARAM_STR);
        $sql_set_metadata->execute();
        $sql_set_metadata->closeCursor();
    }
}