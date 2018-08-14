$(function() {
    const webRoot = $('meta[name=web_root]').attr("content");
    /**
     * @param {string} jqxhr.responseJSON.code
     */
    $(document).ajaxError(function(event, jqxhr) {
        let logoutReasons = ["NOT_LOGGED_IN", "CSRF_FAILED"];
        if (logoutReasons.indexOf(jqxhr.responseJSON.code) >= 0) {
            window.location.replace(webRoot + '/login');
        }

        if (jqxhr.responseJSON.code === "NOT_ADMIN") {
            window.location.replace(webRoot);
        }
    });
});
