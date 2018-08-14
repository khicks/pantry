<?php

class PantryTwoFactorKey {
    private $id;
    private $created;
    private $user_id;
    private $key;

    public function __construct($user_id = null) {
        $this->id = null;
        $this->created = null;
        $this->user_id = null;
        $this->key = null;

        if ($user_id) {
            $sql_get_two_factor_key = Pantry::$db->prepare("SELECT id, created, user_id, two_factor_key FROM two_factor_keys WHERE user_id=:user_id");
            $sql_get_two_factor_key->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sql_get_two_factor_key->execute();

            if ($two_factor_key_row = $sql_get_two_factor_key->fetch(PDO::FETCH_ASSOC)) {
                $this->id = $two_factor_key_row['id'];
                $this->created = $two_factor_key_row['created'];
                $this->user_id = $two_factor_key_row['user_id'];
                $this->key = $two_factor_key_row['two_factor_key'];
            }
        }
    }

    public function initialize($user_id, $key = null) {
        try {
            $this->id = Pantry::generateUUID();
            $this->created = date("Y-m-d H:i:s");
            $this->user_id = $user_id;
            $this->key = ($key) ? $key : $this->generateKey();
        }
        catch (PantryTwoFactorKeyNotSecureException $e) {
            Pantry::$logger->emergency($e->getMessage());
            die();
        }
    }

    /**
     * @return bool
     */
    public function save() {
        if (in_array(null, [$this->id, $this->created, $this->user_id, $this->key], true)) {
            try {
                throw new PantryTwoFactorKeyHasNullValuesException("A two factor key with null values cannot be saved.");
            }
            catch (PantryTwoFactorKeyHasNullValuesException $e) {
                Pantry::$logger->emergency($e->getMessage());
                die();
            }
        }

        $sql_save_key = Pantry::$db->prepare("REPLACE INTO two_factor_keys (id, created, user_id, two_factor_key) VALUES (:key_id, :created, :user_id, :two_factor_key)");
        $sql_save_key->bindValue(':key_id', $this->id, PDO::PARAM_STR);
        $sql_save_key->bindValue(':created', $this->created, PDO::PARAM_STR);
        $sql_save_key->bindValue(':user_id', $this->user_id, PDO::PARAM_STR);
        $sql_save_key->bindValue(':two_factor_key', $this->key, PDO::PARAM_STR);

        if (!$sql_save_key->execute()) {
            try {
                throw new PantryTwoFactorKeyNotSavedException("The SQL query to save the two factor key failed: ".$sql_save_key->errorInfo()[2]);
            }
            catch (PantryTwoFactorKeyNotSavedException $e) {
                Pantry::$logger->emergency($e->getMessage());
                die();
            }
        }

        return true;
    }

    public function destroy() {
        $sql_delete_key = Pantry::$db->prepare("DELETE FROM two_factor_keys WHERE id=:key_id");
        $sql_delete_key->bindValue(':key_id', $this->id, PDO::PARAM_STR);
        $sql_delete_key->execute();

        $this->id = null;
        $this->created = null;
        $this->user_id = null;
        $this->key = null;

        return true;
    }

    public function getCode($time = null) {
        if (empty($this->key)) {
            return null;
        }
        if (empty($time)) {
            $time = floor(time() / 30);
        }

        $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $time);
        $binary_key = $this->getBinaryKey();
        $hmac = hash_hmac('SHA1', $time, $binary_key, true);
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $hashpart = substr($hmac, $offset, 4);

        $value = unpack('N', $hashpart);
        $value = $value[1];
        $value = $value & 0x7FFFFFFF;

        $modulo = pow(10, 6);
        return str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);
    }

    public function verifyCode($code, $discrepancy=1, $time=null) {
        if (empty($time)) {
            $time = floor(time() / 30);
        }

        for ($i=-$discrepancy; $i<=$discrepancy; $i++) {
            $expected_code = $this->getCode($time+$i);
            if (trim($code) === $expected_code) {
                return true;
            }
        }

        return false;
    }

    private function getBinaryKey() {
        $base32_chars = $this->getBase32Chars();
        $base32_chars_flipped = array_flip($base32_chars);

        $key = str_split($this->key);
        $binary_string = "";
        for ($i = 0; $i < count($key); $i = $i+8) {
            $x = "";
            if (!in_array($key[$i], $base32_chars)) return false;
            for ($j = 0; $j < 8; $j++) {
                $x .= str_pad(base_convert(@$base32_chars_flipped[@$key[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
            $eight_bits = str_split($x, 8);
            for ($z = 0; $z < count($eight_bits); $z++) {
                $binary_string .= ( ($y = chr(base_convert($eight_bits[$z], 2, 10))) || ord($y) == 48 ) ? $y:"";
            }
        }
        return $binary_string;
    }

    /**
     * @return string
     * @throws PantryTwoFactorKeyNotSecureException
     */
    private function generateKey() {
        $map = $this->getBase32Chars();

        $random_is_secure = false;
        $rand = openssl_random_pseudo_bytes(40, $random_is_secure);

        if (!$random_is_secure) {
            throw new PantryTwoFactorKeyNotSecureException("Two factor key is not cryptographically secure.");
        }

        $output = '';
        for ($i=0; $i<40; $i+=5) {
            $byte[0] = ord($rand[$i]);
            $byte[1] = ord($rand[$i+1]);
            $byte[2] = ord($rand[$i+2]);
            $byte[3] = ord($rand[$i+3]);
            $byte[4] = ord($rand[$i+4]);

            $output .= $map[$byte[0] >> 3];
            $output .= $map[($byte[0] & ~(31 << 3)) << 2 | $byte[1] >> 6];
            $output .= $map[$byte[1] >> 1 & ~(3 << 5)];
            $output .= $map[($byte[1] & 1) << 4 | $byte[2] >> 4];
            $output .= $map[($byte[2] & ~(15 << 4)) << 1 | $byte[3] >> 7];
            $output .= $map[$byte[3] >> 2 & ~(1 << 5)];
            $output .= $map[($byte[3] & ~(63 << 2)) << 3 | $byte[4] >> 5];
            $output .= $map[$byte[4] & ~(7 << 5)];
        }

        return $output;
    }

    private function getBase32Chars() {
        return ['A','B','C','D','E','F','G','H',
            'I','J','K','L','M','N','O','P',
            'Q','R','S','T','U','V','W','X',
            'Y','Z','2','3','4','5','6','7'];
    }

    public static function purgeUser($user_id) {
        $sql_purge_user = Pantry::$db->prepare("DELETE FROM two_factor_keys WHERE user_id=:user_id");
        $sql_purge_user->bindValue(':user_id', $user_id, PDO::PARAM_STR);
        $sql_purge_user->execute();
    }
}
