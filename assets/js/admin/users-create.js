$(function() {
    const webRoot = $('meta[name=web_root]').attr("content");
    const csrfToken = $('meta[name=csrf_token]').attr("content");

    const elements = {
        username: {
            field: $("#username"),
            icon: function() { return $("#username-feedback-icon") },
            messages: {
                all: $(".username-feedback-message"),
                none: $("#username-feedback-message-none"),
                short: $("#username-feedback-message-short"),
                long: $("#username-feedback-message-long"),
                api: $("#username-feedback-message-api")
            }
        },
        first_name: {
            field: $("#first-name"),
            icon: function() { return $("#firstname-feedback-icon") },
            messages: {
                all: $(".firstname-feedback-message"),
                api: $("#firstname-feedback-message-api")
            }
        },
        last_name: {
            field: $("#last-name"),
            icon: function() { return $("#lastname-feedback-icon") },
            messages: {
                all: $(".lastname-feedback-message"),
                api: $("#lastname-feedback-message-api")
            }
        },
        password1: {
            field: $("#password1"),
            icon: function() { return $("#password1-feedback-icon") },
            messages: {
                all: $(".password1-feedback-message"),
                none: $("#password1-feedback-message-none"),
                api: $("#password1-feedback-message-api")
            }
        },
        password2: {
            field: $("#password2"),
            icon: function() { return $("#password2-feedback-icon") },
            messages: {
                all: $(".password2-feedback-message"),
                none: $("#password2-feedback-message-none"),
                mismatch: $("#password2-feedback-message-mismatch"),
                api: $("#password2-feedback-message-api")
            }
        },
        admin: {
            field: $("#admin")
        },
        disabled: {
            field: $("#disabled")
        },
        create: {
            button: $("#create-user-button"),
            icon: function() { return $("#create-user-button-icon") },
            text: $("#create-user-button-text")
        },
        cancel: {
            button: $("#cancel-button")
        }
    };

    function clearFieldErrors(field) {
        elements[field].icon().hide().removeClass('fa-times fa-check field-feedback-red field-feedback-green');
        elements[field].field.removeClass('is-invalid is-valid');
        elements[field].messages.all.hide();
        elements[field].messages.api.text("");
    }

    function clearAllFieldErrors() {
        $.each(['username', 'first_name', 'last_name', 'password1', 'password2'], function(index, value) {
            clearFieldErrors(value);
        })
    }

    function checkUsername() {
        clearFieldErrors('username');
        let username = elements.username.field.val();
        if (username.length > 0) {
            if (username.length < 3) {
                elements.username.icon().addClass('fa-times field-feedback-red').show();
                elements.username.messages.short.show();
                elements.username.field.addClass('is-invalid');
                return;
            }
            if (username.length > 32) {
                elements.username.icon().addClass('fa-times field-feedback-red').show();
                elements.username.messages.long.show();
                elements.username.field.addClass('is-invalid');
                return;
            }

            $.ajax({
                method: "GET",
                url: webRoot + "/api/v1/admin/users/check-username",
                data: {
                    csrf_token: csrfToken,
                    username: username
                },
                /**
                 * @param {boolean} response.data.available
                 * @param {string} response.data.message
                 */
                success: function(response) {
                    elements.username.messages.api.text(response.data.message).show();

                    if (response.data.available) {
                        elements.username.icon().addClass('fa-check field-feedback-green').show();
                        elements.username.field.addClass('is-valid');
                    }
                    else {
                        elements.username.icon().addClass('fa-times field-feedback-red').show();
                        elements.username.field.addClass('is-invalid');
                    }
                },
                error: function(data) {
                    console.log(data);
                }
            });
        }
    }

    function checkFields() {
        let errors = 0;
        let username = elements.username.field.val();
        let password1 = elements.password1.field.val();
        let password2 = elements.password2.field.val();

        if (username.length === 0) {
            elements.username.messages.none.show();
            elements.username.icon().addClass('fa-times field-feedback-red').show();
            elements.username.field.addClass('is-invalid');
            errors++;
        }

        if (password1.length === 0) {
            elements.password1.messages.none.show();
            elements.password1.icon().addClass('fa-times field-feedback-red').show();
            elements.password1.field.addClass('is-invalid');
            errors++;
        }

        if (password2.length === 0) {
            elements.password2.messages.none.show();
            elements.password2.icon().addClass('fa-times field-feedback-red').show();
            elements.password2.field.addClass('is-invalid');
            errors++;
        }

        if (password1.length > 0 && password2.length > 0 && password1 !== password2) {
            elements.password2.messages.mismatch.show();
            elements.password2.icon().addClass('fa-times field-feedback-red').show();
            elements.password2.field.addClass('is-invalid');
            errors++;
        }

        return errors === 0;
    }

    function toggleFields(disabled) {
        ['username', 'first_name', 'last_name', 'password1', 'password2', 'admin', 'disabled'].forEach(function(field) {
            elements[field].field.prop('disabled', disabled);
        });
        elements.create.button.prop('disabled', disabled);
        elements.cancel.button.prop('disabled', disabled);
    }

    function createUser() {
        clearAllFieldErrors();
        if (!checkFields()) {
            return;
        }

        toggleFields(true);
        elements.create.icon().show();

        $.ajax({
            method: "POST",
            url: webRoot + "/api/v1/admin/users/create",
            data: {
                csrf_token: csrfToken,
                username: elements.username.field.val(),
                first_name: elements.first_name.field.val(),
                last_name: elements.last_name.field.val(),
                password: elements.password1.field.val(),
                admin: elements.admin.field.is(':checked'),
                disabled: elements.disabled.field.is(':checked')
            },
            success: function() {
                window.location.replace(webRoot + '/admin/users');
            },
            error: function(data) {
                console.log(data);
                elements.create.icon().hide();
                toggleFields(false);

                let errors = data.responseJSON.data;
                $.each(errors, function(field, error) {
                    elements[field].icon().addClass('fa-times field-feedback-red').show();
                    elements[field].messages.api.text(error.message).show();
                    elements[field].field.addClass('is-invalid');
                    console.log(field);
                    console.log(error);
                });
            }
        });
    }

    elements.username.field
        .on('blur', checkUsername)
        .on('input propertychange', function() {
            clearFieldErrors('username');
        })
        .on('keydown', function(e) {
            if (e.keyCode === 8) {
                $(this).trigger('input');
            }
        });

    elements.first_name.field
        .on('input propertychange', function() {
            clearFieldErrors('first_name')
        })
        .on('keydown', function(e) {
            if (e.keyCode === 8) {
                $(this).trigger('input');
            }
        });

    elements.last_name.field
        .on('input propertychange', function() {
            clearFieldErrors('last_name')
        })
        .on('keydown', function(e) {
            if (e.keyCode === 8) {
                $(this).trigger('input');
            }
        });

    elements.password1.field
        .on('input propertychange', function() {
            clearFieldErrors('password1')
        })
        .on('keydown', function(e) {
            if (e.keyCode === 8) {
                $(this).trigger('input');
            }
        });

    elements.password2.field
        .on('input propertychange', function() {
            clearFieldErrors('password2')
        })
        .on('keydown', function(e) {
            if (e.keyCode === 8) {
                $(this).trigger('input');
            }
        });

    elements.create.button.on('click', createUser);

    elements.username.field.focus();
});
