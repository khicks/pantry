$(function () {
    const webRoot = $("meta[name=web_root]").attr("content");
    const csrfToken = $("meta[name=csrf_token]").attr("content");

    const elements = {
        sections: {
            card: $("#update-card")
        },
        fields: {
            install_key: {
                field: $("#install-key"),
                icon: function() { return $("#install-key-icon"); },
                messages: {
                    all: $(".install-key-feedback-message"),
                    api: $("#install-key-feedback-message-api")
                }
            }
        },
        fieldGroups: {
            all: $("input, select"),
            select: $(".field-select")
        },
        text: {
            current_version: {
                text: $("#current-version-text"),
                icon: function() { return $("#current-version-icon"); }
            },
            new_version: {
                text: $("#new-version-text"),
                icon: function() { return $("#new-version-icon"); }
            },
        },
        buttons: {
            update: {
                button: $("#update"),
                icon: function() { return $("#update-icon"); }
            }
        },
        alerts: {
            update_success: {
                alert: $("#update-success")
            },
            update_failed: {
                alert: $("#update-alert"),
                message: $("#update-error")
            }
        }
    };

    const checkUpdateRequisites = function() {
        let errorCount = 0;

        if (!elements.fields.install_key.field.hasClass('is-valid')) {
            errorCount++;
        }

        elements.buttons.update.button.prop('disabled', (errorCount > 0));
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
                checkUpdateRequisites();
            }
        });
    };

    const loadVersion = function() {
        const onLoadVersionSuccess = function(response) {
            elements.text.current_version.text.text(response.data.db.current_version);
            elements.text.new_version.text.text(response.data.db.new_version);
            elements.text.current_version.icon().hide();
            elements.text.new_version.icon().hide();
        };

        const onLoadVersionError = function() {
            elements.text.current_version.icon().removeClass('fa-sync fa-spin').addClass('fa-exclamation-triangle');
            elements.text.new_version.icon().removeClass('fa-sync fa-spin').addClass('fa-exclamation-triangle');
        };

        $.ajax({
            method: 'GET',
            url: webRoot + "/api/v1/update/version",
            success: onLoadVersionSuccess,
            error: onLoadVersionError,
        })
    };

    const updatePantry = function() {
        const onUpdateSuccess = function() {
            elements.buttons.update.icon().removeClass('fa-sync fa-spin').addClass('fa-check');
            elements.sections.card.slideUp();
            elements.alerts.update_success.alert.slideDown();

            setTimeout(function() {
                location.href = webRoot;
            }, 3000);
        }

        const onUpdateError = function(response) {
            elements.alerts.update_failed.message.text(response.responseJSON.description);
            elements.alerts.update_failed.alert.show();

            if (response.status !== 403) {
                elements.buttons.update.button.prop('disabled', false);
                elements.fieldGroups.all.prop('disabled', false);
                elements.buttons.update.icon().removeClass('fa-sync fa-spin').addClass('fa-arrow-up');
            }
        }

        elements.alerts.update_failed.alert.hide();
        elements.buttons.update.button.prop('disabled', true);
        elements.fieldGroups.all.prop('disabled', true);
        elements.buttons.update.icon().removeClass('fa-arrow-right').addClass('fa-sync fa-spin');

        $.ajax({
            method: 'POST',
            url: webRoot + "/api/v1/update",
            data: {
                csrf_token: csrfToken,
                key: elements.fields.install_key.field.val(),
            },
            success: onUpdateSuccess,
            error: onUpdateError
        })
    }

    elements.fields.install_key.field.on('blur', checkInstallKey);
    elements.buttons.update.button.on('click', updatePantry);

    loadVersion();
});