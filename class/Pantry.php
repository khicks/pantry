<?php

require_once(dirname(dirname(__FILE__))."/class/PantryApp.php");
require_once(dirname(dirname(__FILE__))."/controller/PantryAPI.php");
require_once(dirname(dirname(__FILE__))."/controller/PantryPage.php");

class Pantry {
    public static $initialized = false;

    public static $php_root;
    public static $web_root;
    public static $config;

    /** @var PantryLogger */
    public static $logger;

    /** @var PDO */
    public static $db;

    /** @var PantrySession */
    public static $session;

    private function __construct() {}

    public static function initialize() {
        if (self::$initialized)
            return;

        self::loadDirectories();
        self::loadFiles();
        self::loadConfig();
        self::loadLogger();
        self::loadDB();
        self::loadSession();
        self::$initialized = true;
    }

    private static function loadDirectories() {
        self::$php_root = dirname(dirname(__FILE__));
        self::$web_root = dirname($_SERVER['SCRIPT_NAME']);
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

    public static function generateUUID() {
        mt_srand();
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public static function getNow($offset = 0) {
        return date("Y-m-d H:i:s", time()+$offset);
    }
}
