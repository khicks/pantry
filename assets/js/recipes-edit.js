$(function() {
    const webRoot = $("meta[name=web_root]").attr("content");
    const csrfToken = $('meta[name=csrf_token]').attr("content");
    const recipeSlug = $("meta[name=recipe_slug]").attr("content");

    const elements = {
        form: $("#edit-recipe-form"),
        loading: $("#main-loading-wheel"),
        select_fields: $(".select-field"),
        buttons: {
            cancel: $("#cancel-button"),
            save: {
                button: $("#recipe-save-button"),
                icon: function() { return $("#recipe-save-button-icon") },
            }
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

        elements.fields.id.field.val(response.data.id);
        elements.fields.title.field.val(response.data.title);
        elements.fields.slug.field.val(response.data.slug);
        elements.fields.blurb.field.val(response.data.blurb);
        elements.fields.description.field.val(response.data.description_raw);
        elements.fields.servings.field.val(response.data.servings);
        elements.fields.prepTime.field.val(response.data.prep_time);
        elements.fields.cookTime.field.val(response.data.cook_time);
        elements.fields.ingredients.field.val(response.data.ingredients_raw);
        elements.fields.directions.field.val(response.data.directions_raw);

        elements.fields.slug.helpIcon().tooltip({
            container: "body",
            placement: "top",
            title: elements.fields.slug.helpText
        });

        elements.buttons.cancel.attr("href", webRoot + "/recipes/" + recipeSlug);

        elements.form.show();
        elements.loading.hide();
    };

    const onRecipeFail = function() {
        alert("recipe failed to load");
    };

    const saveRecipe = function() {
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
                directions: elements.fields.directions.field.val()
            }
        });
    };

    elements.select_fields.on('focus', function() {
        $(this).select();
    });

    elements.buttons.save.button.on('click', saveRecipe);

    $.ajax({
        method: "GET",
        url: webRoot + "/api/v1/recipes/" + recipeSlug,
        success: onRecipeLoad,
        error: onRecipeFail
    });
});
