<?php

class PantryLanguage {
    private const LANGUAGES = [
        "en_us",
        "es_es"
    ];

    private $code;
    private $strings;

    /**
     * PantryLanguage constructor.
     * @param $code
     * @throws PantryLanguageNotFoundException
     */
    public function __construct($code) {
        if (!in_array($code, self::LANGUAGES, true)) {
            throw new PantryLanguageNotFoundException("Language not acceptable: $code");
        }

        $lang_file = Pantry::$php_root . "/language/{$code}.php";
        if (!file_exists($lang_file)) {
            throw new PantryLanguageNotFoundException("Language file not found: $code");
        }

        $this->code = $code;
        $this->strings = require($lang_file);
    }

    public function getCode() {
        return $this->code;
    }

    public function get($key) {
        if (!array_key_exists($key, $this->strings)) {
            Pantry::$logger->warning("Missing language key $key in {$this->code}");
            return "";
        }

        return $this->strings[$key];
    }

    public function getAll() {
        return $this->strings;
    }

    public static function list() {
        $languages = [];

        foreach (self::LANGUAGES as $language_code) {
            try {
                $lang = new self($language_code);
            } catch (PantryLanguageNotFoundException $e) { continue; }

            $languages[] = [
                'code' => $lang->getCode(),
                'description' => $lang->get('LANGUAGE_DESC')
            ];
        }

        return $languages;
    }
}