<?php

class PantryException extends Exception {
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

class PantryUserNotFoundException extends PantryException {}

class PantrySessionsNotPurgedException extends PantryException {}

class PantrySessionNotDestroyedException extends PantryException {}

class PantrySessionNotCreatedException extends PantryException {}

class PantrySessionNotUpdatedException extends PantryException {}

class PantryTwoFactorKeyNotSecureException extends PantryException {}

class PantryTwoFactorKeyHasNullValuesException extends PantryException {}

class PantryTwoFactorKeyNotSavedException extends PantryException {}

class PantryTwoFactorLoginsNotPurgedException extends PantryException {}

class PantryTwoFactorLoginsNotCheckedException extends PantryException {}

class PantryTwoFactorLoginNotRecordedException extends PantryException {}

class PantryTwoFactorSessionsNotPurgedException extends PantryException {}

class PantryTwoFactorSessionSecretNotSecureException extends PantryException {}

class PantryTwoFactorSessionNotCreatedException extends PantryException {}

class PantryTwoFactorSessionNotDestroyedException extends PantryException {}

class PantryTwoFactorSessionsNotLimitedException extends PantryException {}

class PantryGetRandomDataParamNotIntException extends PantryException {}

class PantryGetRandomDataParamNotSecureException extends PantryException {}

class PantryUserValidationException extends PantryException {
    private $field;
    private $bad_value;

    public function __construct($field, $bad_value, $code = 0, Exception $previous = null) {
        $this->field = $field;
        $this->bad_value = $bad_value;
        $message = "Unexpected value for field $field: $bad_value";
        parent::__construct($message, $code, $previous);
    }

    public function getField() {
        return $this->field;
    }

    public function getBadValue() {
        return $this->bad_value;
    }
}

class PantryUsernameValidationException extends PantryUserValidationException {
    public function __construct($bad_value, $code = 0, Exception $previous = null) {
        parent::__construct("username", $bad_value, $code, $previous);
    }
}

class PantryUsernameNotProvidedException extends PantryUsernameValidationException {}

class PantryUsernameTooShortException extends PantryUsernameValidationException {}

class PantryUsernameTooLongException extends PantryUsernameValidationException {}

class PantryUsernameInvalidException extends PantryUsernameValidationException {}

class PantryUsernameNotAvailableException extends PantryUsernameValidationException {}

class PantryUserPasswordValidationException extends PantryUserValidationException {
    public function __construct($field, $code = 0, Exception $previous = null) {
        parent::__construct($field, null, $code, $previous);
    }
}

class PantryUserPasswordEmptyException extends PantryUserPasswordValidationException {
    public function __construct($field = "password1", $code = 0, Exception $previous = null) {
        parent::__construct($field, $code, $previous);
    }
}

class PantryUserPasswordIncorrectException extends PantryUserPasswordValidationException {
    public function __construct($field = "old_password", $code = 0, Exception $previous = null) {
        parent::__construct($field, $code, $previous);
    }
}

class PantryUserFirstNameValidationException extends PantryUserValidationException {
    public function __construct($bad_value, $code = 0, Exception $previous = null) {
        parent::__construct("first_name", $bad_value, $code, $previous);
    }
}

class PantryUserFirstNameTooLongException extends PantryUserFirstNameValidationException {}

class PantryUserLastNameValidationException extends PantryUserValidationException {
    public function __construct($bad_value, $code = 0, Exception $previous = null) {
        parent::__construct("last_name", $bad_value, $code, $previous);
    }
}

class PantryUserLastNameTooLongException extends PantryUserLastNameValidationException {}

class PantryUserNotSavedException extends PantryException {}

class PantryUserNotDeletedException extends PantryException {}

class PantryRecipeNotFoundException extends PantryException {}

class PantryRecipeNotSavedException extends PantryException {}

class PantryRecipeNotDeletedException extends PantryException {}

class PantryCourseNotFoundException extends PantryException {}

class PantryCuisineNotFoundException extends PantryException {}

class PantryRecipeValidationException extends PantryException {
    private $field;
    private $bad_value;

    public function __construct($field, $bad_value, $code = 0, Exception $previous = null) {
        $this->field = $field;
        $this->bad_value = $bad_value;
        $message = "Unexpected value for field $field: $bad_value";
        parent::__construct($message, $code, $previous);
    }

    public function getField() {
        return $this->field;
    }

    public function getBadValue() {
        return $this->bad_value;
    }
}

class PantryRecipeTitleValidationException extends PantryRecipeValidationException {
    public function __construct($bad_value, $code = 0, Exception $previous = null) {
        parent::__construct("title", $bad_value, $code, $previous);
    }
}

class PantryRecipeTitleTooShortException extends PantryRecipeTitleValidationException {}

class PantryRecipeTitleTooLongException extends PantryRecipeTitleValidationException {}

class PantryRecipeSlugValidationException extends PantryRecipeValidationException {
    public function __construct($bad_value, $code = 0, Exception $previous = null) {
        parent::__construct("slug", $bad_value, $code, $previous);
    }
}

class PantryRecipeSlugTooShortException extends PantryRecipeSlugValidationException {}

class PantryRecipeSlugTooLongException extends PantryRecipeSlugValidationException {}

class PantryRecipeSlugInvalidException extends PantryRecipeSlugValidationException {}

class PantryRecipeSlugNotAvailableException extends PantryRecipeSlugValidationException {}

class PantryRecipeBlurbValidationException extends PantryRecipeValidationException {
    public function __construct($bad_value, $code = 0, Exception $previous = null) {
        parent::__construct("blurb", $bad_value, $code, $previous);
    }
}

class PantryRecipeBlurbTooLongException extends PantryRecipeBlurbValidationException {}

class PantryRecipeServingsValidationException extends PantryRecipeValidationException {
    public function __construct($bad_value, $code = 0, Exception $previous = null) {
        parent::__construct("servings", $bad_value, $code, $previous);
    }
}

class PantryRecipeServingsInvalidException extends PantryRecipeServingsValidationException {}

class PantryRecipeServingsTooSmallException extends PantryRecipeServingsValidationException {}

class PantryRecipeServingsTooBigException extends PantryRecipeServingsValidationException {}

class PantryRecipePrepTimeValidationException extends PantryRecipeValidationException {
    public function __construct($bad_value, $code = 0, Exception $previous = null) {
        parent::__construct("prep_time", $bad_value, $code, $previous);
    }
}

class PantryRecipePrepTimeInvalidException extends PantryRecipePrepTimeValidationException {}

class PantryRecipePrepTimeTooSmallException extends PantryRecipePrepTimeValidationException {}

class PantryRecipePrepTimeTooBigException extends PantryRecipePrepTimeValidationException {}

class PantryRecipeCookTimeValidationException extends PantryRecipeValidationException {
    public function __construct($bad_value, $code = 0, Exception $previous = null) {
        parent::__construct("cook_time", $bad_value, $code, $previous);
    }
}

class PantryRecipeCookTimeInvalidException extends PantryRecipeCookTimeValidationException {}

class PantryRecipeCookTimeTooSmallException extends PantryRecipeCookTimeValidationException {}

class PantryRecipeCookTimeTooBigException extends PantryRecipeCookTimeValidationException {}

class PantryImageNotFoundException extends PantryException {}

class PantryImageTypeNotAllowedException extends PantryException {}

class PantryFileNotFoundException extends PantryException {}

class PantryRecipePermissionNotFoundException extends PantryException {}

class PantryRecipePermissionDeniedException extends PantryException {}
