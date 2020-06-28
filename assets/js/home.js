$(function() {
    const webRoot = $("meta[name=web_root]").attr("content");

    const elements = {
        loadingWheels: {
            featured: $("#featured-loading"),
            newRecipes: $("#new-loading"),
        },
        recipeRows: {
            featured: $("#featured-row"),
            newRecipes: $("#new-recipes-row")
        },
        alerts: {
            featuredNone: $("#featured-none"),
            newNone: $("#new-none")
        },
        templates: {
            recipeCard: Handlebars.compile($("#recipe-card-template").html()),
            sectionFailed: Handlebars.compile($("#load-section-failed").html())
        }
    }

    /**
     * @param response.data.recipes
     */
    const onFeaturedRecipesLoad = function(response) {
        elements.loadingWheels.featured.hide();

        if (response.data.recipes.length === 0) {
            elements.alerts.featuredNone.show();
            return;
        }

        $.each(response.data.recipes, function(idx, recipe) {
            elements.recipeRows.featured.append(elements.templates.recipeCard({
                recipe: recipe
            }));
        });
    }

    const onFeaturedRecipesFail = function() {
        elements.loadingWheels.featured.hide();
        elements.recipeRows.featured.append(elements.templates.sectionFailed());
    }

    const onNewRecipesLoad = function(response) {
        elements.loadingWheels.newRecipes.hide();

        if (response.data.recipes.length === 0) {
            elements.alerts.newNone.show();
            return;
        }

        $.each(response.data.recipes, function(idx, recipe) {
            elements.recipeRows.newRecipes.append(elements.templates.recipeCard({
                recipe: recipe
            }));
        });
    }

    const onNewRecipesFail = function() {
        elements.loadingWheels.newRecipes.hide();
        elements.recipeRows.newRecipes.append(elements.templates.sectionFailed());
    }

    const getFeaturedRecipes = function() {
        $.ajax({
            method: "GET",
            url: webRoot + "/api/v1/recipes/featured",
            success: onFeaturedRecipesLoad,
            error: onFeaturedRecipesFail
        });
    }

    const getNewRecipes = function() {
        $.ajax({
            method: "GET",
            url: webRoot + "/api/v1/recipes/new",
            success: onNewRecipesLoad,
            error: onNewRecipesFail
        });
    }

    getFeaturedRecipes();
    getNewRecipes();
});