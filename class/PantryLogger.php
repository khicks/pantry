<?php

class PantryLogger {
    private $log_level;
    private $levels = [
        'EMERGENCY' => 0,
        'ALERT'     => 1,
        'CRITICAL'  => 2,
        'ERROR'     => 3,
        'WARNING'   => 4,
        'NOTICE'    => 5,
        'INFO'      => 6,
        'DEBUG'     => 7
    ];

    private $file_name;
    private $file_handle;
    private $max_size;
    private $max_files;

    public function __construct() {
        $this->file_name = Pantry::$php_root."/".Pantry::$config['log_file'];
        $this->log_level = Pantry::$config['log_level'];
        $this->max_size = Pantry::$config['log_max_size'];
        $this->max_files = Pantry::$config['log_max_files'];

        $this->openFile();
        $this->rotate();
    }

    private function openFile() {
        $this->file_handle = fopen($this->file_name, 'a');
    }

    public function emergency($message) {
        $this->log("EMERGENCY", $message);
    }

    public function alert($message) {
        $this->log("ALERT", $message);
    }

    public function critical($message) {
        $this->log("CRITICAL", $message);
    }

    public function error($message) {
        $this->log("ERROR", $message);
    }

    public function warning($message) {
        $this->log("WARNING", $message);
    }

    public function notice($message) {
        $this->log("NOTICE", $message);
    }

    public function info($message) {
        $this->log("INFO", $message);
    }

    public function debug($message) {
        $this->log("DEBUG", $message);
    }

    public function log($level, $message) {
        if ($this->levels[$level] <= $this->levels[$this->log_level]) {
            $this->write("[{$this->getTimestamp()}][{$level}] $message");
        }
    }

    public function write($message) {
        if ($this->file_handle !== null) {
            fwrite($this->file_handle, trim($message)."\n");
        }
    }

    private function getTimestamp() {
        $originalTime = microtime(true);
        $micro = sprintf("%06d", ($originalTime - floor($originalTime)) * 1000000);
        $date = new DateTime(date('Y-m-d H:i:s.'.$micro, $originalTime));

        return $date->format('Y-m-d G:i:s.u');
    }

    private function rotate() {
        $units = [
            'b' => 1,
            'k' => 1024,
            'm' => 1024 * 1024,
            'g' => 1024 * 1024 * 1024,
        ];

        if (preg_match('/^\s*([0-9]+\.[0-9]+|\.?[0-9]+)\s*(k|m|g|b)(b?ytes)?/i', $this->max_size, $match)) {
            $rotate_at = (int)$match[1] * $units[strtolower($match[2])];
        }

        if (isset($rotate_at) && is_int($rotate_at) && filesize($this->file_name) >= $rotate_at) {
            $this->info("Rotating log.");
            for ($i = $this->max_files-2; $i>0; $i--) {
                if (file_exists("{$this->file_name}.{$i}")) {
                    $old_name = "{$this->file_name}.{$i}";
                    $new_name = "{$this->file_name}.".($i+1);
                    rename($old_name, $new_name);
                }
            }
            rename($this->file_name, $this->file_name.".1");
            $this->openFile();
        }
    }
}
