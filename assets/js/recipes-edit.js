$(function() {
    const webRoot = $("meta[name=web_root]").attr("content");
    const csrfToken = $("meta[name=csrf_token]").attr("content");
    const recipeSlug = $("meta[name=recipe_slug]").attr("content");

    const elements = {
        form: $("#recipe-form"),
        loading: $("#main-loading-wheel"),
        alerts: {
            loadFailed: $("#recipe-edit-load-failed"),
            saveFailed: $("#recipe-edit-save-failed")
        },
        buttons: {
            cancel: $("#cancel-button"),
            save: {
                button: $("#recipe-save-button"),
                icon: function() { return $("#recipe-save-button-icon") },
            }
        },
        fieldGroups: {
            select: $("select.form-control"),
            text: $("input.form-control"),
            textarea: $("textarea.form-control"),
        },
        fields: {
            id: {
                field: $("#id"),
            },
            title: {
                field: $("#recipe-title"),
                icon: function() { return $("#title-feedback-icon"); },
                messages: {
                    all: $(".title-feedback-message"),
                    none: $("#title-feedback-message-none"),
                    short: $("#title-feedback-message-short"),
                    long: $("#title-feedback-message-long"),
                    api: $("#title-feedback-message-api")
                }
            },
            slug: {
                field: $("#slug"),
                helpIcon: function() { return $("#slug-help-icon"); },
                helpText: $("#slug-help-text"),
                icon: function() { return $("#slug-feedback-icon"); },
                messages: {
                    all: $(".slug-feedback-message"),
                    none: $("#slug-feedback-message-none"),
                    short: $("#slug-feedback-message-short"),
                    long: $("#slug-feedback-message-long"),
                    api: $("#slug-feedback-message-api")
                }
            },
            featured: {
                button: $("#featured"),
                value: function() { return $("#featured").hasClass('active').toString() },
                helpIcon: function() { return $("#featured-help-icon"); },
                helpText: $("#featured-help-text"),
                featuredText: $("#featured-text"),
                notFeaturedText: $("#not-featured-text")
            },
            blurb: {
                field: $("#blurb"),
                icon: function() { return $("#blurb-feedback-icon"); },
                messages: {
                    all: $(".blurb-feedback-message"),
                    long: $("#blurb-feedback-message-long"),
                    api: $("#blurb-feedback-message-api")
                }
            },
            description: {
                field: $("#description"),
                icon: function() { return $("#description-feedback-icon"); },
                messages: {
                    all: $(".description-feedback-message"),
                    api: $("#description-feedback-message-api")
                }
            },
            image: {
                field: $("#image"),
                icon: function() { return $("#image-feedback-icon") },
                label: $("#image-label"),
                messages: {
                    text: $("#image-feedback-text"),
                    all: $(".image-feedback-message"),
                    api: $("#image-feedback-message-api"),
                },
                resetButton: $("#image-reset-button"),
                clearField: $("#image-clear"),
                clearButton: $("#image-clear-button"),
                preview: $("#image-preview"),
                placeholder: $("#image-placeholder"),
                originalPath: null
            },
            course: {
                field: $("#course"),
                icon: function() { return $("#course-feedback-icon"); },
                messages: {
                    all: $(".course-feedback-message"),
                    api: $("#course-feedback-message-api")
                }
            },
            cuisine: {
                field: $("#cuisine"),
                icon: function() { return $("#cuisine-feedback-icon"); },
                messages: {
                    all: $(".cuisine-feedback-message"),
                    api: $("#cuisine-feedback-message-api")
                }
            },
            servings: {
                field: $("#servings"),
                icon: function() { return $("#servings-feedback-icon"); },
                messages: {
                    all: $(".servings-feedback-message"),
                    api: $("#servings-feedback-message-api")
                }
            },
            prep_time: {
                field: $("#prep-time"),
                icon: function() { return $("#prep-time-feedback-icon"); },
                messages: {
                    all: $(".prep-time-feedback-message"),
                    api: $("#prep-time-feedback-message-api")
                }
            },
            cook_time: {
                field: $("#cook-time"),
                icon: function() { return $("#cook-time-feedback-icon"); },
                messages: {
                    all: $(".cook-time-feedback-message"),
                    api: $("#cook-time-feedback-message-api")
                }
            },
            ingredients: {
                field: $("#ingredients"),
                icon: function() { return $("#ingredients-feedback-icon"); },
                messages: {
                    all: $(".ingredients-feedback-message"),
                    api: $("#ingredients-feedback-message-api")
                }
            },
            directions: {
                field: $("#directions"),
                icon: function() { return $("#directions-feedback-icon"); },
                messages: {
                    all: $(".directions-feedback-message"),
                    api: $("#directions-feedback-message-api")
                }
            },
            source: {
                field: $("#source"),
                icon: function() { return $("#source-feedback-icon"); },
                messages: {
                    all: $(".source-feedback-message"),
                    api: $("#source-feedback-message-api")
                }
            },
            visibility: {
                all: $("input[name=visibility]"),
                selected: function() { return $("input[name=visibility]:checked") },
                private: $("#visibility-private"),
                internal: $("#visibility-internal"),
                public: $("#visibility-public"),
                value: function() {
                    let sel = $("input[name=visibility]:checked");
                    return (sel.length > 0) ? sel.val() : null;
                }
            },
            default_permission: {
                all: $("input[name=default-permission]"),
                selected: function() { return $("input[name=default-permission]:checked") },
                read: $("#default-permission-read"),
                write: $("#default-permission-write"),
                admin: $("#default-permission-admin"),
                value: function() {
                    let sel = $("input[name=default-permission]:checked");
                    return (sel.length > 0) ? sel.val() : null;
                }
            }
        }
    };

    const imageLabelOriginalText = elements.fields.image.label.text();

    /**
     * @param response.data.description_raw
     * @param response.data.ingredients_raw
     * @param response.data.directions_raw
     * @param response.data.effective_permission_level
     * @param response.data.lang.slug_help_text
     */
    const onRecipeLoad = function(response) {
        if (response.data.effective_permission_level < 2) {
            $("#no-permission-alert").show();
            elements.buttons.save.button.hide();
        }

        $.each(['id', 'title', 'slug', 'blurb', 'servings'], function() {
            elements.fields[this].field.val(response.data[this]);
        });
        elements.fields.description.field.val(response.data.description_raw);
        elements.fields.prep_time.field.val(response.data.prep_time);
        elements.fields.cook_time.field.val(response.data.cook_time);
        elements.fields.ingredients.field.val(response.data.ingredients_raw);
        elements.fields.directions.field.val(response.data.directions_raw);
        elements.fields.source.field.val(response.data.source);

        if (response.data.image) {
            elements.fields.image.originalPath = response.data.image.md_path;
            elements.fields.image.preview.attr('src', elements.fields.image.originalPath).show();
            elements.fields.image.placeholder.hide();
            elements.fields.image.resetButton.show();
        }

        if (response.data.featured) {
            elements.fields.featured.button.click();
        }

        if (response.data.course)
            elements.fields.course.field.val(response.data.course.id);
        if (response.data.cuisine)
            elements.fields.cuisine.field.val(response.data.cuisine.id);

        // permissions radios
        if (response.data.visibility_level === 0) {
            elements.fields.visibility.private.prop('checked', true);
            elements.fields.default_permission.all.prop('disabled', true);
            elements.fields.default_permission.read.prop('checked', false);
        }
        else {
            if (response.data.visibility_level === 1) {
                elements.fields.visibility.internal.prop('checked', true);
            }

            if (response.data.default_permission_level === 2) {
                elements.fields.default_permission.write.prop('checked', true);
            }
            else if (response.data.default_permission_level === 3) {
                elements.fields.default_permission.admin.prop('checked', true);
            }
        }
        if (response.data.effective_permission_level < 3) {
            elements.fields.visibility.all.prop('disabled', true);
            elements.fields.default_permission.all.prop('disabled', true);
        }

        elements.buttons.cancel.attr("href", webRoot + "/recipe/" + recipeSlug);

        elements.form.show();
        elements.loading.hide();
    };

    const onRecipeFail = function() {
        elements.loading.hide();
        elements.alerts.loadFailed.show();
    };

    /**
     * @param response.data.courses
     * @param response.data.cuisines
     */
    const onCCLoad = function(response) {
        $.each(response.data.courses, function() {
            elements.fields.course.field.append($("<option />").val(this.id).text(this.title));
        });
        $.each(response.data.cuisines, function() {
            elements.fields.cuisine.field.append($("<option />").val(this.id).text(this.title));
        });

        $.ajax({
            method: "GET",
            url: webRoot + "/api/v1/recipe/" + recipeSlug,
            success: onRecipeLoad,
            error: onRecipeFail
        });
    };

    const onCCFail = function() {
        alert("courses-cuisines failed to load");
    };

    const onFeaturedToggle = function() {
        let was_pressed = $(this).hasClass('active');
        if (was_pressed) {
            elements.fields.featured.button.addClass('btn-outline-secondary').removeClass('btn-primary');
            elements.fields.featured.notFeaturedText.show();
            elements.fields.featured.featuredText.hide();
        }
        else {
            elements.fields.featured.button.addClass('btn-primary').removeClass('btn-outline-secondary');
            elements.fields.featured.notFeaturedText.hide();
            elements.fields.featured.featuredText.show();
        }
    };

    const onImageChange = function() {
        let filename = $(this).val();
        let lastIndex = filename.lastIndexOf("\\");
        if (lastIndex >= 0) {
            filename = filename.substr(lastIndex + 1);
        }
        elements.fields.image.label.html(filename);
        elements.fields.image.clearField.val("false");
        elements.fields.image.field.removeClass('is-invalid');
        elements.fields.image.icon().removeClass('fa-times field-feedback-red').hide();
        elements.fields.image.messages.text.hide();

        if (this.files && this.files[0]) {
            let reader = new FileReader();
            reader.onload = function(e) {
                elements.fields.image.preview.attr('src', e.target.result)
            }
            reader.readAsDataURL(this.files[0]);
            elements.fields.image.preview.show();
            elements.fields.image.placeholder.hide();
        }
    };

    const onImageReset = function() {
        elements.fields.image.field.val(null);
        elements.fields.image.label.html(imageLabelOriginalText);
        elements.fields.image.clearField.val("false");
        elements.fields.image.preview.attr('src', elements.fields.image.originalPath).show();
        elements.fields.image.placeholder.hide();
        elements.fields.image.field.removeClass('is-invalid');
        elements.fields.image.icon().removeClass('fa-times field-feedback-red').hide();
        elements.fields.image.messages.text.hide();
    }

    const onImageClear = function () {
        elements.fields.image.field.val(null);
        elements.fields.image.label.html(imageLabelOriginalText);
        elements.fields.image.clearField.val("true");
        elements.fields.image.preview.attr('src', "").hide();
        elements.fields.image.placeholder.show();
        elements.fields.image.field.removeClass('is-invalid');
        elements.fields.image.icon().removeClass('fa-times field-feedback-red').hide();
        elements.fields.image.messages.text.hide();
    };

    const onVisibilityChange = function () {
        if ($(this).val() === "0") {
            elements.fields.default_permission.all.prop('checked', false).prop('disabled', true);
        }
        else {
            elements.fields.default_permission.all.prop('disabled', false);
            if (elements.fields.default_permission.selected().length === 0) {
                elements.fields.default_permission.read.prop('checked', true);
            }
        }
    };

    const saveRecipe = function() {
        const onSaveSuccess = function() {
            elements.buttons.save.icon().removeClass('fa-sync fa-spin').addClass('fa-check');
            setTimeout(function() {
                window.location.replace(webRoot + "/recipe/" + elements.fields.slug.field.val());
            }, 500);
        }

        const onSaveFail = function(response) {
            $.each(elements.fieldGroups, function() {
                this.prop('disabled', false);
            });
            elements.buttons.save.button.prop('disabled', false);
            elements.buttons.save.icon().removeClass('fa-sync fa-spin').addClass('fa-save');

            if (!response.responseJSON) {
                elements.alerts.saveFailed.text("Save failed. Unknown error.").show();
                console.log(response);
                return;
            }

            let error = response.responseJSON;

            if (error.data.issue && error.data.issue === "validation") {
                elements.fields[error.data.field].field.addClass('is-invalid');
                elements.fields[error.data.field].icon().addClass('fa-times field-feedback-red').show();
                elements.fields[error.data.field].messages.api.text(error.description).show();

                if (error.data.field === "image") {
                    elements.fields.image.messages.text.show();
                }

                return;
            }

            elements.alerts.saveFailed.text(error.description).show();
        };

        $.each(elements.fieldGroups, function() {
            this.prop('disabled', true);
        });
        $(this).prop('disabled', true);
        elements.buttons.save.icon().removeClass('fa-save').addClass('fa-sync fa-spin');
        elements.alerts.saveFailed.hide();

        let data = {
            csrf_token: csrfToken,
            id: elements.fields.id.field.val(),
            title: elements.fields.title.field.val(),
            slug: elements.fields.slug.field.val(),
            blurb: elements.fields.blurb.field.val(),
            description: elements.fields.description.field.val(),
            image_clear: elements.fields.image.clearField.val(),
            servings: elements.fields.servings.field.val(),
            prep_time: elements.fields.prep_time.field.val(),
            cook_time: elements.fields.cook_time.field.val(),
            ingredients: elements.fields.ingredients.field.val(),
            directions: elements.fields.directions.field.val(),
            source: elements.fields.source.field.val(),
            course_id: elements.fields.course.field.val(),
            cuisine_id: elements.fields.cuisine.field.val(),
            visibility_level: elements.fields.visibility.value(),
            default_permission_level: elements.fields.default_permission.value(),
            featured: elements.fields.featured.value()
        };

        let formData = new FormData();
        for (let key in data) {
            formData.append(key, data[key])
        }

        if (elements.fields.image.field[0].files && elements.fields.image.field[0].files[0]) {
            formData.append('image', elements.fields.image.field[0].files[0]);
        }

        $.ajax({
            method: "POST",
            url: webRoot + "/api/v1/recipes/edit",
            contentType: false,
            processData: false,
            data: formData,
            success: onSaveSuccess,
            error: onSaveFail
        });
    };

    const clearFieldErrors = function() {
        $(this).removeClass('is-invalid');
        $(this).siblings('label').children('.field-feedback-icon').removeClass('fa-times field-feedback-red').hide();
    }

    elements.buttons.save.button.on('click', saveRecipe);
    elements.fields.featured.button.on('click', onFeaturedToggle);
    elements.fields.image.field.on('change', onImageChange);
    elements.fields.image.resetButton.on('click', onImageReset);
    elements.fields.image.clearButton.on('click', onImageClear);
    elements.fields.visibility.all.on('change', onVisibilityChange);

    $.each(elements.fieldGroups, function() {
        this.on('input propertychange', clearFieldErrors);
    });

    elements.fields.slug.helpIcon().tooltip({
        container: "body",
        placement: "top",
        title: elements.fields.slug.helpText
    });

    elements.fields.featured.helpIcon().tooltip({
        container: "body",
        placement: "top",
        title: elements.fields.featured.helpText
    });

    $.ajax({
        method: "GET",
        url: webRoot + "/api/v1/courses-cuisines",
        success: onCCLoad,
        error: onCCFail,
    });
});
