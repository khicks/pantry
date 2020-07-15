<?php

class PantryInstaller {
    private $is_installed;
    private $install_key_file;
    protected $install_key;

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
     * @param $host
     * @param int $port
     * @param $username
     * @param $password
     * @param $database
     * @throws PantryInstallationException
     * @return PDO
     */
    private function createDBConnection($host, int $port, $username, $password, $database) {
        if (!filter_var(gethostbyname($host), FILTER_VALIDATE_IP)) {
            throw new PantryInstallationClientException("INSTALL_ERROR_DB_HOST_INVALID", 422);
        }

        if ($port <= 0 || $port > 65535) {
            throw new PantryInstallationClientException("INSTALL_ERROR_DB_PORT_INVALID", 422);
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8";
        try {
            return new PDO($dsn, $username, $password, [
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        }
        catch (PDOException $e) {
            Pantry::$logger->error("Database connection could not be established.");
            Pantry::$logger->error($e->getMessage());
            throw new PantryInstallationClientException("INSTALL_ERROR_DB_NOT_CONNECTED");
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
     * @param $host
     * @param int $port
     * @param $username
     * @param $password
     * @param $database
     * @throws PantryInstallationServerException
     */
    private function createConfigFile($host, int $port, $username, $password, $database) {
        $config_file_path = Pantry::$php_root . "/data/config.php";
        $config = [
            'db_host' => $host,
            'db_port' => $port,
            'db_username' => $username,
            'db_password' => $password,
            'db_database' => $database
        ];

        if (!file_put_contents($config_file_path, "<?php\n\nreturn " . var_export($config, true) . ";\n")) {
            throw new PantryInstallationServerException("INSTALL_ERROR_CONFIG_FILE_NOT_CREATED");
        }
    }

    /**
     * @throws PantryInstallationServerException
     */
    private function runBaseSQL() {
        $base_sql_file = Pantry::$php_root . "/system/sql/base/base_" . Pantry::$app_metadata->get('db_version') . ".sql";
        $base_query = file_get_contents($base_sql_file);
        $sql_base_query = Pantry::$db->prepare($base_query);

        if (!$sql_base_query->execute()) {
            Pantry::$logger->error("Could not run base SQL query to build tables.");
            Pantry::$logger->error(print_r($sql_base_query->errorInfo(), true));
            throw new PantryInstallationServerException("INSTALL_ERROR_SQL_FAILED");
        }
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

            Pantry::$db = $this->createDBConnection($_POST['db_host'], (int)$_POST['db_port'], $_POST['db_username'], $_POST['db_password'], $_POST['db_database']);
            Pantry::$db_metadata = new PantryDBMetadata();

            $this->createDataDirectories();
            $this->createConfigFile($_POST['db_host'], (int)$_POST['db_port'], $_POST['db_username'], $_POST['db_password'], $_POST['db_database']);
            $this->runBaseSQL();
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
