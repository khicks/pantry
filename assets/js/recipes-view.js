$(function() {
    const appName = $("meta[name=app_name]").attr("content");
    const webRoot = $("meta[name=web_root]").attr("content");
    const csrfToken = $("meta[name=csrf_token]").attr("content");
    const recipeSlug = $("meta[name=recipe_slug]").attr("content");

    const loadingWheel = $("#main-loading-wheel");
    const recipeWrapper = $("#recipe-wrapper");

    const recipeElements = {
        document: $(document),
        title: $("#recipe-title"),
        blurb: $("#recipe-blurb"),
        detailsCol: $("#recipe-details-col"),
        descriptionCol: $("#recipe-description-col"),
        description: $("#recipe-description"),
        servings: $("#recipe-servings"),
        prepTime: $("#recipe-prep-time"),
        cookTime: $("#recipe-cook-time"),
        tableCol: $("#recipe-table-col"),
        imageCol: $("#recipe-image-col"),
        image: $("#recipe-image"),
        ingredients: $("#recipe-ingredients"),
        ingredientsNone: $("#recipe-ingredients-none"),
        directions: $("#recipe-directions"),
        author: {
            name: $("#recipe-author-name"),
            none: $("#recipe-author-none")
        },
        source: {
            wrapper: $("#recipe-source"),
            link: $("#recipe-source-link")
        },
        buttons: {
            edit: $("#recipe-edit-button"),
            delete: $("#recipe-delete-button"),
            deleteConfirm: $("#recipe-delete-confirm-button")
        },
        modals: {
            delete: $("#recipe-delete-modal")
        },
        fields: {
            deleteRecipeId: $("#recipe-delete-modal-recipeid"),
            deleteRecipeName: $("#recipe-delete-modal-name")
        },
        icons: {
            deleteConfirm: function() { return $("#recipe-delete-button-icon") }
        }
    };

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
        recipeElements.title.html(response.data.title);
        recipeElements.blurb.html(response.data.blurb);
        recipeElements.description.html(response.data.description_html);
        recipeElements.servings.html(response.data.servings);
        recipeElements.prepTime.html(convertMinutesToTime(response.data.prep_time, response.data.lang));
        recipeElements.cookTime.html(convertMinutesToTime(response.data.cook_time, response.data.lang));

        // edit and delete buttons
        if (response.data.effective_permission_level < 2) {
            recipeElements.buttons.edit.hide();
        }
        if (response.data.effective_permission_level < 3) {
            recipeElements.buttons.delete.hide();
        }

        // image
        if (response.data.image === null) {
            // image not present
            recipeElements.detailsCol.addClass("col-12");
            recipeElements.descriptionCol.addClass("col-md-6").css("border-right", "1px solid #d9d9d9");
            recipeElements.tableCol.addClass("col-md-6");
        }
        else {
            // image present
            recipeElements.detailsCol.addClass("col-md-6");
            recipeElements.descriptionCol.addClass("col-12");
            recipeElements.tableCol.addClass("col-12");
            recipeElements.imageCol.addClass("col-md-6").show();
            recipeElements.image.attr("src", response.data.image.md_path);
        }

        // ingredients
        recipeElements.ingredients.html(response.data.ingredients_html);

        // directions
        recipeElements.directions.html(response.data.directions_html);

        // author
        if (response.data.author) {
            recipeElements.author.name.html(response.data.author.display_name);
        }
        else {
            recipeElements.author.none.show();
        }

        // source
        if (response.data.source) {
            recipeElements.source.wrapper.show();
            recipeElements.source.link.attr("href", response.data.source).text(response.data.source);
        }

        // delete modal
        recipeElements.fields.deleteRecipeId.val(response.data.id);
        recipeElements.fields.deleteRecipeName.html(response.data.title);

        //render
        recipeElements.document.attr("title", response.data.title + " - " + appName);
        recipeWrapper.show();
        loadingWheel.hide();
    };

    const onRecipeFail = function() {
        $("#recipe-fail").show();
        loadingWheel.hide();
    };

    const recipeDelete = function() {
        recipeElements.buttons.deleteConfirm.prop('disabled', true);
        recipeElements.icons.deleteConfirm().removeClass('fa-trash-alt').addClass('fa-sync fa-spin');

        $.ajax({
            method: "POST",
            url: webRoot + "/api/v1/recipes/delete",
            data: {
                csrf_token: csrfToken,
                id: recipeElements.fields.deleteRecipeId.val(),
            },
            success: function() {
                recipeElements.icons.deleteConfirm().removeClass('fa-sync fa-spin').addClass('fa-check');
                setTimeout(function() {
                    window.location.replace(webRoot + "/recipes");
                }, 500);
            }
        });
    }

    recipeElements.buttons.edit.attr("href", webRoot + "/recipe/" + recipeSlug + "/edit");
    recipeElements.buttons.deleteConfirm.on('click', recipeDelete)

    $.ajax({
        method: "GET",
        url: webRoot + "/api/v1/recipe/" + recipeSlug,
        success: onRecipeLoad,
        error: onRecipeFail
    });
});
