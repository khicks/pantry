$(function() {
    const webRoot = $("meta[name=web_root]").attr("content");

    const elements = {
        loadingWheel: $("#browse-loading"),
        recipeRow: $("#browse-row"),
        alerts: {
            noRecipes: $("#browse-none"),
            loadFailed: $("#browse-error")
        },
        templates: {
            recipeCard: Handlebars.compile($("#recipe-card-template").html())
        }
    };

    const onRecipesLoad = function(response) {
        elements.loadingWheel.hide();

        if (response.data.recipes.length === 0) {
            elements.alerts.newNone.show();
            return;
        }

        $.each(response.data.recipes, function(idx, recipe) {
            elements.recipeRow.append(elements.templates.recipeCard({
                recipe: recipe
            }));
        });
    };

    const onRecipesFail = function() {
        elements.loadingWheel.hide();
        elements.alerts.loadFailed.show();
    };

    const getRecipes = function() {
        $.ajax({
            method: "GET",
            url: webRoot + "/api/v1/recipes/all",
            success: onRecipesLoad,
            error: onRecipesFail
        });
    }

    getRecipes();
});