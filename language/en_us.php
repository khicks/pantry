<?php

return [
    'LANGUAGE' => "en_us",

    // base
    'COPYRIGHT' => "Copyright",
    'CANCEL' => "Cancel",
    'SAVE' => "Save",
    'NONE' => "none",
    'NEW' => "New",
    'COURSE' => "Course",
    'COURSES' => "Courses",
    'CUISINE' => "Cuisine",
    'CUISINES' => "Cuisines",

    // navbar
    'HOME_MENU_BUTTON' => "Home",
    'LOGIN_BUTTON' => "Log in",
    'LOGOUT_BUTTON' => "Log out",
    'ADMIN_MENU_BUTTON' => "Admin",

    // login page
    'LOGIN_TITLE' => "Log in",
    'LOGIN_PAGE_USERNAME' => "Username",
    'LOGIN_PAGE_PASSWORD' => "Password",
    'LOGIN_PAGE_2FA_HEADER' => "Two-Factor Authentication",
    'LOGIN_PAGE_2FA_TEXT' => "Please enter the 6-digit verification code from your mobile device.",
    'LOGIN_PAGE_2FA_LABEL' => "Verification code",
    'LOGIN_PAGE_2FA_REMEMBER' => "Do not ask again on this device for 30 days",
    'LOGIN_PAGE_SUCCESS' => "Login success!",
    'LOGIN_PAGE_FAILED' => "Login failed:",
    'LOGIN_PAGE_ERROR_USERPASS_EMPTY' => "Please enter a username and password.",
    'LOGIN_PAGE_ERROR_VERIFICATION_EMPTY' => "Please enter a verification code.",
    'LOGIN_PAGE_ERROR_USERPASS_INCORRECT' => "Username or password incorrect.",
    'LOGIN_PAGE_ERROR_VERIFICATION_INCORRECT' => "Verification code incorrect.",

    // home page
    'HOME_PAGE_TITLE' => "Home",
    'FEATURED_RECIPES' => "Featured Recipes",
    'NEW_RECIPES' => "New Recipes",
    'NO_FEATURED_RECIPES' => "There are no featured recipes.",
    'NO_NEW_RECIPES' => "There are no recipes.",
    'HOME_SECTION_FAILED' => "Section failed to load.",

    // account page
    'MY_ACCOUNT_TITLE' => "My Account",
    'ACCOUNT_SAVED' => "Account settings saved.",
    'NAME' => "Name",
    'USERNAME' => "Username",
    'FIRSTNAME' => "First name",
    'LASTNAME' => "Last name",
    'PASSWORD' => "Password",
    'OLD_PASSWORD' => "Old password",
    'NEW_PASSWORD' => "New password",
    'CONFIRM_PASSWORD' => "Confirm password",
    'ACCOUNT_USERNAME_HELP' => "Only admins may change usernames in the admin panel.",
    'EDIT_USER_PASSWORD_BLANK_TIP' => "Leave these fields blank to keep current password.",
    'EDIT_USER_PASSWORD_LOGOUT_TIP' => "Changing your password will log you out of all other sessions.",
    'OLD_PASSWORD_BLANK' => "Please enter your old password.",
    'CONFIRM_PASSWORD_BLANK' => "Please type the password again.",
    'CONFIRM_PASSWORD_MISMATCH' => "Passwords must match.",

    // browse recipes page
    'NO_RECIPES' => "There are no recipes.",
    'BROWSE_LOAD_FAILED' => "Could not load recipes.",

    // create recipe page
    'CREATE_RECIPE_TITLE' => "Create Recipe",

    // view recipe page
    'VIEW_RECIPE_TITLE' => "View Recipe",
    'SERVINGS' => "Servings",
    'PREP_TIME' => "Prep time",
    'COOK_TIME' => "Cook time",
    'DAY' => "day",
    'DAYS' => "days",
    'DAYS_SHORT' => "d",
    'HOUR' => "hour",
    'HOURS' => "hours",
    'HOURS_SHORT' => "h",
    'MINUTE' => "minute",
    'MINUTES' => "minutes",
    'MINUTES_SHORT' => "m",
    'INGREDIENTS' => "Ingredients",
    'DIRECTIONS' => "Directions",
    'AUTHOR' => "Author",
    'SOURCE' => "Source",
    'EDIT' => "Edit",
    'DELETE' => "Delete",
    'RECIPE_NOT_FOUND_TITLE' => "Recipe not found.",
    'RECIPE_NOT_FOUND_DESC' => "The recipe does not exist or you do not have permission to view it.",

    // edit recipe page
    'EDIT_RECIPE_TITLE' => "Edit Recipe",
    'TITLE' => "Title",
    'SLUG' => "Slug",
    'RECIPE_SLUG_HELP' => "The URL path for the recipe.",
    'BLURB' => "Blurb",
    'DESCRIPTION' => "Description",
    'IMAGE' => "Image",
    'CHOOSE_FILE' => "Choose file...",
    'CLEAR' => "Clear",
    'NO_IMAGE' => "no image",
    'PERMISSIONS' => "Permissions",
    'VISIBILITY' => "Visibility",
    'PRIVATE' => "Private",
    'PRIVATE_DESC' => "Viewers must be logged in and explicitly granted access.",
    'INTERNAL' => "Internal",
    'INTERNAL_DESC' => "Recipe can be accessed by any logged in user.",
    'PUBLIC' => "Public",
    'PUBLIC_DESC' => "Anyone may view this recipe.",
    'DEFAULT_PERMISSION_LEVEL' => "Default permission level",
    'READ' => "Read",
    'READ_DESC' => "Logged in users may view this recipe.",
    'WRITE' => "Write",
    'WRITE_DESC' => "Any logged in user may edit this recipe.",
    'ADMIN' => "Admin",
    'ADMIN_DESC' => "All logged in users have admin access to this recipe.",
    'MARKDOWN_SUPPORTED' => "Markdown is supported in this field.",
    'RECIPE_TITLE_ERROR_NONE' => "Please enter a recipe title.",
    'RECIPE_TITLE_ERROR_SHORT' => "Recipe title is too short.",
    'RECIPE_TITLE_ERROR_LONG' => "Recipe title is too long.",
    'RECIPE_SLUG_ERROR_NONE' => "Please enter a recipe slug.",
    'RECIPE_SLUG_ERROR_SHORT' => "Recipe slug is too short.",
    'RECIPE_SLUG_ERROR_LONG' => "Recipe slug is too long.",
    'RECIPE_BLURB_ERROR_LONG' => "Recipe blurb is too long.",
    'EDIT_RECIPE_SERVINGS_ERROR_LOW' => "Number of servings is too low. The minimum is 0.",
    'EDIT_RECIPE_SERVINGS_ERROR_HIGH' => "Number of servings is too high. The maximum is 120.",
    'EDIT_RECIPE_PREP_TIME_ERROR_LOW' => "Value for Prep Time is too low. The minimum is 0.",
    'EDIT_RECIPE_PREP_TIME_ERROR_HIGH' => "Value for Prep Time is too high. The maximum is 43200.",
    'EDIT_RECIPE_COOK_TIME_ERROR_LOW' => "Value for Cook Time is too low. The minimum is 0.",
    'EDIT_RECIPE_COOK_TIME_ERROR_HIGH' => "Value for Cook Time is too high. The maximum is 43200.",
    'EDIT_RECIPE_NO_PERMISSION' => "Whoops! You don't have permission to edit this recipe.",
    'EDIT_RECIPE_LOAD_FAILED_1' => "Recipe failed to load.",
    'EDIT_RECIPE_LOAD_FAILED_2' => "You may not have permission to access it. Use the main menu to navigate or refresh the page to try again.",

    // delete recipe modal
    'DELETE_RECIPE_TITLE' => "Delete recipe",
    'DELETE_RECIPE_PROMPT' => "Are you sure you want to delete this recipe?",

    // API success
    'ME_SUCCESS' => "Here are your user details.",
    'LANGUAGE_SUCCESS' => "Here is your language config.",
    'LOGIN_SUCCESS' => "You are now logged in.",
    'LOGOUT_SUCCESS' => "You have been logged out.",
    'LIST_USERS_SUCCESS' => "Here is the list of users.",
    'GET_USER_SUCCESS' => "Here are the requested user details.",
    'CHECK_USERNAME_SUCCESS' => "Username check succeeded.",
    'CREATE_USER_SUCCESS' => "User was successfully created.",
    'SAVE_USER_SUCCESS' => "User was successfully saved.",
    'DELETE_USER_SUCCESS' => "User was successfully deleted.",
    'RECIPE_SUCCESS' => "Here is your recipe.",
    'LIST_COURSES_SUCCESS' => "Here are the courses.",
    'LIST_CUISINES_SUCCESS' => "Here are the cuisines.",
    'LIST_COURSES_CUISINES_SUCCESS' => "Here are the courses and cuisines.",

    // API error
    'INTERNAL_ERROR' => "Something went wrong on the server. Please contact your administrator or try again later.",
    'CSRF_FAILED' => "A CSRF token was not provided or did not match.",
    'NOT_LOGGED_IN' => "This resource requires authentication.",
    'NOT_LOGGED_OUT' => "This resource requires the user to be logged out.",
    'ACCESS_DENIED' => "Access denied.",
    'MISSING_USERNAME_PASSWORD' => "Username or password was not specified.",
    'BAD_USERNAME_PASSWORD' => "Username or password was incorrect.",
    'USER_DISABLED' => "This account is disabled.",
    'TWO_FACTOR_REQUIRED' => "A two-factor authentication token is required.",
    'TWO_FACTOR_INCORRECT' => "The two-factor authentication token is incorrect.",
    'NOT_ADMIN' => "You must be an administrator to perform this action.",
    'CREATE_USER_FAILED' => "The user could not be created.",
    'NO_USER_ID' => "You must specify a user ID.",
    'NO_USER_ID_USERNAME' => "You must specify a user ID or username.",
    'USER_NOT_FOUND' => "That user does not exist.",
    'DELETE_OWN_USER' => "Cannot delete your own user account.",

    'USER_USERNAME_NOT_PROVIDED' => "Please enter a username.",
    'USER_USERNAME_TOO_SHORT' => "The username is too short.",
    'USER_USERNAME_TOO_LONG' => "The username is too long.",
    'USER_USERNAME_INVALID' => "The username is invalid.",
    'USER_USERNAME_NOT_AVAILABLE' => "This username is not available.",
    'USER_PASSWORD_NOT_PROVIDED' => "Please enter a password.",
    'USER_PASSWORD_INCORRECT' => "The password is incorrect.",
    'USER_FIRST_NAME_TOO_LONG' => "The first name is too long.",
    'USER_LAST_NAME_TOO_LONG' => "The last name is too long.",

    'RECIPE_NOT_FOUND' => "Recipe could not be found or you do not have permission to access it.",
    'IMAGE_NOT_FOUND' => "Recipe does not have an image.",
    'IMAGE_EXTENSION_INVALID' => "Recipe does not have an image with that extension.",
    'NOT_A_NUMBER' => "This field must be a number.",
    'IMAGE_NOT_ALLOWED' => "The supplied image type is not allowed.",
    'IMAGE_UPLOAD_FAILED' => "The image could not be uploaded.",
    'COURSE_NOT_FOUND' => "The specified course type does not exist.",
    'CUISINE_NOT_FOUND' => "The specified cuisine type does not exist.",
    'RECIPE_TITLE_TOO_SHORT' => "Recipe title is too short.",
    'RECIPE_TITLE_TOO_LONG' => "Recipe title is too long.",
    'RECIPE_SLUG_TOO_SHORT' => "Recipe slug is too short.",
    'RECIPE_SLUG_TOO_LONG' => "Recipe slug is too long.",
    'RECIPE_SLUG_INVALID' => "Recipe slug is invalid.",
    'RECIPE_SLUG_NOT_AVAILABLE' => "This recipe slug is not available.",
    'RECIPE_BLURB_TOO_LONG' => "Recipe blurb is too long.",
    'RECIPE_SERVINGS_INVALID' => "Number of servings is invalid.",
    'RECIPE_SERVINGS_TOO_SMALL' => "Number of servings is too small.",
    'RECIPE_SERVINGS_TOO_BIG' => "Number of servings is too big.",
    'RECIPE_PREP_TIME_INVALID' => "The value for prep time is invalid.",
    'RECIPE_PREP_TIME_TOO_SMALL' => "The value for prep time is too small.",
    'RECIPE_PREP_TIME_TOO_BIG' => "The value for prep time is too big.",
    'RECIPE_COOK_TIME_INVALID' => "The value for cook time is invalid.",
    'RECIPE_COOK_TIME_TOO_SMALL' => "The value for cook time is too small.",
    'RECIPE_COOK_TIME_TOO_BIG' => "The value for cook time is too big.",
    'RECIPE_SOURCE_INVALID' => "Source URL is invalid. (Must start with \"http://\" or \"https://\".)",

    // Admin pages
    'ADMIN_BRAND' => "Admin",
    'ADMIN_BACK' => "Back to",
    'ADMIN_BREADCRUMBS_ADMIN' => "Admin",
    'ADMIN_DASHBOARD_BUTTON' => "Dashboard",
    'ADMIN_DASHBOARD_TITLE' => "Dashboard",
    'ADMIN_SORT_BY_FIELD_LABEL' => "Sort by...",
    'ADMIN_COURSES_AND_CUISINES' => "Courses and Cuisines",
    'ADMIN_CC_SEARCH_FIELD' => "Search by name or slug",
    'ADMIN_CC_DELETE_COURSE_TITLE' => "Delete course",
    'ADMIN_CC_DELETE_COURSE_PROMPT' => "Are you sure you want to delete this course?",
    'ADMIN_CC_DELETE_CUISINE_TITLE' => "Delete cuisine",
    'ADMIN_CC_DELETE_CUISINE_PROMPT' => "Are you sure you want to delete this cuisine?",
    'ADMIN_USERS_BUTTON' => "Users",
    'ADMIN_USERS_TITLE' => "User Accounts",
    'ADMIN_USERS_LASTLOGIN' => "Last login",
    'ADMIN_USERS_ADMIN' => "Admin",
    'ADMIN_USERS_ACTIVE' => "Active",
    'ADMIN_USERS_EDIT_BUTTON' => "Edit",
    'ADMIN_USERS_DELETE_BUTTON' => "Delete",
    'ADMIN_USERS_CREATE_BUTTON' => "New user",
    'ADMIN_USERS_SEARCH_FIELD' => "Search by name or username",
    'ADMIN_USERS_SORT_BY_FIELD_USERNAME' => "Username",
    'ADMIN_USERS_SORT_BY_FIELD_CREATED' => "Created",
    'ADMIN_USERS_SORT_BY_FIELD_LASTLOGIN' => "Last login",
    'ADMIN_USERS_SORT_BY_FIELD_ADMINS' => "Admins",
    'ADMIN_USERS_SORT_BY_FIELD_DISABLED' => "Disabled",
    'ADMIN_USERS_ACCOUNT' => "Account",
    'ADMIN_USERS_ATTRIBUTES' => "Attributes",
    'ADMIN_USERS_DISABLED' => "Disabled",
    'ADMIN_CREATE_USER_BREADCRUMBS' => "Create",
    'ADMIN_CREATE_USER_TITLE' => "Create User",
    'ADMIN_CREATE_USER_TITLE2' => "New User",
    'ADMIN_CREATE_USER_BUTTON' => "Create user",
    'ADMIN_USERS_USERNAME_AVAILABLE' => "Username is available.",
    'ADMIN_USERS_DELETE_TITLE' => "Delete user",
    'ADMIN_USERS_DELETE_PROMPT' => "Are you sure you want to delete this user?",
    'ADMIN_EDIT_USER_TITLE' => "Edit User",
    'ADMIN_EDIT_USER_DISABLE_2FA' => "Disable Two-Factor Authentication",
    'ADMIN_EDIT_USER_DISABLE_2FA_PROMPT' => "Are you sure you want to disable two-factor authentication for this user? Only the user can re-enable it for themselves.",
    'ADMIN_EDIT_USER_DISABLE_2FA_SHORT' => "Disable",
];
