$(function() {
    const appName = $("meta[name=app_name]").attr("content");
    const webRoot = $("meta[name=web_root]").attr("content");
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
            delete: $("#recipe-delete-button")
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
     * @param response.data.permission_level
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
        if (response.data.permission_level < 2) {
            recipeElements.buttons.edit.hide();
        }
        if (response.data.permission_level < 3) {
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
            recipeElements.image.attr("src", response.data.image.path);
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

        //render
        recipeElements.document.attr("title", response.data.title + " - " + appName);
        recipeWrapper.show();
        loadingWheel.hide();
    };

    const onRecipeFail = function() {
        alert("Recipe failed to load.");
    };

    recipeElements.buttons.edit.attr("href", webRoot + "/recipes/" + recipeSlug + "/edit");

    $.ajax({
        method: "GET",
        url: webRoot + "/api/v1/recipes/" + recipeSlug,
        success: onRecipeLoad,
        error: onRecipeFail
    });
});
