<?php

return [
    // base
    'COPYRIGHT' => "Copyright",

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
    'HOME_PAGE_TEXT' => "Home page",

    // API success
    'ME_SUCCESS' => "Here are your user details.",
    'LOGIN_SUCCESS' => "You are now logged in.",
    'LOGOUT_SUCCESS' => "You have been logged out.",

    // API error
    'INTERNAL_ERROR' => "Something went wrong on the server. Please contact your administrator or try again later.",
    'CSRF_FAILED' => "A CSRF token was not provided or did not match.",
    'NOT_LOGGED_IN' => "This resource requires authentication.",
    'NOT_LOGGED_OUT' => "This resource requires the user to be logged out.",
    'MISSING_USERNAME_PASSWORD' => "Username or password was not specified.",
    'BAD_USERNAME_PASSWORD' => "Username or password was incorrect.",
    'USER_DISABLED' => "This account is disabled.",
    'TWO_FACTOR_REQUIRED' => "A two-factor authentication token is required.",
    'TWO_FACTOR_INCORRECT' => "The two-factor authentication token is incorrect."
];
