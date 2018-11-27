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
        tableCol: $("#recipe-table-col"),
        imageCol: $("#recipe-image-col"),
        image: $("#recipe-image"),
        ingredients: $("#recipe-ingredients"),
        ingredientsNone: $("#recipe-ingredients-none"),
        directions: $("#recipe-directions"),
        author: {
            name: $("#recipe-author-name"),
            none: $("#recipe-author-none")
        }
    };

    $.ajax({
        method: "GET",
        url: webRoot + "/api/v1/recipes/" + recipeSlug,
        /**
         * @param response.data.description_html
         * @param response.data.directions_html
         * @param response.data.author.display_name
         */
        success: function(response) {
            recipeElements.title.html(response.data.title);
            recipeElements.blurb.html(response.data.blurb);
            recipeElements.description.html(response.data.description_html);

            // image
            if (response.data.image === null) {
                // image not present
                recipeElements.detailsCol.addClass("col-12");
                recipeElements.descriptionCol.addClass("col-sm-6").css("border-right", "1px solid #d9d9d9");
                recipeElements.tableCol.addClass("col-sm-6");
            }
            else {
                // image present
                recipeElements.detailsCol.addClass("col-sm-6");
                recipeElements.descriptionCol.addClass("col-12");
                recipeElements.tableCol.addClass("col-12");
                recipeElements.imageCol.addClass("col-sm-6").show();
                recipeElements.image.attr("src", response.data.image.path);
            }

            // ingredients
            let ingredients = response.data.ingredients;
            if (ingredients === null) {
                recipeElements.ingredientsNone.show();
            }
            else {
                let ingredientsHTML = "";
                $.each(ingredients.groups, function(index, group) {
                    ingredientsHTML += "<h5>"+group.title+"</h5>";
                    ingredientsHTML += "<ul>";
                    /**
                     * @param item.quantity
                     */
                    $.each(group.items, function(index, item) {
                        ingredientsHTML += "<li>" + item.quantity + " " + item.name + "</li>";
                    });
                    ingredientsHTML += "</ul>"
                });
                recipeElements.ingredients.html(ingredientsHTML);
            }

            //directions
            recipeElements.directions.html(response.data.directions_html);

            if (response.data.author) {
                recipeElements.author.name.html(response.data.author.display_name);
            }
            else {
                recipeElements.author.none.show();
            }

            recipeElements.document.attr("title", response.data.title + " - " + appName);
            recipeWrapper.show();
            loadingWheel.hide();
        },
        error: function() {
            alert(2);
        }
    });
});
