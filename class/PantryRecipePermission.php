<?php

class  PantryRecipePermission {
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
        $this->level = 0;
        $this->saved = false;

        if ($recipe_permission_id) {
            $sql_get_permission = Pantry::$db->prepare("SELECT id, recipe_id, user_id, level FROM recipes_permissions WHERE id=:id");
            $sql_get_permission->bindValue(':id', $recipe_permission_id, PDO::PARAM_STR);
            $sql_get_permission->execute();

            if ($permission_row = $sql_get_permission->fetch(PDO::FETCH_ASSOC)) {
                $this->id = $permission_row['id'];
                $this->level = (int)$permission_row['level'];
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

        try {
            $permission = new self(null);
            $permission->recipe = $recipe;
            $permission->user = $user;
            return $permission;
        }
        catch (PantryRecipePermissionNotFoundException $e) {
            Pantry::$logger->critical("Recipe permission not found in constructBySubjectAndObject 2.");
            die();
        }
    }

    private function setNull() {
        $this->id = null;
        $this->level = null;
        $this->saved = null;
        $this->recipe = null;
        $this->user = null;
    }

    public function getLevel() {
        // site admins and recipe authors have recipe admin
        if ($this->user && ($this->user->getIsAdmin() || $this->user->getID() === $this->recipe->getAuthor()->getID())) {
            return 3;
        }

        $lower_bound = ($this->recipe->getIsPublic()) ? 1 : 0;
        $upper_bound = min($this->level, 3);
        return max($lower_bound, $upper_bound);
    }
}
