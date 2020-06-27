$(function() {
    const webRoot = $('meta[name=web_root]').attr("content");
    const csrfToken = $('meta[name=csrf_token]').attr("content");

    const sortBySelect = $("#sort-by");
    const searchField = $("#search");

    const usersTableLoading = $("#users-table-loading");
    const usersTable = $("#users-table");
    const usersTableBody = $("#users-table-body");

    const deleteUserModal = $("#delete-user-modal");
    const deleteUserModalUserID = $("#delete-user-modal-userid");
    const deleteUserModalUsername = $("#delete-user-modal-username");

    const deleteUserButton = $("#delete-user-button");
    const deleteUserButtonIcon = function() { return $("#delete-user-button-icon"); };

    let language;

    /**
     *
     * @param {string} str
     * @returns {boolean}
     */
    function isEmpty(str) {
        return (!str || str.length === 0);
    }

    function fullName(firstName, lastName) {
        if (isEmpty(firstName) && isEmpty(lastName)) {
            return "";
        }
        if (isEmpty(lastName)) {
            return firstName;
        }
        if (isEmpty(firstName)) {
            return lastName;
        }
        return firstName+" "+lastName;
    }

    function formatTime(time) {
        if (!time) {
            return "<i>never</i>";
        }
        let d = new Date(time);
        return d.toLocaleString();
    }

    function placeCheckMark(state) {
        if (state) {
            return "<i class=\"fas fa-check\"></i>";
        }
        return "";
    }

    function placeAdminIcon(state) {
        if (state) {
            return "<i class=\"fas fa-inverse fa-wrench admin-icon\" data-fa-transform=\"shrink-8 up-4 right-9\"></i>";
        }
        return "";
    }

    function placeDisabledIcon(state) {
        if (state) {
            return "<i class=\"fas fa-inverse fa-times disabled-icon\" data-fa-transform=\"shrink-6 up-4 left-9\"></i>";
        }
        return "";
    }

    function placeDisabledProperty(state) {
        if (state) {
            return "disabled=\"disabled\"";
        }
        return "";
    }


    function buildUsersTable(search = null, sort_by = "username") {
        usersTableBody.html(null);

        $.ajax({
            method: "GET",
            url: webRoot + "/api/v1/admin/users",
            data: {
                csrf_token: csrfToken,
                search: search,
                sort_by: sort_by
            },
            /**
             * @param {Object} response
             */
            success: function(response) {
                /**
                 * @param {Object} elem
                 * @param {string} elem.id
                 * @param {string} elem.username
                 * @param {string} elem.first_name
                 * @param {string} elem.last_name
                 * @param {string} elem.last_login
                 * @param {boolean} elem.is_admin
                 * @param {boolean} elem.is_disabled
                 * @param {boolean} elem.is_self
                 * @param {string} language.ADMIN_USERS_EDIT_BUTTON
                 * @param {string} language.ADMIN_USERS_DELETE_BUTTON
                 */
                $.each(response.data.users, function(idx, elem) {
                    let row =
                        "<tr>" +
                        "<td><span class=\"fa-layers fa-fw\">" +
                            "<i class=\"fas fa-user-alt\"></i>"+placeAdminIcon(elem.is_admin)+placeDisabledIcon(elem.is_disabled)+
                        "</span></td>" +
                        "<th scope=\"row\">"+elem.username+"</th>" +
                        "<td>"+fullName(elem.first_name, elem.last_name)+"</td>" +
                        "<td>"+formatTime(elem.last_login)+"</td>" +
                        "<td class=\"fit icon-col\">"+placeCheckMark(elem.is_admin)+"</td>" +
                        "<td class=\"fit icon-col\">"+placeCheckMark(!elem.is_disabled)+"</td>" +
                        "<td class=\"fit user-action-buttons\">" +
                        "<a class=\"btn btn-primary mr-1 btn-edit\" href=\""+webRoot+"/admin/user/"+elem.username+"\" title=\""+language.ADMIN_USERS_EDIT_BUTTON+"\" role=\"button\"><i class=\"fas fa-pencil-alt\"></i></a>" +
                        "<button class=\"btn btn-danger btn-delete\" title=\""+language.ADMIN_USERS_DELETE_BUTTON+"\" data-toggle=\"modal\" data-target=\"#delete-user-modal\" data-userid=\""+elem.id+"\" data-username=\""+elem.username+"\" "+placeDisabledProperty(elem.is_self)+"><i class=\"fas fa-trash-alt\"></i></button>" +
                        "</td>" +
                        "</tr>";
                    usersTableBody.append(row);
                });
                usersTableLoading.hide();
                usersTable.show();
            }
        });
    }

    function loadTable() {
        usersTable.hide();
        usersTableLoading.show();

        $.ajax({
            method: "GET",
            url: webRoot + "/api/v1/language",
            success: function(response) {
                language = response.data;
                buildUsersTable(searchField.val(), sortBySelect.val());
            }
        });
    }

    searchField
        .on('keypress', function(e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                loadTable();
            }
        })
        .on('focus', function() {
            $(this).select();
        });

    sortBySelect.on('change', function() {
        loadTable();
    });

    deleteUserModal.on('show.bs.modal', function(event) {
        let userID = $(event.relatedTarget).data('userid');
        let username = $(event.relatedTarget).data('username');
        deleteUserModalUserID.val(userID);
        deleteUserModalUsername.text(username);
    });

    deleteUserButton.on('click', function() {
        deleteUserButton.prop('disabled', true);
        deleteUserButtonIcon().removeClass('fa-trash-alt').addClass('fa-sync fa-spin');
        let userID = deleteUserModalUserID.val();

        $.ajax({
            method: "POST",
            url: webRoot + '/api/v1/admin/users/delete',
            data: {
                csrf_token: csrfToken,
                user_id: userID
            },
            success: function() {
                window.location.replace(webRoot + '/admin/users');
            },
            error: function(data) {
                console.log(data);
                alert("Delete failed.");
            }
        });
    });

    loadTable();
});
