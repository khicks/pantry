<?php

class  PantryRecipePermission {
    public static $permission_level_map = [
        'NONE' => 0,
        'READ' => 1,
        'WRITE' => 2,
        'ADMIN' => 3
    ];

    public static $visibility_level_map = [
        'PRIVATE' => 0,
        'INTERNAL' => 1,
        'PUBLIC' => 2
    ];

    private $id;
    private $level;
    private $saved;

    /** @var PantryRecipe $recipe */
    private $recipe;

    /** @var PantryUser $user */
    private $user;

    /**
     * PantryRecipePermission constructor.
     * @param $recipe_permission_id
     * @throws PantryRecipePermissionNotFoundException
     */
    public function __construct($recipe_permission_id = null) {
        $this->setNull();
        $this->level = self::$permission_level_map['NONE'];
        $this->saved = false;

        if ($recipe_permission_id) {
            $sql_get_permission = Pantry::$db->prepare("SELECT id, recipe_id, user_id, level FROM recipes_permissions WHERE id=:id");
            $sql_get_permission->bindValue(':id', $recipe_permission_id, PDO::PARAM_STR);
            $sql_get_permission->execute();

            if ($permission_row = $sql_get_permission->fetch(PDO::FETCH_ASSOC)) {
                $this->id = $permission_row['id'];
                $this->level = self::boundPermissionLevel((int)$permission_row['level']);
                $this->saved = true;

                try {
                    $this->recipe = new PantryRecipe($permission_row['recipe_id']);
                    $this->user = new PantryUser($permission_row['user_id']);
                }
                catch (PantryRecipeNotFoundException | PantryUserNotFoundException $e) {}
            }
            else {
                throw new PantryRecipePermissionNotFoundException("Recipe permission not found.");
            }
        }
    }

    /**
     * @param PantryRecipe $recipe
     * @param PantryUser $user
     * @return PantryRecipePermission
     */
    public static function constructBySubjectAndObject($recipe, $user) {
        if ($recipe && $user) {
            $sql_get_permission_id = Pantry::$db->prepare("SELECT id FROM recipes_permissions WHERE recipe_id=:recipe_id AND user_id=:user_id");
            $sql_get_permission_id->bindValue(':recipe_id', $recipe->getID(), PDO::PARAM_STR);
            $sql_get_permission_id->bindValue(':user_id', $user->getID(), PDO::PARAM_STR);
            $sql_get_permission_id->execute();

            if ($permission_id_row = $sql_get_permission_id->fetch(PDO::FETCH_ASSOC)) {
                try {
                    return new self($permission_id_row['id']);
                }
                catch (PantryRecipePermissionNotFoundException $e) {
                    Pantry::$logger->critical("Recipe permission not found in constructBySubjectAndObject 1.");
                    die();
                }
            }
        }

        $permission = new self(null);
        $permission->recipe = $recipe;
        $permission->user = $user;
        return $permission;
    }

    private function setNull() {
        $this->id = null;
        $this->level = null;
        $this->saved = null;
        $this->recipe = null;
        $this->user = null;
    }

    public function getLevel() {
        return self::boundPermissionLevel($this->level);
    }

    private static function boundPermissionLevel(int $level) {
        return max(self::$permission_level_map['NONE'], min($level, self::$permission_level_map['ADMIN']));
    }

    public static function getEffectivePermissionLevel(PantryRecipe $recipe, PantryUser $user = null) {
        // logged in
        if ($user && $user->getID()) {
            // site admins get recipe admin
            if ($user->getIsAdmin()) {
                return self::$permission_level_map['ADMIN'];
            }

            // recipe author gets admin
            if ($user->getID() === $recipe->getAuthorID()) {
                return self::$permission_level_map['ADMIN'];
            }

            // get recipe-user specific permission
            $sql_get_permission = Pantry::$db->prepare("SELECT level FROM recipes_permissions WHERE recipe_id=:recipe_id AND user_id=:user_id");
            $sql_get_permission->bindValue(':recipe_id', $recipe->getID(), PDO::PARAM_STR);
            $sql_get_permission->bindValue(':user_id', $user->getID(), PDO::PARAM_STR);
            $sql_get_permission->execute();
            if ($row = $sql_get_permission->fetch(PDO::FETCH_ASSOC)) {
                return self::boundPermissionLevel($row['level']);
            }

            // recipe default permission level
            return self::boundPermissionLevel($recipe->getDefaultPermissionLevel());
        }

        // logged out, public
        if ($recipe->getVisibilityLevel() === self::$visibility_level_map['PUBLIC']) {
            return self::$permission_level_map['READ'];
        }

        // denied
        return self::$permission_level_map['NONE'];
    }
}
