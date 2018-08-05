<?php

class PantryUser {
    protected $id;
    private $created;
    private $username;
    private $password;
    private $is_admin;
    private $is_disabled;

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

        if ($user_id) {
            $sql_get_user = Pantry::$db->prepare("SELECT id, created, username, password, is_admin, is_disabled FROM users WHERE id=:user_id");
            $sql_get_user->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sql_get_user->execute();

            if ($user_row = $sql_get_user->fetch(PDO::FETCH_ASSOC)) {
                $this->id = $user_row['id'];
                $this->created = $user_row['created'];
                $this->username = $user_row['username'];
                $this->password = $user_row['password'];
                $this->is_admin = boolval($user_row['is_admin']);
                $this->is_disabled = boolval($user_row['is_disabled']);
            }
            else {
                throw new PantryUserNotFoundException("User not found.");
            }
        }
    }

    public function initialize() {
        $this->id = Pantry::generateUUID();
        $this->created = date("Y-m-d H:i:s");
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

    public static function lookupUsername($username) {
        if (!$username) {
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

    private static function twoFactorRequired($username) {
        $user_id = self::lookupUsername($username);

        if (!$user_id) {
            return false;
        }

        $sql_lookup_two_factor_required = Pantry::$db->prepare("SELECT id FROM two_factor_keys WHERE user_id=:user_id");
        $sql_lookup_two_factor_required->bindValue(':user_id', $user_id, PDO::PARAM_STR);
        $sql_lookup_two_factor_required->execute();

        if ($lookup_two_factor_required_row = $sql_lookup_two_factor_required->fetch(PDO::FETCH_ASSOC)) {
            return true;
        }

        return false;
    }

    public static function checkLogin($username, $password, $two_factor_code = null, $two_factor_session_secret = null) {
        if (!$username || !$password) {
            return "fail";
        }

        $user_id = self::lookupUsername($username);
        if (!$user_id) {
            return "fail";
        }

        $sql_get_user = Pantry::$db->prepare("SELECT id, username, password, is_disabled FROM users WHERE id=:user_id");
        $sql_get_user->bindValue(':user_id', $user_id, PDO::PARAM_STR);
        $sql_get_user->execute();

        if ($user_row = $sql_get_user->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($password, $user_row['password'])) {
                if (isset($_COOKIE['pantry_two_factor_session'])) {
                    $two_factor_session_valid = PantryTwoFactorSession::checkTwoFactorSession(
                        $_COOKIE['pantry_two_factor_session'],
                        $user_row['id'],
                        $two_factor_session_secret
                    );
                }
                else {
                    $two_factor_session_valid = false;
                }


                if (!$two_factor_session_valid && self::twoFactorRequired($username)) {
                    if (!$two_factor_code) {
                        return "two_factor_required";
                    }
                    $login_two_factor_key = new PantryTwoFactorKey($user_id);
                    if ($login_two_factor_key->verifyCode($two_factor_code) && PantryTwoFactorLogin::checkAndRecord($user_id, $two_factor_code)) {
                        if ($user_row['is_disabled']) {
                            return "disabled";
                        }
                        return "success";
                    }
                    return "two_factor_incorrect";
                }
                if ($user_row['is_disabled']) {
                    return "disabled";
                }
                return "success";
            }
        }

        return "fail";
    }

    public static function listUsers() {
        $sql_list_users = Pantry::$db->prepare("SELECT id, created, username, is_admin, is_disabled FROM users ORDER BY username");
        $sql_list_users->execute();

        $users = [];
        while ($user_row = $sql_list_users->fetch(PDO::FETCH_ASSOC)) {
            $users[] = [
                'id' => $user_row['id'],
                'created' => $user_row['created'],
                'username' => $user_row['username'],
                'isAdmin' => boolval($user_row['is_admin']),
                'isDisabled' => boolval($user_row['is_disabled'])
            ];
        }

        return $users;
    }
}
