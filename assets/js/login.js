$(function() {
    const webRoot = $('meta[name=web_root]').attr("content");
    const pageTracker = $('meta[name=page_tracker]').attr("content");

    const formSigninControls = $("#form-signin-controls");
    const usernameField = $("#username");
    const passwordField = $("#password");
    const verificationField = $("#verification");
    const rememberField = $("#remember");
    const loginButton = $("#login-button");
    const usernamePasswordFields = $("#username-password-fields");
    const twoFactorFields = $("#two-factor-fields");
    const allFields = $(".login-field");

    const loginSuccessAlert = $("#login-success-alert");
    const loginErrorAlert = $("#login-error-alert");
    const loginErrorDesc = {
        all: $(".login-error-desc"),
        userpassEmpty: $("#login-error-desc-userpass-empty"),
        verificationEmpty: $("#login-error-desc-verification-empty"),
        userpassIncorrect: $("#login-error-desc-userpass-incorrect"),
        verificationIncorrect: $("#login-error-desc-verification-incorrect"),
        disabled: $("#login-error-desc-disabled"),
        other: $("#login-error-desc-other")
    };

    function signinIcon() {
        return $("#form-signin-icon");
    }

    function toggleFields(disabled) {
        allFields.prop('disabled', disabled);
        loginButton.prop('disabled', disabled);
    }

    function clearFieldErrors() {
        allFields.removeClass('is-invalid');
    }

    function loginSuccess() {
        signinIcon().removeClass('fa-spinner fa-spin').addClass('fa-check');
        loginSuccessAlert.slideDown();
        formSigninControls.fadeOut(function() {
            setTimeout(function() {
                if (pageTracker.length > 0 && pageTracker.charAt(0) === "/") {
                    window.location.replace(pageTracker);
                }
                else {
                    window.location.replace(webRoot);
                }
            });
        });
    }

    function loginErrorShow(messageID) {
        loginErrorDesc[messageID].show();
        loginErrorAlert.slideDown();
        toggleFields(false);
        signinIcon().removeClass('fa-spinner fa-spin').addClass('fa-lock');
    }

    function loginStart() {
        let username = usernameField.val();
        let password = passwordField.val();
        let verification = verificationField.val();
        let remember = rememberField.is(':checked');

        if (username.length === 0 || password.length === 0) {
            if (username.length === 0) {
                usernameField.addClass('is-invalid');
            }
            if (password.length === 0) {
                passwordField.addClass('is-invalid');
            }

            if (username.length === 0) {
                usernameField.focus();
            }
            else {
                passwordField.focus();
            }

            twoFactorFields.hide();
            usernamePasswordFields.show();
            loginErrorDesc['all'].hide();
            loginErrorShow("userpassEmpty");
            return;
        }

        if (twoFactorFields.is(':visible') && verification.length === 0) {
            verificationField.addClass('is-invalid');
            loginErrorDesc['all'].hide();
            loginErrorShow("verificationEmpty");
            return;
        }

        toggleFields(true);
        signinIcon().removeClass('fa-lock').addClass('fa-spinner fa-spin');
        loginErrorAlert.slideUp(function() {
            loginErrorDesc['all'].hide();
            loginRequest(username, password, verification, remember);
        });
    }

    function loginRequest(username, password, verification, remember) {
        $.ajax({
            method: "GET",
            url: webRoot + "/api/v1/me",
            success: function(response) {
                let csrfToken = response.data.csrf_token;

                if (csrfToken.length !== 64) {
                    loginErrorShow("other");
                    console.log(data);
                    return;
                }

                $.ajax({
                    method: "POST",
                    url: webRoot + "/api/v1/login",
                    data: {
                        csrf_token: csrfToken,
                        username: username,
                        password: password,
                        verification: verification,
                        remember: remember,
                        two_factor_session_secret: localStorage.getItem('pantryTwoFactorSessionSecret-' + webRoot)
                    },
                    success: function(response) {
                        if (response.data.two_factor_session_secret) {
                            localStorage.setItem('pantryTwoFactorSessionSecret-' + webRoot, response.data.two_factor_session_secret);
                        }

                        loginSuccess();
                    },
                    error: function(data) {
                        toggleFields(false);
                        signinIcon().removeClass('fa-spinner fa-spin').addClass('fa-lock');

                        let responseCode = data.responseJSON.code;
                        if (responseCode === "BAD_USERNAME_PASSWORD") {
                            usernameField.addClass('is-invalid');
                            passwordField.addClass('is-invalid').focus().select();
                            loginErrorShow("userpassIncorrect");
                        }
                        else if (responseCode === "USER_DISABLED") {
                            loginErrorShow("disabled");
                        }
                        else if (responseCode === "TWO_FACTOR_REQUIRED") {
                            usernamePasswordFields.slideUp();
                            twoFactorFields.slideDown();
                            verificationField.focus();
                        }
                        else if (responseCode === "TWO_FACTOR_INCORRECT") {
                            verificationField.addClass('is-invalid').focus().select();
                            loginErrorShow("verificationIncorrect");
                        }
                        else {
                            loginErrorShow("other");
                            console.log(data);
                        }
                    }
                });
            },
            error: function(data) {
                loginErrorShow("other");
                console.log(data);
            }
        });
    }

    loginButton.on('click', function() {
        clearFieldErrors();
        loginStart();
    });

    allFields.on('keypress', function(e) {
        clearFieldErrors();
        if (e.keyCode === 13) {
            loginStart();
        }
    })
});
