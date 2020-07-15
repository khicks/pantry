<?php

class PantryException extends Exception {
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

class PantryDBMetadataException extends PantryException {}

class PantryDBMetadataNameEmptyException extends PantryDBMetadataException {}

class PantryDBMetadataNameNotFoundException extends PantryDBMetadataException {}

class PantryConfigurationException extends PantryException {
    public function __construct($field, $bad_value, $message = null, $code = 0, Exception $previous = null) {
        $message = $message ?? "Bad value for $field in configuration: $bad_value";
        parent::__construct($message, $code, $previous);
    }
}

class PantryInstallationException extends PantryException {
    private $response_code;

    public function __construct($error_code, $response_code, $code = 0, Exception $previous = null) {
        $this->response_code = $response_code;
        parent::__construct($error_code, $code, $previous);
    }

    public function getResponseCode() {
        return $this->response_code;
    }
}

class PantryInstallationClientException extends PantryInstallationException {
    public function __construct($message, $response_code = 422, $code = 0, Exception $previous = null) {
        parent::__construct($message, $response_code, $code, $previous);
    }
}

class PantryInstallationServerException extends PantryInstallationException {
    public function __construct($message, $response_code = 500, $code = 0, Exception $previous = null) {
        parent::__construct($message, $response_code, $code, $previous);
    }
}

class PantryLanguageNotFoundException extends PantryException {}

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

class PantryValidationException extends PantryException {
    private $type;
    private $field;
    private $bad_value;

    public function __construct($type, $field, $bad_value, $code = 0, Exception $previous = null) {
        $this->type = $type;
        $this->field = $field;
        $this->bad_value = $bad_value;
        $message = "Unexpected value on $type for field $field: $bad_value";
        parent::__construct($message, $code, $previous);
    }

    public function getType() {
        return $this->type;
    }

    public function getField() {
        return $this->field;
    }

    public function getBadValue() {
        return $this->bad_value;
    }
}

class PantryUserValidationException extends PantryValidationException {
    public function __construct($field, $bad_value, $code = 0, Exception $previous = null) {
        parent::__construct("PantryUser", $field, $bad_value, $code, $previous);
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

//========================================
// PantryCourse
//========================================
class PantryCourseNotFoundException extends PantryException {}

class PantryCourseNotSavedException extends PantryException {}

class PantryCourseNotDeletedException extends PantryException {}

class PantryCourseDeleteReplacementIsSameException extends PantryException {}

class PantryCourseValidationException extends PantryValidationException {
    public function __construct($field, $bad_value, $code = 0, Exception $previous = null) {
        parent::__construct("PantryCourse", $field, $bad_value, $code, $previous);
    }
}

class PantryCourseTitleValidationException extends PantryCourseValidationException {
    public function __construct($bad_value, $code = 0, Exception $previous = null) {
        parent::__construct("title", $bad_value, $code, $previous);
    }
}

class PantryCourseTitleNoneException extends PantryCourseTitleValidationException {}

class PantryCourseTitleTooLongException extends PantryCourseTitleValidationException {}

class PantryCourseSlugValidationException extends PantryCourseValidationException {
    public function __construct($bad_value, $code = 0, Exception $previous = null) {
        parent::__construct("slug", $bad_value, $code, $previous);
    }
}

class PantryCourseSlugNoneException extends PantryCourseSlugValidationException {}

class PantryCourseSlugTooShortException extends PantryCourseSlugValidationException {}

class PantryCourseSlugTooLongException extends PantryCourseSlugValidationException {}

class PantryCourseSlugInvalidException extends PantryCourseSlugValidationException {}

class PantryCourseSlugNotAvailableException extends PantryCourseSlugValidationException {}

//========================================
// PantryCuisine
//========================================
class PantryCuisineNotFoundException extends PantryException {}

class PantryCuisineNotSavedException extends PantryException {}

class PantryCuisineNotDeletedException extends PantryException {}

class PantryCuisineDeleteReplacementIsSameException extends PantryException {}

class PantryCuisineValidationException extends PantryValidationException {
    public function __construct($field, $bad_value, $code = 0, Exception $previous = null) {
        parent::__construct("PantryCuisine", $field, $bad_value, $code, $previous);
    }
}

class PantryCuisineTitleValidationException extends PantryCuisineValidationException {
    public function __construct($bad_value, $code = 0, Exception $previous = null) {
        parent::__construct("title", $bad_value, $code, $previous);
    }
}

class PantryCuisineTitleNoneException extends PantryCuisineTitleValidationException {}

class PantryCuisineTitleTooLongException extends PantryCuisineTitleValidationException {}

class PantryCuisineSlugValidationException extends PantryCuisineValidationException {
    public function __construct($bad_value, $code = 0, Exception $previous = null) {
        parent::__construct("slug", $bad_value, $code, $previous);
    }
}

class PantryCuisineSlugNoneException extends PantryCuisineSlugValidationException {}

class PantryCuisineSlugTooShortException extends PantryCuisineSlugValidationException {}

class PantryCuisineSlugTooLongException extends PantryCuisineSlugValidationException {}

class PantryCuisineSlugInvalidException extends PantryCuisineSlugValidationException {}

class PantryCuisineSlugNotAvailableException extends PantryCuisineSlugValidationException {}

//========================================
// PantryRecipe
//========================================
class PantryRecipeValidationException extends PantryValidationException {
    public function __construct($field, $bad_value, $code = 0, Exception $previous = null) {
        parent::__construct("PantryRecipe", $field, $bad_value, $code, $previous);
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

class PantryRecipeSourceValidationException extends PantryRecipeValidationException {
    public function __construct($bad_value, $code = 0, Exception $previous = null) {
        parent::__construct("source", $bad_value, $code, $previous);
    }
}

class PantryRecipeSourceInvalidException extends PantryRecipeSourceValidationException {}

class PantryImageNotFoundException extends PantryException {}

class PantryImageFileSizeTooBigException extends PantryException {}

class PantryImageTypeNotAllowedException extends PantryException {}

class PantryFileNotFoundException extends PantryException {}

class PantryRecipePermissionNotFoundException extends PantryException {}

class PantryRecipePermissionDeniedException extends PantryException {}
