<?php

class PantryClamp {
    private $start;

    public function __construct() {
        $this->start();
    }

    private function getTime() {
        return round(microtime(true) * 1000);
    }

    public function start() {
        $this->start = $this->getTime();
    }

    public function wait($milliseconds) {
        $time_to_wait = max(0, $milliseconds - ($this->getTime() - $this->start));
        usleep($time_to_wait * 1000);
    }
}
