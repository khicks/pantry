$(function() {
    const webRoot = $("meta[name=web_root]").attr("content");
    const csrfToken = $("meta[name=csrf_token]").attr("content");
    const recipeSlug = $("meta[name=recipe_slug]").attr("content");

    const elements = {
        form: $("#edit-recipe-form"),
        loading: $("#main-loading-wheel"),
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
            prepTime: {
                field: $("#prep-time"),
                icon: function() { return $("#prep-time-feedback-icon"); },
                messages: {
                    all: $(".prep-time-feedback-message"),
                    api: $("#prep-time-feedback-message-api")
                }
            },
            cookTime: {
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
            }
        }
    };

    /**
     * @param response.data.description_raw
     * @param response.data.ingredients_raw
     * @param response.data.directions_raw
     * @param response.data.lang.slug_help_text
     */
    const onRecipeLoad = function(response) {
        if (response.data.permission_level < 2) {
            $("#no-permission-alert").show();
            elements.buttons.save.button.hide();
        }

        $.each(['id', 'title', 'slug', 'blurb', 'servings'], function() {
            elements.fields[this].field.val(response.data[this]);
        });
        elements.fields.description.field.val(response.data.description_raw);
        elements.fields.prepTime.field.val(response.data.prep_time);
        elements.fields.cookTime.field.val(response.data.cook_time);
        elements.fields.ingredients.field.val(response.data.ingredients_raw);
        elements.fields.directions.field.val(response.data.directions_raw);

        if (response.data.course)
            elements.fields.course.field.val(response.data.course.id);
        if (response.data.cuisine)
            elements.fields.cuisine.field.val(response.data.cuisine.id);

        elements.fields.slug.helpIcon().tooltip({
            container: "body",
            placement: "top",
            title: elements.fields.slug.helpText
        });

        elements.buttons.cancel.attr("href", webRoot + "/recipe/" + recipeSlug);

        elements.form.show();
        elements.loading.hide();
    };

    const onRecipeFail = function() {
        alert("recipe failed to load");
    };

    /**
     * @param response.data.courses
     * @param response.data.cuisines
     */
    const onCCLoad = function(response) {
        $.each(response.data.courses, function() {
            console.log(this);
            elements.fields.course.field.append($("<option />").val(this.id).text(this.title));
        });
        $.each(response.data.cuisines, function() {
            console.log(this);
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

    const saveRecipe = function() {
        $.each(elements.fieldGroups, function() {
            this.prop('disabled', true);
        });
        const icon = elements.buttons.save.icon;
        $(this).prop('disabled', true);
        icon().removeClass('fa-save').addClass('fa-sync fa-spin');

        $.ajax({
            method: "POST",
            url: webRoot + "/api/v1/recipes/edit",
            data: {
                csrf_token: csrfToken,
                id: elements.fields.id.field.val(),
                title: elements.fields.title.field.val(),
                slug: elements.fields.slug.field.val(),
                blurb: elements.fields.blurb.field.val(),
                description: elements.fields.description.field.val(),
                servings: elements.fields.servings.field.val(),
                prep_time: elements.fields.prepTime.field.val(),
                cook_time: elements.fields.cookTime.field.val(),
                ingredients: elements.fields.ingredients.field.val(),
                directions: elements.fields.directions.field.val(),
                course_id: elements.fields.course.field.val(),
                cuisine_id: elements.fields.cuisine.field.val()
            },
            success: function() {
                icon().removeClass('fa-sync fa-spin').addClass('fa-check');
                $(".card-body").slideUp(function() {
                    setTimeout(function() {
                        window.location.replace(webRoot + "/recipe/" + elements.fields.slug.field.val());
                    }, 500);
                });
            }
        });
    };

    $(".select-field").on('focus', function() {
        $(this).select();
    });

    elements.buttons.save.button.on('click', saveRecipe);

    $.ajax({
        method: "GET",
        url: webRoot + "/api/v1/courses-cuisines",
        success: onCCLoad,
        error: onCCFail,
    });
});
