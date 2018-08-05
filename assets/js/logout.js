$(function() {
    const logoutButton = $("#logout-button");

    function logout() {
        let webRoot = $('meta[name=web_root]').attr("content");

        $.ajax({
            method: "GET",
            url: "api/v1/me",
            /**
             * @param response.data.logged_in
             */
            success: function(response) {
                if (response.data.logged_in) {
                    $.ajax({
                        method: "POST",
                        url: "api/v1/logout",
                        data: {
                            csrf_token: response.data.csrf_token,
                        },
                        success: function() {
                            window.location.replace(webRoot);
                        },
                        error: function(data) {
                            console.log(data);
                            alert("Unable to log out.");
                        }
                    });
                }
                else {
                    window.location.replace(webRoot);
                }
            },
            error: function() {
                console.log(data);
                alert("Unable to log out.");
            }
        });
    }

    logoutButton.on('click', function() {
        logout();
    })
});
