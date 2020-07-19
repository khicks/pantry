<?php

class PantryInstaller {
    private $is_installed;
    private $install_key_file;
    private $install_key;

    public function __construct() {
        $this->is_installed = $this->fetchIsInstalled();
        $this->install_key_file = Pantry::$php_root . "/data/system/install_key";
        $this->install_key = $this->fetchInstallKey();
    }

    private function fetchIsInstalled() {
        return file_exists(Pantry::$php_root . "/data/system/installed");
    }

    private function fetchInstallKey() {
        if (!file_exists($this->install_key_file)) {
            return $this->createInstallKey();
        }

        $install_key = trim(file_get_contents($this->install_key_file));
        if (!preg_match('/^[0-9a-f]{64}$/', $install_key)) {
            Pantry::$logger->warning("Replacing invalid install key in {$this->install_key_file}");
            $install_key = $this->createInstallKey();
        }

        return $install_key;
    }

    private function createInstallKey() {
        $new_install_key = $this->generateInstallKey();
        if (!file_exists(dirname($this->install_key_file)) && ! mkdir(dirname($this->install_key_file), 0775, true)) {
            Pantry::$logger->critical("Could not create .../data/system directory.");
            die(http_response_code(500));
        }

        if (file_put_contents($this->install_key_file, $new_install_key) === false) {
            Pantry::$logger->critical("Could not rewrite install key file to {$this->install_key_file}");
            die(http_response_code(500));
        }

        return $new_install_key;
    }

    private function generateInstallKey() {
        return Pantry::getRandomData(32) . "\n";
    }

    public function getIsInstalled() {
        return $this->is_installed;
    }

    public function getSupportedDatabases() {
        $databases = [];
        $extensions = get_loaded_extensions();

        if (in_array('pdo_mysql', $extensions, true)) {
            $databases[] = ['type' => "mysql", 'description' => "MySQL"];
        }

        if (in_array('pdo_sqlite', $extensions, true)) {
            $databases[] = ['type' => "sqlite", 'description' => "SQLite"];
        }

        return $databases;
    }

    public function checkSupportedDatabase($type) {
        $databases = $this->getSupportedDatabases();

        foreach ($databases as $database) {
            if ($database['type'] === $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $install_key
     * @throws PantryInstallationClientException
     */
    public function checkInstallKey($install_key) {
        if ($install_key !== $this->install_key) {
            throw new PantryInstallationClientException("INSTALL_KEY_CHECK_ERROR", 401);
        }
    }

    /**
     * @param array $params
     * @throws PantryInstallationException
     * @return PDO
     */
    private function createDBConnection($params) {
        if ($params['type'] === "mysql") {
            return $this->createMySQLDBConnection($params);
        }

        if ($params['type'] === "sqlite") {
            return $this->createSQLiteDBConnection($params);
        }

        throw new PantryInstallationClientException("INSTALL_ERROR_DB_TYPE_NOT_FOUND");
    }

    /**
     * @param array $params
     * @throws PantryInstallationException
     * @return PDO
     */
    private function createMySQLDBConnection($params) {
        if (!filter_var(gethostbyname($params['host']), FILTER_VALIDATE_IP)) {
            throw new PantryInstallationClientException("INSTALL_ERROR_DB_HOST_INVALID", 422);
        }

        if ($params['port'] <= 0 || $params['port'] > 65535) {
            throw new PantryInstallationClientException("INSTALL_ERROR_DB_PORT_INVALID", 422);
        }

        $dsn = "mysql:host={$params['host']};port={$params['port']};dbname={$params['database']};charset=utf8";
        try {
            return new PDO($dsn, $params['username'], $params['password'], [
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        }
        catch (PDOException $e) {
            Pantry::$logger->error("MySQL connection could not be established.");
            Pantry::$logger->error($e->getMessage());
            throw new PantryInstallationClientException("INSTALL_ERROR_DB_NOT_CONNECTED");
        }
    }

    /**
     * @param array $params
     * @throws PantryInstallationException
     * @return PDO
     */
    private function createSQLiteDBConnection($params) {
        $dsn = "sqlite:" . Pantry::$php_root."/".$params['path'];
        try {
            return new PDO($dsn);
        }
        catch (PDOException $e) {
            Pantry::$logger->error("SQLite connection could not be established.");
            Pantry::$logger->error($e->getMessage());
            throw new PantryInstallationServerException("INSTALL_ERROR_DB_NOT_CONNECTED");
        }
    }


    /**
     * @throws PantryInstallationServerException
     */
    private function createDataDirectories() {
        $dirs = [
            Pantry::$php_root . "/data/images"
        ];

        foreach ($dirs as $dir) {
            if (!file_exists($dir) && !mkdir($dir)) {
                throw new PantryInstallationServerException("INSTALL_ERROR_DIRS_NOT_CREATED");
            }
        }
    }

    /**
     * @param array $db_params
     * @throws PantryInstallationServerException
     */
    private function createConfigFile($db_params) {
        $config_file_path = Pantry::$php_root . "/data/config.php";
        $config = [
            'db_type' => $db_params['type']
        ];

        if ($db_params['type'] === "mysql") {
            $config['db_host'] = $db_params['host'];
            $config['db_port'] = $db_params['port'];
            $config['db_username'] = $db_params['username'];
            $config['db_password'] = $db_params['password'];
            $config['db_database'] = $db_params['database'];
        }
        elseif ($db_params['type'] === "sqlite") {
            $config['db_path'] = $db_params['path'];
        }

        if (!file_put_contents($config_file_path, "<?php\n\nreturn " . var_export($config, true) . ";\n")) {
            throw new PantryInstallationServerException("INSTALL_ERROR_CONFIG_FILE_NOT_CREATED");
        }
    }

    /**
     * @param string $db_type
     * @throws PantryInstallationServerException
     */
    private function runBaseSQL($db_type) {
        if ($db_type === "mysql") {
            $this->runMySQLBaseSQL();
        }
        elseif ($db_type === "sqlite") {
            $this->runSQLiteBaseSQL();
        }
    }

    /**
     * @throws PantryInstallationServerException
     */
    private function runMySQLBaseSQL() {
        $base_sql_file = Pantry::$php_root . "/system/sql/mysql/base/base_" . Pantry::$app_metadata->get('db_version') . ".sql";
        $base_query = file_get_contents($base_sql_file);
        $sql_base_query = Pantry::$db->prepare($base_query);

        if (!$sql_base_query->execute()) {
            Pantry::$logger->error("Could not run base SQL query to build tables.");
            Pantry::$logger->error(print_r($sql_base_query->errorInfo(), true));
            throw new PantryInstallationServerException("INSTALL_ERROR_SQL_FAILED");
        }
    }

    /**
     * @throws PantryInstallationServerException
     */
    private function runSQLiteBaseSQL() {
        $base_sql_file = Pantry::$php_root . "/system/sql/sqlite/base/base_" . Pantry::$app_metadata->get('db_version') . ".sql";
        $base_queries = explode(';', file_get_contents($base_sql_file));

        Pantry::$db->beginTransaction();
        foreach ($base_queries as $query) {
            $base_query = trim($query);
            if (empty($base_query)) continue;

            $sql_base_query = Pantry::$db->prepare($base_query);
            if (!$sql_base_query->execute()) {
                Pantry::$logger->error("Could not run base SQL query to build tables.");
                Pantry::$logger->error(print_r($sql_base_query->errorInfo(), true));
                Pantry::$db->rollBack();
                throw new PantryInstallationServerException("INSTALL_ERROR_SQL_FAILED");
            }
        }
        Pantry::$db->commit();
    }

    /** @throws PantryInstallationServerException */
    private function populateDBMetadata() {
        try {
            Pantry::$db_metadata->set('db_version', Pantry::$app_metadata->get('db_version'));
        }
        catch (PantryDBMetadataException $e) {
            throw new PantryInstallationServerException("INSTALL_ERROR_METADATA_FAILED");
        }
    }

    /**
     * @param $language
     * @throws PantryConfigurationException
     */
    private function populateDBConfig($language) {
        Pantry::$config->setDBConfig('app_language', $language->getCode());
    }

    /**
     * @throws PantryInstallationException
     */
    public function install() {
        try {
            $this->checkInstallKey($_POST['key']);
            $language = new PantryLanguage($_POST['language']);

            $user = new PantryUser();
            $user->setUsername($_POST['user_username']);
            $user->setFirstName($_POST['user_firstname']);
            $user->setLastName($_POST['user_lastname']);
            $user->setPassword($_POST['user_password']);
            $user->setIsAdmin(true);
            $user->setIsDisabled(false);

            $db_params = [
                'type' => $_POST['db_type'],
                'host' => $_POST['db_host'],
                'port' => (int)$_POST['db_port'],
                'username' => $_POST['db_username'],
                'password' => $_POST['db_password'],
                'database' => $_POST['db_database'],
                'path' => "data/pantry.db",
            ];

            Pantry::$db = $this->createDBConnection($db_params);
            Pantry::$db_metadata = new PantryDBMetadata();

            $this->createDataDirectories();
            $this->createConfigFile($db_params);
            $this->runBaseSQL($db_params['type']);
            $this->populateDBMetadata();
            $this->populateDBConfig($language);

            PantryUser::checkUsername($_POST['user_username']);
            $user->save();

            touch(Pantry::$php_root . "/data/system/installed");
        }
        catch (PantryLanguageNotFoundException $e) {
            throw new PantryInstallationClientException("INSTALL_ERROR_LANGUAGE");
        }
        catch (PantryUsernameTooShortException $e) {
            throw new PantryInstallationClientException("USER_USERNAME_TOO_SHORT");
        }
        catch (PantryUsernameNotAvailableException $e) {
            throw new PantryInstallationClientException("USER_USERNAME_NOT_AVAILABLE");
        }
        catch (PantryUsernameValidationException $e) {
            throw new PantryInstallationClientException("USER_USERNAME_INVALID");
        }
        catch (PantryUserFirstNameTooLongException $e) {
            throw new PantryInstallationClientException("USER_FIRST_NAME_TOO_LONG");
        }
        catch (PantryUserLastNameTooLongException $e) {
            throw new PantryInstallationClientException("USER_LAST_NAME_TOO_LONG");
        }
        catch (PantryUserPasswordEmptyException $e) {
            throw new PantryInstallationClientException("USER_PASSWORD_NOT_PROVIDED");
        }
        catch (PantryConfigurationException $e) {
            throw new PantryInstallationServerException("INSTALL_ERROR_DB_CONFIG_FAILED");
        }
    }
}
