$(function() {
    const webRoot = $("meta[name=web_root]").attr("content");
    const csrfToken = $("meta[name=csrf_token]").attr("content");

    const elements = {
        form: $("#recipe-form"),
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


    const onCCLoad = function(response) {
        $.each(response.data.courses, function() {
            elements.fields.course.field.append($("<option />").val(this.id).text(this.title));
        });
        $.each(response.data.cuisines, function() {
            elements.fields.cuisine.field.append($("<option />").val(this.id).text(this.title));
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
            url: webRoot + "/api/v1/recipes/create",
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
                setTimeout(function() {
                    window.location.replace(webRoot + "/recipe/" + elements.fields.slug.field.val());
                }, 500);
            }
        });
    };

    elements.buttons.save.button.on('click', saveRecipe);
    elements.buttons.cancel.attr("href", webRoot + "/recipes");

    $.ajax({
        method: "GET",
        url: webRoot + "/api/v1/courses-cuisines",
        success: onCCLoad,
        error: onCCFail,
    });

    elements.fields.title.field.val("Cheeseburger 3");
    elements.fields.slug.field.val("cheeseburger3");
    elements.fields.blurb.field.val("Yet another cheeseburger");
    elements.fields.description.field.val("They're the best.");
    elements.fields.servings.field.val(1);
    elements.fields.prepTime.field.val(1);
    elements.fields.cookTime.field.val(1);
    elements.fields.ingredients.field.val("* Cheeseburger");
    elements.fields.directions.field.val("Cook it.");
});