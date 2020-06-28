$(function() {
    const webRoot = $('meta[name=web_root]').attr("content");
    const csrfToken = $('meta[name=csrf_token]').attr("content");

    const elements = {
        form: $("#account-form"),
        loading: function() { return $("#account-loading"); },
        alerts: {
            saved: $("#account-saved-alert")
        },
        fields: {
            groups: {
                all: $("input"),
                select: $(".select-field")
            },
            username: {
                field: $("#username"),
                helpIcon: function() { return $("#username-help-icon"); },
                helpText: $("#username-help-text")
            },
            first_name: {
                field: $("#first-name"),
                icon: function() { return $("#firstname-feedback-icon"); },
                messages: {
                    all: $(".firstname-feedback-message"),
                    api: $("#firstname-feedback-message-api")
                }
            },
            last_name: {
                field: $("#last-name"),
                icon: function() { return $("#lastname-feedback-icon"); },
                messages: {
                    all: $(".lastname-feedback-message"),
                    api: $("#lastname-feedback-message-api")
                }
            },
            old_password: {
                field: $("#old-password"),
                icon: function() { return $("#old-password-feedback-icon"); },
                messages: {
                    all: $(".old-password-feedback-message"),
                    none: $("#old-password-feedback-message-none"),
                    api: $("#old-password-feedback-message-api")
                }
            },
            password1: {
                field: $("#password1"),
                icon: function() { return $("#password1-feedback-icon"); },
                messages: {
                    all: $(".password1-feedback-message"),
                    none: $("#password1-feedback-message-none"),
                    api: $("#password1-feedback-message-api")
                }
            },
            password2: {
                field: $("#password2"),
                icon: function() { return $("#password2-feedback-icon"); },
                messages: {
                    all: $(".password2-feedback-message"),
                    none: $("#password2-feedback-message-none"),
                    mismatch: $("#password2-feedback-message-mismatch"),
                    api: $("#password2-feedback-message-api")
                }
            }
        },
        buttons: {
            save: {
                button: $("#save-button"),
                icon: function() { return $("#save-button-icon"); },
                text: $("#save-button-text")
            },
            cancel: {
                button: $("#cancel-button")
            }
        }
    };

    const clearFieldErrors = function(field) {
        elements.fields[field].icon().hide().removeClass('fa-times fa-check field-feedback-red field-feedback-green');
        elements.fields[field].field.removeClass('is-invalid is-valid');
        elements.fields[field].messages.all.hide();
        elements.fields[field].messages.api.text("");
    };

    const clearAllFieldErrors = function() {
        $.each(['first_name', 'last_name', 'old_password', 'password1', 'password2'], function(index, value) {
            clearFieldErrors(value);
        })
    };

    const onUserLoad = function(response) {
        elements.fields.username.field.val(response.data.username);
        elements.fields.first_name.field.val(response.data.first_name);
        elements.fields.last_name.field.val(response.data.last_name);

        elements.loading().hide();
        elements.form.show();

        elements.fields.username.helpIcon().tooltip({
            container: "body",
            placement: "top",
            title: elements.fields.username.helpText
        });
    };

    const loadForm = function() {
        $.ajax({
            method: "GET",
            url: webRoot + "/api/v1/me",
            data: {
                csrf_token: csrfToken
            },
            success: onUserLoad,
            error: function(data) {
                console.log(data);
                alert("User fetch failed.");
            }
        });
    };

    const checkFields = function() {
        let errors = 0;
        let oldPassword = elements.fields.old_password.field.val();
        let password1 = elements.fields.password1.field.val();
        let password2 = elements.fields.password2.field.val();

        if (oldPassword.length > 0 || password1.length > 0 || password2.length > 0) {
            if (oldPassword.length === 0) {
                elements.fields.old_password.messages.none.show();
                elements.fields.old_password.icon().addClass('fa-times field-feedback-red').show();
                elements.fields.old_password.field.addClass('is-invalid');
                errors++;
            }

            if (password1.length === 0) {
                elements.fields.password1.messages.none.show();
                elements.fields.password1.icon().addClass('fa-times field-feedback-red').show();
                elements.fields.password1.field.addClass('is-invalid');
                errors++;
            }

            if (password2.length === 0) {
                elements.fields.password2.messages.none.show();
                elements.fields.password2.icon().addClass('fa-times field-feedback-red').show();
                elements.fields.password2.field.addClass('is-invalid');
                errors++;
            }

            if (password1.length > 0 && password2.length > 0 && password1 !== password2) {
                elements.fields.password2.messages.mismatch.show();
                elements.fields.password2.icon().addClass('fa-times field-feedback-red').show();
                elements.fields.password2.field.addClass('is-invalid');
                errors++;
            }
        }

        return errors === 0;
    };

    function toggleFields(disabled) {
        ['first_name', 'last_name', 'old_password', 'password1', 'password2'].forEach(function(field) {
            elements.fields[field].field.prop('disabled', disabled);
        });

        elements.buttons.save.button.prop('disabled', disabled);
        elements.buttons.cancel.button.prop('disabled', disabled);
    }

    const save = function() {
        clearAllFieldErrors();
        elements.alerts.saved.hide();
        if (!checkFields()) {
            return;
        }

        toggleFields(true);
        elements.buttons.save.icon().show();

        $.ajax({
            method: "POST",
            url: webRoot + "/api/v1/account",
            data: {
                csrf_token: csrfToken,
                first_name: elements.fields.first_name.field.val(),
                last_name: elements.fields.last_name.field.val(),
                old_password: elements.fields.old_password.field.val(),
                password: elements.fields.password1.field.val()
            },
            success: function() {
                elements.buttons.save.icon().hide();
                toggleFields(false);
                elements.alerts.saved.show();

                ['old_password', 'password1', 'password2'].forEach(function(field) {
                    elements.fields[field].field.val("");
                });
            },
            error: function(response) {
                console.log(response);
                elements.buttons.save.icon().hide();
                toggleFields(false);

                let error = response.responseJSON;
                elements.fields[error.data.field].icon().addClass('fa-times field-feedback-red').show();
                elements.fields[error.data.field].messages.api.text(error.description).show();
                elements.fields[error.data.field].field.addClass('is-invalid');
                elements.fields[error.data.field].field.focus();
            }
        });
    };

    elements.fields.groups.select.on('focus', function() {
        $(this).select();
    });

    elements.buttons.save.button.on('click', save);
    elements.fields.groups.all.on('keypress', function(e) {
        if (e.which === 13) elements.buttons.save.button.click();
    });

    loadForm();
});