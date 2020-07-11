<?php

class PantryConfig {
    private const DEFAULTS = [
        'app_name'         => "Pantry",
        'app_language'     => "en_us",
        'app_brand_format' => "logo",
        'db_host'          => null,
        'db_username'      => null,
        'db_password'      => null,
        'db_database'      => null,
        'image_dir'        => "data/images",
        'image_max_size'   => "5MB",
        'log_dir'          => "data/log",
        'log_level'        => "WARNING",
        'log_max_size'     => "50KB",
        'log_max_files'    => 10,
        'session_timeout'  => 259200,
        'csrf_required'    => true,
    ];

    private $config_file = null;
    private $config = self::DEFAULTS;

    /**
     * PantryConfig constructor.
     * @throws PantryConfigurationException
     */
    public function __construct() {
        // load config file
        if ($config_file = include(Pantry::$php_root."/data/config.php")) {
            if (is_array($config_file)) {
                $this->config_file = $config_file;
            }
        }

        foreach ($this->config as $key => $value) {
            // set from config file
            if (!is_null($config_file) && array_key_exists($key, $config_file) && !is_null($config_file[$key])) {
                $this->config[$key] = $this->config_file[$key];
            }

            // set from env
            if (getenv(strtoupper($key))) {
                $this->config[$key] = getenv(strtoupper($key));
            }
        }

        $this->validate();
    }

    /**
     * Checks for invalid config parameters and replaces with defaults if possible.
     * @throws PantryConfigurationException
     */
    private function validate() {
        // app_language (default)
        if (!file_exists(Pantry::$php_root . "/language/{$this->config['app_language']}.php")) {
            $this->config['app_language'] = self::DEFAULTS['app_language'];
        }

        // app_brand_format (default)
        if (!in_array($this->config['app_brand_format'], ["logo", "text", true])) {
            $this->config['app_brand_format'] = self::DEFAULTS['app_brand_format'];
        }

        // log_dir (fail)
        $this->config['log_dir'] = (substr($this->config['log_dir'], 0, 1) === "/")
            ? $this->config['log_dir']
            : Pantry::$php_root . "/" . $this->config['log_dir'];
        if (!is_writable($this->config['log_dir']) && !mkdir($this->config['log_dir'], 0755, true)) {
            throw new PantryConfigurationException("log_dir", $this->config['log_dir'],
                "Log directory {$this->config['log_dir']} is not writable.");
        }
        if (!is_writable("{$this->config['log_dir']}/pantry.log") && !touch("{$this->config['log_dir']}/pantry.log")) {
            throw new PantryConfigurationException("log_dir", $this->config['log_dir'],
                "Log file 'pantry.log' is not writable in log_dir {$this->config['log_dir']}");
        }

        // log_level (default)
        $this->config['log_level'] = strtoupper($this->config['log_level']);
        if (!in_array($this->config['log_level'], ["EMERGENCY", "ALERT", "CRITICAL", "ERROR", "WARNING", "NOTICE", "INFO", "DEBUG"], true)) {
            $this->config['log_level'] = self::DEFAULTS['log_level'];
        }

        // log_max_size (default)
        $this->config['log_max_size'] = $this->toBytes($this->config['log_max_size']);
        if ($this->config['log_max_size'] <= 0) {
            $this->config['log_max_size'] = $this->toBytes(self::DEFAULTS['log_max_size']);
        }

        // log_max_files (default)
        $this->config['log_max_files'] = (int)$this->config['log_max_files'];
        if ($this->config['log_max_files'] < 1) {
            $this->config['log_max_files'] = 10;
        }

        // image_dir (fail)
        $this->config['image_dir'] = (substr($this->config['image_dir'], 0, 1) === "/")
            ? $this->config['image_dir']
            : Pantry::$php_root . "/" . $this->config['image_dir'];
        if (!is_writable($this->config['image_dir']) && !mkdir($this->config['image_dir'], 0755, true)) {
            throw new PantryConfigurationException("image_dir", $this->config['image_dir'],
                "Image directory {$this->config['image_dir']} is not writable.");
        }

        // image_max_size (default)
        $this->config['image_max_size'] = $this->toBytes($this->config['image_max_size']);
        if ($this->config['image_max_size'] <= 0) {
            $this->config['image_max_size'] = $this->toBytes(self::DEFAULTS['image_max_size']);
        }

        // session_timeout (default)
        $this->config['session_timeout'] = $this->toSeconds($this->config['session_timeout']);
        if ($this->config['session_timeout'] <= 0) {
            $this->config['session_timeout'] = $this->toBytes(self::DEFAULTS['session_timeout']);
        }
        if ($this->config['session_timeout'] > 31622400) { // 1 (leap) year
            $this->config['session_timeout'] = 31622400;
        }

        // csrf_required (default)
        $this->config['csrf_required'] = (in_array($this->config['csrf_required'], [true, "true", 1, "1"], true));
    }

    private function toBytes($size) {
        $units = [
            'b' => 1,
            'k' => 1024,
            'm' => 1024 * 1024,
            'g' => 1024 * 1024 * 1024,
        ];

        if (is_int($size)) {
            return $size;
        }

        if (preg_match('/^\s*([0-9]+\.[0-9]+|\.?[0-9]+)\s*(k|m|g|b)(b?ytes)?/i', $size, $match)) {
            return (int)$match[1] * $units[strtolower($match[2])];
        }

        return 0;
    }

    private function toSeconds($time) {
        $units = [
            's' => 1,
            'm' => 60,
            'h' => 60 * 60,
            'd' => 60 * 60 * 24,
        ];

        if (is_numeric($time)) {
            return (int)$time;
        }

        if (preg_match('/^\s*([0-9]+\.[0-9]+|\.?[0-9]+)\s*(s|m|h|d)/i', $time, $match)) {
            return (int)$match[1] * $units[strtolower($match[2])];
        }

        return 0;
    }

    public function loadFromDB() {
        $acceptable_vars = [
            "app_name",
            "app_language",
            "app_brand_format",
            "session_timeout",
            "csrf_required"
        ];

        $sql_get_config = Pantry::$db->query("SELECT name, data FROM app_config", PDO::FETCH_ASSOC);
        foreach ($sql_get_config as $row) {
            if (array_key_exists($row['name'], $this->config) && in_array($row['name'], $acceptable_vars, true)) {
                $this->config[$row['name']] = $row['data'];
            }
        }

        try { $this->validate(); } catch (PantryConfigurationException $e) {}
    }

    public function get($param) {
        return $this->config[$param];
    }
}