$(function() {
    const webRoot = $('meta[name=web_root]').attr("content");
    const csrfToken = $('meta[name=csrf_token]').attr("content");

    const tablesLoadingWheel = $("#tables-loading");

    const coursesSearchField = $("#courses-search");
    const coursesSortByField = $("#courses-sort-by");
    const coursesTableBody = $("#courses-table-body");
    const cuisinesTableBody = $("#cuisines-table-body");

    const deleteCourseModal = $("#delete-course-modal");
    const deleteCuisineModal = $("#delete-cuisine-modal");

    const loadCCTables = function(response) {
        $.each(response.data.courses, function(idx, course) {
            coursesTableBody.append($('<tr>')
                .append($('<th>')
                    .attr('scope', 'row')
                    .text(course.title)
                )
                .append($('<td>')
                    .text(course.slug)
                )
                .append($('<td>')
                    .addClass("fit action-buttons")
                    .append($('<a>')
                        .addClass("btn btn-primary mr-1 btn-edit")
                        .attr('href', webRoot + "/admin/courses/" + course.slug + "/edit")
                        .attr('role', "button")
                        .append($('<i>')
                            .addClass("fas fa-pencil-alt")
                        )
                    )
                    .append($('<button>')
                        .addClass("btn btn-danger btn-delete")
                        .attr('data-toggle', "modal")
                        .attr('data-target', "#delete-course-modal")
                        .attr('data-id', course.id)
                        .attr('data-title', course.title)
                        .append($('<i>')
                            .addClass("fas fa-trash-alt")
                        )
                    )
                )
            );
        });

        $.each(response.data.cuisines, function(idx, cuisine) {
            cuisinesTableBody.append($('<tr>')
                .append($('<th>')
                    .attr('scope', 'row')
                    .text(cuisine.title)
                )
                .append($('<td>')
                    .text(cuisine.slug)
                )
                .append($('<td>')
                    .addClass("fit action-buttons")
                    .append($('<a>')
                        .addClass("btn btn-primary mr-1 btn-edit")
                        .attr('href', webRoot + "/admin/cuisines/" + cuisine.slug + "/edit")
                        .attr('role', "button")
                        .append($('<i>')
                            .addClass("fas fa-pencil-alt")
                        )
                    )
                    .append($('<button>')
                        .addClass("btn btn-danger btn-delete")
                        .attr('data-toggle', "modal")
                        .attr('data-target', "#delete-course-modal")
                        .attr('data-id', cuisine.id)
                        .attr('data-title', cuisine.title)
                        .append($('<i>')
                            .addClass("fas fa-trash-alt")
                        )
                    )
                )
            );
        });

        tablesLoadingWheel.hide();
    };

    const loadCCTablesError = function() {
        alert("Could not load tables.");
    };

    deleteCourseModal.on('show.bs.modal', function(event) {
        let userID = $(event.relatedTarget).data('userid');
        let username = $(event.relatedTarget).data('username');
        $("#delete-course-modal-id").val($(event.relatedTarget).data('id'));
        $("#delete-course-modal-title").text($(event.relatedTarget).data('title'));
    });

    $.ajax({
        method: "GET",
        url: webRoot + "/api/v1/courses-cuisines",
        data: {
            csrf_token: csrfToken
        },
        success: loadCCTables,
        error: loadCCTablesError
    });
});
