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

class PantryUserNotSavedException extends PantryException {}

class PantryUserNotDeletedException extends PantryException {}

class PantryRecipeNotFoundException extends PantryException {}

class PantryCourseNotFoundException extends PantryException {}

class PantryCuisineNotFoundException extends PantryException {}

class PantryImageNotFoundException extends PantryException {}
