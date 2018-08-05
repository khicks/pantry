<?php

class PantryCurrentUser extends PantryUser {
    /**
     * PantryCurrentUser constructor.
     * @param $user_id
     * @throws PantryUserNotFoundException
     */
    public function __construct($user_id) {
        parent::__construct($user_id);
    }
}
