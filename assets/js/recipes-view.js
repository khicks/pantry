$(function() {
    const appName = $("meta[name=app_name]").attr("content");
    const webRoot = $("meta[name=web_root]").attr("content");
    const csrfToken = $("meta[name=csrf_token]").attr("content");
    const recipeSlug = $("meta[name=recipe_slug]").attr("content");

    const loadingWheel = $("#main-loading-wheel");
    const recipeWrapper = $("#recipe-wrapper");

    const elements = {
        document: $(document),
        sections: {
            details: $("#recipe-details-section"),
            description: $("#recipe-description-section"),
            table: $("#recipe-table-section"),
            image: $("#recipe-image-section"),
        },
        fields: {
            title: $("#recipe-title"),
            blurb: $("#recipe-blurb"),
            description: $("#recipe-description"),
            course: {
                field: $("#recipe-course"),
                none: $("#recipe-course-none")
            },
            cuisine: {
                field: $("#recipe-cuisine"),
                none: $("#recipe-cuisine-none")
            },
            servings: $("#recipe-servings"),
            prep_time: {
                field: $("#recipe-prep-time"),
                none: $("#recipe-prep-time-none")
            },
            cook_time: {
                field: $("#recipe-cook-time"),
                none: $("#recipe-cook-time-none")
            },
            ingredients: {
                field: $("#recipe-ingredients"),
                none: $("#recipe-ingredients-none")
            },
            directions: {
                field: $("#recipe-directions"),
                none: $("#recipe-directions-none")
            },
            author: {
                name: $("#recipe-author-name"),
                none: $("#recipe-author-none")
            },
            source: {
                wrapper: $("#recipe-source"),
                link: $("#recipe-source-link")
            },
        },
        image: {
            img: $("#recipe-image"),
            download: $("#recipe-image-download")
        },
        buttons: {
            edit: $("#recipe-edit-button"),
            delete: $("#recipe-delete-button"),
        },
        modals: {
            delete: {
                fields: {
                    id: $("#recipe-delete-modal-recipeid"),
                    name: $("#recipe-delete-modal-name")
                },
                buttons: {
                    confirm: {
                        button: $("#recipe-delete-confirm-button"),
                        icon: function() { return $("#recipe-delete-button-icon") }
                    }
                }
            }
        }
    }

    /**
     * @param lang.day
     * @param lang.days
     * @param lang.hour
     * @param lang.hours
     * @param lang.minute
     * @param lang.minutes
     */
    const convertMinutesToTime = function(totalMinutes, lang) {
        let days = Math.floor(totalMinutes / 1440);
        let hours = Math.floor((totalMinutes % 1440) / 60);
        let minutes = totalMinutes % 60;

        let output = [];
        if (days >= 1) {
            output.push(days + " " + ((days > 1) ? lang.days : lang.day));
        }
        if (hours >= 1) {
            output.push(hours + " " + ((hours > 1) ? lang.hours : lang.hour));
        }
        if (minutes >= 1) {
            output.push(minutes + " " + ((minutes > 1) ? lang.minutes : lang.minute));
        }

        return output.join(', ');
    };

    /**
     * @param response.data.effective_permission_level
     * @param response.data.description_html
     * @param response.data.ingredients_html
     * @param response.data.directions_html
     * @param response.data.author.display_name
     * @param response.data.prep_time
     * @param response.data.cook_time
     */
    const onRecipeLoad = function(response) {
        // intro
        elements.fields.title.html(response.data.title);
        elements.fields.blurb.html(response.data.blurb);
        elements.fields.description.html(response.data.description_html);

        // course, cuisine
        if (response.data.course) {
            elements.fields.course.field.html(response.data.course.title);
        }
        else {
            elements.fields.course.none.show();
        }

        if (response.data.cuisine) {
            elements.fields.cuisine.field.html(response.data.cuisine.title);
        }
        else {
            elements.fields.cuisine.none.show();
        }

        elements.fields.servings.html(response.data.servings);

        if (response.data.prep_time > 0) {
            elements.fields.prep_time.field.html(convertMinutesToTime(response.data.prep_time, response.data.lang));
        }
        else {
            elements.fields.prep_time.none.show();
        }

        if (response.data.cook_time > 0) {
            elements.fields.cook_time.field.html(convertMinutesToTime(response.data.cook_time, response.data.lang));
        }
        else {
            elements.fields.cook_time.none.show();
        }

        // edit and delete buttons
        if (response.data.effective_permission_level < 2) {
            elements.buttons.edit.hide();
        }
        if (response.data.effective_permission_level < 3) {
            elements.buttons.delete.hide();
        }

        // image
        if (response.data.image === null) {
            // image not present
            elements.sections.details.addClass("col-12");
            elements.sections.description.addClass("col-md-6");
            elements.sections.table.addClass("col-md-6");
        }
        else {
            // image present
            elements.sections.details.addClass("col-md-6");
            elements.sections.description.addClass("col-12");
            elements.sections.table.addClass("col-12");
            elements.sections.image.addClass("col-md-6").show();
            elements.image.img.attr("src", response.data.image.md_path);
            elements.image.img.attr("alt", response.data.title);
            elements.image.download.attr("href", response.data.image.path);
        }

        // ingredients
        elements.fields.ingredients.field.html(response.data.ingredients_html);
        if (response.data.ingredients_raw.length === 0) {
            elements.fields.ingredients.none.show();
        }

        // directions
        elements.fields.directions.field.html(response.data.directions_html);
        if (response.data.directions_raw.length === 0) {
            elements.fields.directions.none.show();
        }

        // author
        if (response.data.author) {
            elements.fields.author.name.html(response.data.author.display_name);
        }
        else {
            elements.fields.author.none.show();
        }

        // source
        if (response.data.source) {
            elements.fields.source.wrapper.show();
            elements.fields.source.link.attr("href", response.data.source).text(response.data.source);
        }

        // delete modal
        elements.modals.delete.fields.id.val(response.data.id);
        elements.modals.delete.fields.name.html(response.data.title);

        //render
        elements.document.attr("title", response.data.title + " - " + appName);
        recipeWrapper.show();
        loadingWheel.hide();
    };

    const onRecipeFail = function() {
        $("#recipe-fail").show();
        loadingWheel.hide();
    };

    const recipeDelete = function() {
        elements.modals.delete.buttons.confirm.button.prop('disabled', true);
        elements.modals.delete.buttons.confirm.icon().removeClass('fa-trash-alt').addClass('fa-sync fa-spin');

        $.ajax({
            method: "POST",
            url: webRoot + "/api/v1/recipes/delete",
            data: {
                csrf_token: csrfToken,
                id: elements.modals.delete.fields.id.val(),
            },
            success: function() {
                elements.modals.delete.buttons.confirm.icon().removeClass('fa-sync fa-spin').addClass('fa-check');
                setTimeout(function() {
                    window.location.replace(webRoot + "/recipes");
                }, 500);
            }
        });
    }

    elements.buttons.edit.attr("href", webRoot + "/recipe/" + recipeSlug + "/edit");
    elements.modals.delete.buttons.confirm.button.on('click', recipeDelete)

    $.ajax({
        method: "GET",
        url: webRoot + "/api/v1/recipe/" + recipeSlug,
        success: onRecipeLoad,
        error: onRecipeFail
    });
});
