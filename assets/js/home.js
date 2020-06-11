$(function() {
    const webRoot = $("meta[name=web_root]").attr("content");

    const elements = {
        loadingWheels: {
            featured: $("#featured-loading"),
            newRecipes: $("#new-loading"),
        }
    }

    const onFeaturedLoad = function(response) {
        console.log(response);
    }

    const onFeaturedFail = function() {}

    const getFeaturedRecipes = function() {
        $.ajax({
            method: "GET",
            url: webRoot + "/api/v1/recipes/featured",
            success: onFeaturedLoad,
            error: onFeaturedFail
        });
    }

    getFeaturedRecipes();
});