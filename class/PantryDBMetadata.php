<?php

class PantryDBMetadata {
    public function __construct() {}

    /**
     * @param string $key
     * @param string|null $default
     * @return string
     * @throws PantryDBMetadataKeyEmptyException
     * @throws PantryDBMetadataKeyNotFoundException
     */
    public function get(string $key, string $default = null) {
        if (!$key) {
            throw new PantryDBMetadataKeyEmptyException("Value for metadata key is empty.");
        }

        $sql_get_metadata = Pantry::$db->prepare("SELECT kv_value FROM app_metadata WHERE kv_key = :kv_key");
        $sql_get_metadata->bindValue(':kv_key', $key, PDO::PARAM_STR);
        $sql_get_metadata->execute();

        if ($row = $sql_get_metadata->fetch(PDO::FETCH_ASSOC)) {
            return $row['kv_value'];
        }

        throw new PantryDBMetadataKeyNotFoundException("Key '$key' not found in DB metadata.");
    }

    /**
     * @param $key
     * @param $value
     * @throws PantryDBMetadataKeyEmptyException
     */
    public function set(string $key, string $value) {
        if (!$key) {
            throw new PantryDBMetadataKeyEmptyException("Value for metadata key is empty.");
        }

        Pantry::$logger->debug("Setting DB metadata '$key' to '$value'.");
        $sql_set_metadata = Pantry::$db->prepare("REPLACE INTO app_metadata (kv_key, kv_value) VALUES (:kv_key, :kv_value)");
        $sql_set_metadata->bindValue(':kv_key', $key, PDO::PARAM_STR);
        $sql_set_metadata->bindValue(':kv_value', $value, PDO::PARAM_STR);
        $sql_set_metadata->execute();
        $sql_set_metadata->closeCursor();
    }
}
