<?php

require_once(dirname(dirname(__FILE__))."/class/PantryApp.php");
require_once(dirname(dirname(__FILE__))."/controller/PantryAPI.php");
require_once(dirname(dirname(__FILE__))."/controller/PantryPage.php");
require_once(dirname(dirname(__FILE__))."/controller/PantryAdminAPI.php");
require_once(dirname(dirname(__FILE__))."/controller/PantryAdminPage.php");

class Pantry {
    public static $initialized = false;

    public static $php_root;
    public static $web_root;
    public static $cookie_path;
    public static $config;

    /** @var PantryLogger $logger */
    public static $logger;

    /** @var Parsedown $parsedown */
    public static $parsedown;

    /** @var HTMLPurifier $html_purifier */
    public static $html_purifier;

    /** @var PDO $db */
    public static $db;

    /** @var PantrySession $session */
    public static $session;

    private function __construct() {}

    public static function initialize() {
        if (self::$initialized)
            return;

        self::loadDirectories();
        self::loadFiles();
        self::loadConfig();
        self::loadLogger();
        self::loadParsedown();
        self::loadHTMLPurifier();
        self::loadDB();
        self::loadSession();
        self::$initialized = true;
    }

    private static function loadDirectories() {
        self::$php_root = dirname(dirname(__FILE__));
	self::$web_root = dirname($_SERVER['SCRIPT_NAME']);
        self::$cookie_path = self::$web_root;

        if (self::$web_root === "/") {
            self::$web_root = "";
        }
    }

    private static function loadFiles() {
        $load_files = [
            "/class/PantryLogger.php",
            "/class/PantrySession.php"
        ];

        foreach ($load_files as $load_file) {
            require_once(self::$php_root.$load_file);
        }
    }

    private static function loadConfig() {
        if (file_exists(self::$php_root."/config.php")) {
            self::$config = require_once(self::$php_root."/config.php");
        }
        else {
            header("Location: ".self::$web_root."/install");
            die();
        }
    }

    private static function loadLogger() {
        self::$logger = new PantryLogger();
    }

    private static function loadParsedown() {
        self::$parsedown = new Parsedown();
        self::$parsedown->setSafeMode(true);
    }

    private static function loadHTMLPurifier() {
        $config = HTMLPurifier_Config::createDefault();
        self::$html_purifier = new HTMLPurifier($config);
    }

    private static function loadDB() {
        $dsn = "mysql:host=".self::$config['db_host'].";dbname=".self::$config['db_database'].";charset=utf8";
        $user = self::$config['db_username'];
        $password = self::$config['db_password'];

        try {
            self::$db = new PDO($dsn, $user, $password);
        } catch (PDOException $e) {
            if (self::$config['debug']) {
                die("Connection to database failed: ".$e->getMessage());
            }
            else {
                die("Connection to database failed.");
            }
        }
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
