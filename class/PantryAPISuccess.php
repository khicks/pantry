<?php

class PantryAPISuccess extends PantryAPIResponse {
    public function __construct($code = "GENERAL_SUCCESS", $description = "", $data = []) {
        parent::__construct();
        $this->status = "success";
        $this->code = $code;
        $this->description = $description;
        $this->data = $data;
    }
}
