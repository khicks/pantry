<?php

require_once(dirname(dirname(__FILE__))."/class/PantryApp.php");
require_once(dirname(dirname(__FILE__))."/class/PantryConfig.php");
require_once(dirname(dirname(__FILE__))."/controller/PantryAPI.php");
require_once(dirname(dirname(__FILE__))."/controller/PantryPage.php");
require_once(dirname(dirname(__FILE__))."/controller/PantryAdminAPI.php");
require_once(dirname(dirname(__FILE__))."/controller/PantryAdminPage.php");

class Pantry {
    public static $initialized = false;

    public static $php_root;
    public static $web_root;
    public static $cookie_path;

    /** @var PantryAppMetadata $app_metadata */
    public static $app_metadata;

    /** @var PantryConfig $config */
    public static $config;

    /** @var PantryLogger $logger */
    public static $logger;

    /** @var Parsedown $parsedown */
    public static $parsedown;

    /** @var HTMLPurifier $html_purifier */
    public static $html_purifier;

    /** @var PantryInstaller $installer */
    public static $installer;

    /** @var PDO $db */
    public static $db;

    /** @var PantryDBMetadata $db_metadata */
    public static $db_metadata;

    /** @var PantryUpdater $updater */
    public static $updater;

    /** @var PantrySession $session */
    public static $session;

    private function __construct() {}

    public static function initialize() {
        if (self::$initialized)
            return;

        self::loadDirectories();
        self::loadFiles();
        self::loadAppMetadata();
        self::loadConfig();
        self::loadLogger();
        self::loadParsedown();
        self::loadHTMLPurifier();
        self::loadInstaller();

        if (self::$installer->getIsInstalled()) {
            self::loadDB();
            self::loadConfigFromDB();
            self::loadDBMetadata();
            self::loadUpdater();
        }

        self::loadSession();
        self::$initialized = true;
    }

    private static function loadDirectories() {
        self::$php_root = dirname(dirname(__FILE__));
	    self::$web_root = dirname($_SERVER['SCRIPT_NAME']);

        if (self::$web_root === "/") {
            self::$web_root = "";
        }

        self::$cookie_path = self::$web_root . "/";
    }

    private static function loadFiles() {
        $load_files = [
            "/class/PantryAppMetadata.php",
            "/class/PantryConfig.php",
            "/class/PantryDBMetadata.php",
            "/class/PantryExceptions.php",
            "/class/PantryInstaller.php",
            "/class/PantryLogger.php",
            "/class/PantrySession.php",
            "/class/PantryUpdater.php"
        ];

        foreach ($load_files as $load_file) {
            require_once(self::$php_root.$load_file);
        }
    }

    private static function loadAppMetadata() {
        self::$app_metadata = new PantryAppMetadata();
    }

    private static function loadConfig() {
        self::$config = new PantryConfig();
    }

    private static function loadLogger() {
        self::$logger = new PantryLogger();
    }

    private static function loadParsedown() {
        self::$parsedown = new Parsedown();
        self::$parsedown->setSafeMode(true);
    }

    private static function loadHTMLPurifier() {
        $htmlp_config = HTMLPurifier_Config::createDefault();
        $htmlp_config->set('Cache.DefinitionImpl', null);
        $htmlp_config->set('Core.Encoding', "UTF-8");
        $htmlp_config->set('HTML.Allowed', "");
        self::$html_purifier = new HTMLPurifier($htmlp_config);
    }

    private static function loadInstaller() {
        self::$installer = new PantryInstaller();
    }

    private static function loadDB() {
        $type = self::$config->get('db_type');
        if (!$type) {
            die("Connection to database failed.");
        }

        $dsn = $username = $password = null;

        if ($type === "mysql") {
            $dsn = "mysql:"  .
                "host="   . self::$config->get('db_host')     . ";" .
                "port="   . self::$config->get('db_port')     . ";" .
                "dbname=" . self::$config->get('db_database') . ";" .
                "charset=utf8";
            $username = self::$config->get('db_username');
            $password = self::$config->get('db_password');
        }
        elseif ($type === "sqlite") {
            $dsn = "sqlite:" . self::$config->get('db_path');
        }

        try {
            self::$db = new PDO($dsn, $username, $password);
        } catch (PDOException $e) {
            if (self::$config->get('debug')) {
                die("Connection to database failed: ".$e->getMessage());
            }
            else {
                die("Connection to database failed.");
            }
        }
    }

    private static function loadConfigFromDB() {
        self::$config->loadFromDB();
    }

    private static function loadDBMetadata() {
        self::$db_metadata = new PantryDBMetadata();
    }

    private static function loadUpdater() {
        self::$updater = new PantryUpdater();
    }

    private static function loadSession() {
        self::$session = new PantrySession();
    }

    /**
     * @param int $bytes Number of bytes of random data to generate.
     * @param bool $hex Return random data as a hex string.
     * @return string Random data.
     */
    public static function getRandomData($bytes = 16, $hex = true) {
        try {
            if (!is_int($bytes)) {
                throw new PantryGetRandomDataParamNotIntException("The parameter passed to Pantry::getRandomData() is not an int.");
            }
        }
        catch (PantryGetRandomDataParamNotIntException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }

        try {
            $rand = openssl_random_pseudo_bytes($bytes, $random_is_secure);
            if (!$random_is_secure) {
                throw new PantryGetRandomDataParamNotSecureException("The data generated by Pantry::getRandomData() is not secure.");
            }
        }
        catch (PantryGetRandomDataParamNotSecureException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }

        return ($hex) ? bin2hex($rand) : $rand;
    }

    public static function generateUUID() {
        $parts = str_split(self::getRandomData(16, true), 4);
        return "{$parts[0]}{$parts[1]}-{$parts[2]}-{$parts[3]}-{$parts[4]}-{$parts[5]}{$parts[6]}{$parts[7]}";
    }

    public static function getNow($offset = 0) {
        return date("Y-m-d H:i:s", time()+$offset);
    }
}
