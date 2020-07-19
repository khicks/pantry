<?php

class PantryUpdater  {
    // 1 = update required, 0 = ok, -1 = db version too high, -2 = metadata missing
    /** @var int $status */
    private $status;

    private $app_version;
    private $app_db_version;
    private $db_db_version;
    private $upgrade_dir;
    private $upgrade_versions;

    public function __construct() {
        $this->app_version = Pantry::$app_metadata->get('app_version');
        $this->app_db_version = Pantry::$app_metadata->get('db_version');

        try {
            $this->db_db_version = Pantry::$db_metadata->get('db_version');
        }
        catch (PantryDBMetadataException $e) {
            Pantry::$logger->critical("Could not determine DB version in PantryUpdater constructor.");
        }

        $this->status = version_compare($this->app_db_version, $this->db_db_version);
        $this->upgrade_dir = Pantry::$php_root . "/system/sql/". Pantry::$config->get('db_type') . "/upgrade";
        $this->upgrade_versions = [];
    }

    public function getIsUpdated() {
        return ($this->status === 0);
    }

    public function getStatus() {
        return $this->status;
    }

    private function fetchUpgradeSQLVersionList() {
        $upgrade_file_full_list = scandir($this->upgrade_dir);

        foreach ($upgrade_file_full_list as $upgrade_file) {
            if (preg_match('/^upgrade_(.*)\.sql$/', $upgrade_file, $matches)) {
                if (version_compare($matches[1], $this->db_db_version) === 1 && version_compare($matches[1], $this->app_db_version) <= 0) {
                    $this->upgrade_versions[] = $matches[1];
                }
            }
        }
        usort($upgrade_versions, 'version_compare');
    }

    /**
     * @throws PantryInstallationServerException
     */
    private function runUpdateSQLs() {
        $this->fetchUpgradeSQLVersionList();
        Pantry::$logger->debug("Upgrade files in next log entry.");
        Pantry::$logger->debug(print_r($this->upgrade_versions, true));

        $type = Pantry::$config->get('db_type');

        if ($type === "mysql") {
            $this->runMySQLUpdateSQLs();
        }
        elseif ($type === "sqlite") {
            $this->runSQLiteUpdateSQLs();
        }
    }

    /**
     * @throws PantryInstallationServerException
     */
    private function runMySQLUpdateSQLs() {
        foreach ($this->upgrade_versions as $upgrade_version) {
            $upgrade_query = file_get_contents("{$this->upgrade_dir}/upgrade_{$upgrade_version}.sql");
            $sql_upgrade_query = Pantry::$db->prepare($upgrade_query);
            if (!$sql_upgrade_query->execute()) {
                Pantry::$logger->error("Could not run upgrade SQL query from file upgrade_{$upgrade_version}.sql.");
                Pantry::$logger->error(print_r($sql_upgrade_query->errorInfo(), true));
                throw new PantryInstallationServerException("INSTALL_ERROR_SQL_FAILED");
            }
            $sql_upgrade_query->closeCursor();

            try {
                Pantry::$db_metadata->set('db_version', $upgrade_version);
            }
            catch (PantryDBMetadataKeyEmptyException $e) {
                Pantry::$logger->critical("Could not set DB metadata after step upgrade.");
            }



            Pantry::$logger->warning("DB upgrade to version {$upgrade_version} complete.");
        }
    }

    /**
     * @throws PantryInstallationServerException
     */
    private function runSQLiteUpdateSQLs() {
        foreach ($this->upgrade_versions as $upgrade_version) {
            $upgrade_sql_file = "{$this->upgrade_dir}/upgrade_{$upgrade_version}.sql";
            $upgrade_queries = explode(';', file_get_contents($upgrade_sql_file));

            Pantry::$db->beginTransaction();
            foreach ($upgrade_queries as $query) {
                $upgrade_query = trim($query);
                if (empty($upgrade_query)) continue;

                $sql_upgrade_query = Pantry::$db->prepare($upgrade_query);
                if (!$sql_upgrade_query->execute()) {
                    Pantry::$logger->error("Could not run upgrade SQL query from file upgrade_{$upgrade_version}.sql.");
                    Pantry::$logger->error(print_r($sql_upgrade_query->errorInfo(), true));
                    Pantry::$db->rollBack();
                    throw new PantryInstallationServerException("INSTALL_ERROR_SQL_FAILED");
                }
                $sql_upgrade_query->closeCursor();
            }
            Pantry::$db->commit();

            try {
                Pantry::$db_metadata->set('db_version', $upgrade_version);
            }
            catch (PantryDBMetadataKeyEmptyException $e) {
                Pantry::$logger->critical("Could not set DB metadata after step upgrade.");
            }

            Pantry::$logger->warning("DB upgrade to version {$upgrade_version} complete.");
        }
    }

    /**
     * @throws PantryInstallationException
     */
    public function update() {
        Pantry::$logger->warning("Starting upgrade.");

        try {
            Pantry::$installer->checkInstallKey($_POST['key']);
            $this->runUpdateSQLs();
        }
        catch (PantryConfigurationException $e) {
            throw new PantryInstallationServerException("UPDATE_FAILED_UNKNOWN");
        }

        Pantry::$logger->notice("Updating Pantry from version ");
    }
}
