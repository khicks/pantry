$(function() {
    const webRoot = $('meta[name=web_root]').attr("content");
    const csrfToken = $('meta[name=csrf_token]').attr("content");

    const coursesSearchField = $("#courses-search");
    const coursesSortByField = $("#courses-sort-by");
    const coursesTableBody = $("#courses-table-body");

    const loadCoursesTable = function() {
        $.ajax({
            method: "GET",
            url: webRoot + "/api/v1/courses",
            data: {
                csrf_token: csrfToken,
                search: coursesSearchField.val(),
                sort_by: coursesSortByField.val()
            },
            success: function(response) {
                $.each(response.data.courses, function(idx, elem) {
                    let row =
                        "<tr>" +
                        "<th scope=\"row\">"+elem.title+"</th>" +
                        "<td>"+elem.slug+"</td>" +
                        "<td class=\"fit action-buttons\">" +
                        "<a class=\"btn btn-primary mr-1 btn-edit\" href=\""+webRoot+"/admin/courses/edit/"+elem.slug+"\" title=\""+"EDIT"+"\" role=\"button\"><i class=\"fas fa-pencil-alt\"></i></a>" +
                        "<button class=\"btn btn-danger btn-delete\" title=\""+"DELETE"+"\" data-toggle=\"modal\" data-target=\"#delete-user-modal\" data-id=\""+elem.id+"\" data-title=\""+elem.title+"\"><i class=\"fas fa-trash-alt\"></i></button>" +
                        "</td>" +
                        "</tr>";
                    coursesTableBody.append(row);
                });
            }
        });
    };

    const loadCuisinesTable = function() {

    };

    loadCoursesTable();
    loadCuisinesTable();
});
