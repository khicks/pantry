$(function() {
    const webRoot = $('meta[name=web_root]').attr("content");
    const csrfToken = $('meta[name=csrf_token]').attr("content");

    const elements = {
        users: {
            sections: {
                loading: $("#users-loading"),
                content: $("#users-content")
            },
            fields: {
                total: $("#users-total"),
                admins: $("#users-admins"),
                disabled: $("#users-disabled")
            },
            buttons: {
                add: $("#users-add"),
                view: $("#users-view"),
            }
        }
    }

    const onLoadUsersSuccess = function(response) {
        console.log(response);

        $.each(response.data.counts, function(k, v) {
            elements.users.fields[k].text(v);
        });

        elements.users.sections.loading.hide();
        elements.users.sections.content.show();
    };

    const loadUsers = function() {
        $.ajax({
            method: 'GET',
            url: webRoot + "/api/v1/admin/users/count",
            data: {
                csrf_token: csrfToken
            },
            success: onLoadUsersSuccess,
        });
    };

    loadUsers();
});