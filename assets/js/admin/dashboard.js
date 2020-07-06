$(function() {
    const webRoot = $('meta[name=web_root]').attr("content");
    const csrfToken = $('meta[name=csrf_token]').attr("content");

    const elements = {
        users: {
            sections: {
                loading: {
                    section: $("#users-loading"),
                    icon: function() { return $("#users-loading-icon") }
                },
                content: $("#users-content")
            },
            fields: {
                total: $("#users-total"),
                admins: $("#users-admins"),
                disabled: $("#users-disabled")
            },
            buttons: {
                add: $("#users-add"),
                view: $("#users-view")
            }
        },
        cc: {
            sections: {
                loading: {
                    section: $("#cc-loading"),
                    icon: function() { return $("#cc-loading-icon") }
                },
                content: $("#cc-content")
            },
            fields: {
                courses: $("#cc-courses"),
                cuisines: $("#cc-cuisines")
            },
            buttons: {
                view: $("#cc-view")
            }
        }
    }

    const onLoadUsersSuccess = function(response) {
        $.each(response.data.counts, function(k, v) {
            elements.users.fields[k].text(v);
        });

        elements.users.sections.loading.section.hide();
        elements.users.sections.content.show();
    };

    const onLoadUsersFail = function(response) {
        elements.users.sections.loading.icon().addClass('fa-exclamation-triangle').removeClass('fa-sync fa-spin');
    }

    const loadUsers = function() {
        $.ajax({
            method: 'GET',
            url: webRoot + "/api/v1/admin/users/count",
            data: {
                csrf_token: csrfToken
            },
            success: onLoadUsersSuccess,
            error: onLoadUsersFail,
        });
    };

    const onLoadCCSuccess = function(response) {
        elements.cc.fields.courses.text(response.data.courses.length);
        elements.cc.fields.cuisines.text(response.data.cuisines.length);

        elements.cc.sections.loading.section.hide();
        elements.cc.sections.content.show();
    }

    const onLoadCCFail = function() {
        elements.cc.sections.loading.icon().addClass('fa-exclamation-triangle').removeClass('fa-sync fa-spin');
    }

    const loadCC = function() {
        $.ajax({
            method: 'GET',
            url: webRoot + "/api/v1/courses-cuisines",
            success: onLoadCCSuccess,
            error: onLoadCCFail,
        });
    }

    loadUsers();
    loadCC();
});