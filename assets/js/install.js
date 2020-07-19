$(function() {
    const webRoot = $("meta[name=web_root]").attr("content");
    const csrfToken = $("meta[name=csrf_token]").attr("content");

    const elements = {
        sections: {
            card: $("#install-card"),
            databases: {
                all: $(".db-section"),
                none: $("#db-section-none"),
                mysql: $("#db-section-mysql"),
                sqlite: $("#db-section-sqlite")
            }
        },
        fields: {
            language: {
                field: $("#language"),
                icon: function() { return $("#language-icon"); }
            },
            install_key: {
                field: $("#install-key"),
                icon: function() { return $("#install-key-icon"); },
                messages: {
                    all: $(".install-key-feedback-message"),
                    api: $("#install-key-feedback-message-api")
                }
            },
            db_type: {
                field: $("#db-type"),
                icon: function() { return $("#db-type-icon"); },
                options: $("#db-type option")
            },
            db_host: {
                field: $("#db-host")
            },
            db_port: {
                field: $("#db-port")
            },
            db_username: {
                field: $("#db-username")
            },
            db_password: {
                field: $("#db-password")
            },
            db_database: {
                field: $("#db-database")
            },
            user_username: {
                field: $("#user-username")
            },
            user_firstname: {
                field: $("#user-firstname")
            },
            user_lastname: {
                field: $("#user-lastname")
            },
            user_password1: {
                field: $("#user-password1")
            },
            user_password2: {
                field: $("#user-password2")
            }
        },
        fieldGroups: {
            all: $("input, select"),
            select: $(".field-select"),
            required: $(".field-required"),
        },
        buttons: {
            install: {
                button: $("#install"),
                icon: function() { return $("#install-icon"); }
            }
        },
        alerts: {
            install_success: {
                alert: $("#install-success")
            },
            install_failed: {
                alert: $("#install-alert"),
                message: $("#install-error")
            }
        }
    };

    const loadLanguages = function() {
        const onLoadLanguagesSuccess = function(response) {
            $.each(response.data.list, function(idx, language) {
                elements.fields.language.field.append($('<option>').val(language.code).append(language.description));
            });

            elements.fields.language.field.val(response.data.current.code);
            elements.fields.language.field.prop('disabled', false);
            elements.fields.language.icon().removeClass('fa-sync fa-spin').addClass('fa-globe-americas');
        }

        const onLoadLanguagesError = function(response) {
            console.log(response);
            elements.fields.language.icon().removeClass('fa-sync fa-spin').addClass('fa-exclamation-triangle');
        }

        $.ajax({
            method: 'GET',
            url: webRoot + "/api/v1/languages",
            success: onLoadLanguagesSuccess,
            error: onLoadLanguagesError
        });
    };

    const loadSupportedDatabases = function() {
        const onLoadSupportedDatabasesSuccess = function(response) {
            elements.fields.db_type.icon().hide();

            if (response.data.length === 0) {
                elements.sections.databases.none.show();
                return;
            }

            $.each(response.data, function(idx, db) {
                elements.fields.db_type.field.append($('<option>').val(db.type).append(db.description));
            });

            elements.fields.db_type.field.prop('disabled', false);
            elements.sections.databases[elements.fields.db_type.field.val()].show();
        }

        const onLoadSupportedDatabasesError = function(response) {
            console.log(response);
            elements.fields.db_type.icon().removeClass('fa-sync fa-spin').addClass('fa-exclamation-triangle');
        }

        $.ajax({
            method: 'GET',
            url: webRoot + "/api/v1/install/databases",
            success: onLoadSupportedDatabasesSuccess,
            error: onLoadSupportedDatabasesError
        });
    }

    const onLanguageChange = function() {
        $.ajax({
            method: 'POST',
            url: webRoot + "/api/v1/install/language",
            data: {
                csrf_token: csrfToken,
                lang_code: $(this).val()
            },
            success: function() { location.reload() }
        });
    };

    const onDatabaseTypeChange = function() {
        elements.sections.databases.all.hide();
        elements.sections.databases[$(this).val()].show();
    }

    const checkInstallRequisites = function() {
        let errorCount = 0;

        if (!elements.fields.install_key.field.hasClass('is-valid')) {
            errorCount++;
        }

        if (elements.fields.user_password1.field.val().length === 0) {
            errorCount++;
        }

        if (elements.fields.user_password1.field.val() !== elements.fields.user_password2.field.val()) {
            errorCount++;
        }

        elements.buttons.install.button.prop('disabled', (errorCount > 0));
    };

    const checkInstallKey = function() {
        const onCheckInstallKeySuccess = function() {
            elements.fields.install_key.field.addClass('is-valid');
            elements.fields.install_key.icon().addClass('fa-check field-feedback-green').show();
        }

        const onCheckInstallKeyError = function(response) {
            elements.fields.install_key.field.addClass('is-invalid');
            elements.fields.install_key.icon().addClass('fa-times field-feedback-red').show();
            elements.fields.install_key.messages.api.text(response.responseJSON.description).show();
        }

        elements.fields.install_key.icon().removeClass('fa-check fa-times field-feedback-green field-feedback-red').hide();
        elements.fields.install_key.field.removeClass('is-valid is-invalid');

        if (elements.fields.install_key.field.val().length === 0) {
            return;
        }

        $.ajax({
            method: 'POST',
            url: webRoot + "/api/v1/install/check_key",
            data: {
                csrf_token: csrfToken,
                key: $(this).val()
            },
            success: onCheckInstallKeySuccess,
            error: onCheckInstallKeyError,
            complete: function() {
                checkInstallRequisites();
            }
        });
    };

    const installPantry = function() {
        const onInstallSuccess = function() {
            elements.buttons.install.icon().removeClass('fa-sync fa-spin').addClass('fa-check');
            elements.sections.card.slideUp();
            elements.alerts.install_success.alert.slideDown();

            setTimeout(function() {
                location.href = webRoot;
            }, 3000);
        }

        const onInstallError = function(response) {
            elements.alerts.install_failed.message.text(response.responseJSON.description);
            elements.alerts.install_failed.alert.show();

            if (response.status !== 403) {
                elements.buttons.install.button.prop('disabled', false);
                elements.fieldGroups.all.prop('disabled', false);
                elements.buttons.install.icon().removeClass('fa-sync fa-spin').addClass('fa-arrow-right');
            }
        }

        elements.alerts.install_failed.alert.hide();
        elements.buttons.install.button.prop('disabled', true);
        elements.fieldGroups.all.prop('disabled', true);
        elements.buttons.install.icon().removeClass('fa-arrow-right').addClass('fa-sync fa-spin');

        $.ajax({
            method: 'POST',
            url: webRoot + "/api/v1/install",
            data: {
                csrf_token: csrfToken,
                language: elements.fields.language.field.val(),
                key: elements.fields.install_key.field.val(),
                db_type: elements.fields.db_type.field.val(),
                db_host: elements.fields.db_host.field.val() || "localhost",
                db_port: elements.fields.db_port.field.val() || "3306",
                db_username: elements.fields.db_username.field.val() || "pantry",
                db_password: elements.fields.db_password.field.val(),
                db_database: elements.fields.db_database.field.val() || "pantry",
                user_username: elements.fields.user_username.field.val() || "admin",
                user_firstname: elements.fields.user_firstname.field.val(),
                user_lastname: elements.fields.user_lastname.field.val(),
                user_password: elements.fields.user_password1.field.val()
            },
            success: onInstallSuccess,
            error: onInstallError
        });
    };

    elements.fieldGroups.select.on('focus', function() {
        $(this).select();
    });
    elements.fields.language.field.on('change', onLanguageChange);
    elements.fields.db_type.field.on('change', onDatabaseTypeChange);
    elements.fields.install_key.field.on('blur', checkInstallKey);
    elements.fieldGroups.required.on('input propertychange', checkInstallRequisites);
    elements.buttons.install.button.on('click', installPantry);

    loadLanguages();
    loadSupportedDatabases();
});
