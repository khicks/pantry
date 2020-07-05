<?php

class PantryUser {
    public static $error_map = [
        'PantryUsernameNotProvidedException' => "USER_USERNAME_NOT_PROVIDED",
        'PantryUsernameTooShortException' => "USER_USERNAME_TOO_SHORT",
        'PantryUsernameTooLongException' => "USER_USERNAME_TOO_LONG",
        'PantryUsernameInvalidException' => "USER_USERNAME_INVALID",
        'PantryUsernameNotAvailableException' => "USER_USERNAME_NOT_AVAILABLE",
        'PantryUserPasswordEmptyException' => "USER_PASSWORD_NOT_PROVIDED",
        'PantryUserPasswordIncorrectException' => "USER_PASSWORD_INCORRECT",
        'PantryUserFirstNameTooLongException' => "USER_FIRST_NAME_TOO_LONG",
        'PantryUserLastNameTooLongException' => "USER_LAST_NAME_TOO_LONG"
    ];

    protected $id;
    private $created;
    private $username;
    private $password;
    private $is_admin;
    private $is_disabled;
    private $last_login;
    private $first_name;
    private $last_name;

    /**
     * PantryUser constructor.
     * @param $user_id
     * @throws PantryUserNotFoundException
     */
    public function __construct($user_id = null) {
        $this->id = null;
        $this->created = null;
        $this->username = null;
        $this->password = null;
        $this->is_admin = null;
        $this->is_disabled = null;
        $this->last_login = null;
        $this->first_name = null;
        $this->last_name = null;

        if ($user_id) {
            $sql_get_user = Pantry::$db->prepare("SELECT id, created, username, password, is_admin, is_disabled, last_login, first_name, last_name FROM users WHERE id=:user_id");
            $sql_get_user->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sql_get_user->execute();

            if ($user_row = $sql_get_user->fetch(PDO::FETCH_ASSOC)) {
                $this->id = $user_row['id'];
                $this->created = $user_row['created'];
                $this->username = $user_row['username'];
                $this->password = $user_row['password'];
                $this->is_admin = boolval($user_row['is_admin']);
                $this->is_disabled = boolval($user_row['is_disabled']);
                $this->last_login = $user_row['last_login'];
                $this->first_name = $user_row['first_name'];
                $this->last_name = $user_row['last_name'];
            }
            else {
                throw new PantryUserNotFoundException("User not found.");
            }
        }
    }

    public function getID() {
        return $this->id;
    }

    public function getCreated() {
        return $this->created;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getIsAdmin() {
        return $this->is_admin;
    }

    public function getIsDisabled() {
        return $this->is_disabled;
    }

    public function getLastLogin() {
        return $this->last_login;
    }

    public function getFirstName() {
        return $this->first_name;
    }

    public function getLastName() {
        return $this->last_name;
    }

    public function getFullName() {
        if (empty($this->first_name) && empty($this->last_name)) {
            return null;
        }
        if (empty($this->last_name)) {
            return $this->first_name;
        }
        if (empty($this->first_name)) {
            return $this->last_name;
        }
        return "{$this->first_name} {$this->last_name}";
    }

    public function getDisplayName() {
        if (is_null($this->getFullName())) {
            return $this->getUsername();
        }
        return "{$this->getFullName()} ({$this->getUsername()})";
    }

    /**
     * @param $username
     * @param bool $sanitize
     * @throws PantryUsernameValidationException
     */
    public function setUsername($username, $sanitize = true) {
        if ($sanitize) {
            $username = Pantry::$html_purifier->purify($username);
            $username = trim(preg_replace('/\s+/', " ", $username));
            self::checkUsername($username, $this); // checks format and availability
        }

        $this->username = $username;
    }

    /**
     * @param string $password
     * @throws PantryUserPasswordValidationException
     */
    public function setPassword(string $password) {
        if (empty($password)) {
            throw new PantryUserPasswordEmptyException();
        }

        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    public function setIsAdmin($is_admin) {
        $this->is_admin = boolval($is_admin);
    }

    public function setIsDisabled($is_disabled) {
        $this->is_disabled = boolval($is_disabled);
    }

    public function setLastLogin($last_login = null) {
        if ($last_login === null) {
            $last_login = Pantry::getNow();
        }
        elseif ($last_login === false) {
            $last_login = null;
        }
        else {
            $last_login = date("Y-m-d H:i:s", $last_login);
        }

        $this->last_login = $last_login;
    }

    /**
     * @param $first_name
     * @param bool $sanitize
     * @throws PantryUserFirstNameTooLongException
     */
    public function setFirstName($first_name, $sanitize = true) {
        if ($sanitize) {
            $first_name = Pantry::$html_purifier->purify($first_name);
            $first_name = trim(preg_replace('/\s+/', " ", $first_name));
            if (strlen($first_name) > 64) {
                throw new PantryUserFirstNameTooLongException($first_name);
            }
        }

        $this->first_name = trim($first_name);
    }

    /**
     * @param $last_name
     * @param bool $sanitize
     * @throws PantryUserLastNameTooLongException
     */
    public function setLastName($last_name, $sanitize = true) {
        if ($sanitize) {
            $last_name = Pantry::$html_purifier->purify($last_name);
            $last_name = trim(preg_replace('/\s+/', " ", $last_name));
            if (strlen($last_name) > 64) {
                throw new PantryUserLastNameTooLongException($last_name);
            }
        }

        $this->last_name = trim($last_name);
    }

    public function save() {
        try {
            if ($this->id) {
                $sql_save_user = Pantry::$db->prepare("UPDATE users SET username=:username, password=:password, is_admin=:is_admin, is_disabled=:is_disabled, last_login=:last_login, first_name=:first_name, last_name=:last_name WHERE id=:id");
                $sql_save_user->bindValue(':id', $this->id, PDO::PARAM_STR);
                $sql_save_user->bindValue(':username', $this->username, PDO::PARAM_STR);
                $sql_save_user->bindValue(':password', $this->password, PDO::PARAM_STR);
                $sql_save_user->bindValue(':is_admin', $this->is_admin, PDO::PARAM_INT);
                $sql_save_user->bindValue(':is_disabled', $this->is_disabled, PDO::PARAM_INT);
                $sql_save_user->bindValue(':last_login', $this->last_login, PDO::PARAM_STR);
                $sql_save_user->bindValue(':first_name', $this->first_name, PDO::PARAM_STR);
                $sql_save_user->bindValue(':last_name', $this->last_name, PDO::PARAM_STR);
                if (!$sql_save_user->execute()) {
                    throw new PantryUserNotSavedException("User '{$this->username}' could not be saved");
                }
            }
            else {
                if (in_array(null, [$this->username, $this->password, $this->is_admin, $this->is_disabled], true)) {
                    throw new PantryUserNotSavedException("User could not be created because a required field was null.");
                }
                $sql_create_user = Pantry::$db->prepare("INSERT INTO users (id, created, username, password, is_admin, is_disabled, last_login, first_name, last_name) VALUES (:id, NOW(), :username, :password, :is_admin, :is_disabled, :last_login, :first_name, :last_name)");
                $sql_create_user->bindValue(':id', Pantry::generateUUID(), PDO::PARAM_STR);
                $sql_create_user->bindValue(':username', $this->username, PDO::PARAM_STR);
                $sql_create_user->bindValue(':password', $this->password, PDO::PARAM_STR);
                $sql_create_user->bindValue(':is_admin', $this->is_admin, PDO::PARAM_INT);
                $sql_create_user->bindValue(':is_disabled', $this->is_disabled, PDO::PARAM_INT);
                $sql_create_user->bindValue(':last_login', $this->last_login, PDO::PARAM_STR);
                $sql_create_user->bindValue(':first_name', $this->first_name, PDO::PARAM_STR);
                $sql_create_user->bindValue(':last_name', $this->last_name, PDO::PARAM_STR);
                if (!$sql_create_user->execute()) {
                    throw new PantryUserNotSavedException("User could not be created.");
                }
            }
        }
        catch (PantryUserNotSavedException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }
    }

    public function delete() {
        Pantry::$logger->debug("Called to delete user {$this->username}.");

        try {
            $sql_delete_user = Pantry::$db->prepare("DELETE FROM users WHERE id=:id");
            $sql_delete_user->bindValue(':id', $this->id, PDO::PARAM_STR);
            if (!$sql_delete_user->execute()) {
                throw new PantryUserNotDeletedException("User {$this->username} could not be deleted.");
            }
        }
        catch (PantryUserNotDeletedException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }

        $this->id = null;
        $this->created = null;
        $this->username = null;
        $this->password = null;
        $this->is_admin = null;
        $this->is_disabled = null;
        $this->last_login = null;
        $this->first_name = null;
        $this->last_name = null;
    }

    public function twoFactorRequired() {
        $sql_lookup_two_factor_required = Pantry::$db->prepare("SELECT id FROM two_factor_keys WHERE user_id=:user_id");
        $sql_lookup_two_factor_required->bindValue(':user_id', $this->id, PDO::PARAM_STR);
        $sql_lookup_two_factor_required->execute();

        if ($lookup_two_factor_required_row = $sql_lookup_two_factor_required->fetch(PDO::FETCH_ASSOC)) {
            return true;
        }

        return false;
    }

    public function disableTwoFactorAuth() {
        PantryTwoFactorSession::purgeUser($this->id);
        PantryTwoFactorLogin::purgeUser($this->id);
        $two_factor_key = new PantryTwoFactorKey($this->id);
        $two_factor_key->destroy();
    }

    public static function lookupUsername($username) {
        if (!$username) {
            return false;
        }

        if (!preg_match('/^[A-Za-z0-9-_]{3,32}$/', $username)) {
            return false;
        }

        $sql_lookup_username = Pantry::$db->prepare("SELECT id FROM users WHERE username=:username");
        $sql_lookup_username->bindValue(':username', $username, PDO::PARAM_STR);
        $sql_lookup_username->execute();

        if ($user_row = $sql_lookup_username->fetch(PDO::PARAM_STR)) {
            return $user_row['id'];
        }

        return false;
    }

    /**
     * @param $username
     * @param PantryUser|null $user
     * @return string
     * @throws PantryUsernameValidationException
     */
    public static function checkUsername($username, PantryUser $user = null) {
        if (empty($username)) {
            throw new PantryUsernameNotProvidedException($username);
        }
        if (strlen($username) < 3) {
            throw new PantryUsernameTooShortException($username);
        }
        if (strlen($username) > 32) {
            throw new PantryUsernameTooLongException($username);
        }
        if (!preg_match('/^[a-z0-9-_]+$/', $username)) {
            throw new PantryUsernameInvalidException($username);
        }

        $user_id = self::lookupUsername($username);
        if ($user_id !== false && (!$user || $user_id !== $user->getID())) {
            throw new PantryUsernameNotAvailableException($username);
        }
    }

    /**
     * @param $username
     * @param $password
     * @throws PantryUserNotFoundException
     * @throws PantryUserValidationException
     */
    public static function checkPassword($username, $password) {
        if (!$username) {
            throw new PantryUsernameNotProvidedException($username);
        }
        if (!$password) {
            throw new PantryUserPasswordEmptyException($password);
        }

        $user_id = self::lookupUsername($username);
        if (!$user_id) {
            throw new PantryUserNotFoundException($username);
        }
        $user = new PantryUser($user_id);

        if (!password_verify($password, $user->password)) {
            throw new PantryUserPasswordIncorrectException();
        }
    }

    public static function checkLogin($username, $password, $two_factor_code = null, $two_factor_session_secret = null) {
        if (!$username || !$password) {
            return "fail";
        }

        $user_id = self::lookupUsername($username);
        if (!$user_id) {
            return "fail";
        }

        try {
            $user = new self($user_id);
        }
        catch (PantryUserNotFoundException $e) {
            return "fail";
        }

        if (password_verify($password, $user->password)) {
            if (isset($_COOKIE['pantry_two_factor_session'])) {
                $two_factor_session_valid = PantryTwoFactorSession::checkTwoFactorSession(
                    $_COOKIE['pantry_two_factor_session'],
                    $user->id,
                    $two_factor_session_secret
                );
            }
            else {
                $two_factor_session_valid = false;
            }

            if (!$two_factor_session_valid && $user->twoFactorRequired()) {
                if (!$two_factor_code) {
                    return "two_factor_required";
                }
                $login_two_factor_key = new PantryTwoFactorKey($user->id);
                if ($login_two_factor_key->verifyCode($two_factor_code) && PantryTwoFactorLogin::checkAndRecord($user->id, $two_factor_code)) {
                    if ($user->is_disabled) {
                        return "disabled";
                    }
                    return "success";
                }
                return "two_factor_incorrect";
            }
            if ($user->is_disabled) {
                return "disabled";
            }
            return "success";
        }
        return "fail";
    }

    public static function listUsers($search, $sort_by = "username") {
        $sort_map = [
            'username' => "username",
            'created' => "created, username",
            'login' => "last_login DESC, username",
            'admin' => "is_admin DESC, username",
            'disabled' => "is_disabled DESC, username"
        ];
        $sort_query = (array_key_exists($sort_by, $sort_map)) ? $sort_map[$sort_by] : "username";

        $sql_list_users = Pantry::$db->prepare("SELECT id, created, username, is_admin, is_disabled, last_login, first_name, last_name FROM users WHERE username LIKE :search OR first_name LIKE :search OR last_name LIKE :search ORDER BY {$sort_query}");
        $sql_list_users->bindValue(':search', "{$search}%", PDO::PARAM_STR);
        $sql_list_users->execute();

        $users = [];
        while ($user_row = $sql_list_users->fetch(PDO::FETCH_ASSOC)) {
            $users[] = [
                'id' => $user_row['id'],
                'created' => $user_row['created'],
                'username' => $user_row['username'],
                'is_admin' => boolval($user_row['is_admin']),
                'is_disabled' => boolval($user_row['is_disabled']),
                'last_login' => $user_row['last_login'],
                'first_name' => $user_row['first_name'],
                'last_name' => $user_row['last_name'],
                'is_self' => false
            ];
        }

        return $users;
    }

    public static function getUserCounts() {
        $count = [
            'total' => 0,
            'admins' => 0,
            'disabled' => 0
        ];

        $sql_get_users = Pantry::$db->prepare("SELECT id, is_admin, is_disabled FROM users");
        $sql_get_users->execute();
        while ($row = $sql_get_users->fetch(PDO::FETCH_ASSOC)) {
            $count['total']++;
            if (boolval($row['is_admin']))
                $count['admins']++;
            if (boolval($row['is_disabled']))
                $count['disabled']++;
        }

        return $count;
    }
}
