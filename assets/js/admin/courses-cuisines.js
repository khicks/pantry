$(function() {
    const webRoot = $('meta[name=web_root]').attr("content");
    const csrfToken = $('meta[name=csrf_token]').attr("content");

    let courses = [];
    let cuisines = [];

    let elements = {
        loadingWheels: {
            tables: $("#tables-loading")
        },
        buttons: {
            modalActions: $("div.modal button")
        },
        tables: {
            courses: $("#courses-table-body"),
            cuisines: $("#cuisines-table-body"),
        },
        templates: {
            ccRow: Handlebars.compile($("#cc-row").html()),
            selectFieldNone: Handlebars.compile($("#select-field-none").html())
        },
        modals: {
            all: $("div.modal"),
            courses: {
                create: $("#create-course-modal"),
                edit: $("#edit-course-modal"),
                delete: $("#delete-course-modal")
            },
            cuisines: {
                create: $("#create-cuisine-modal"),
                edit: $("#edit-cuisine-modal"),
                delete: $("#delete-cuisine-modal")
            }
        },
        alerts: {
            all: $(".alert-failed"),
            courses: {
                edit: $("#edit-course-failed"),
                delete: $("#delete-course-failed")
            },
            cuisines: {
                edit: $("#edit-cuisine-failed"),
                delete: $("#delete-cuisine-failed")
            },
        },
        forms: {
            courses: {
                create: {
                    button: {
                        button: $("#create-course-button"),
                        icon: function() { return $("#create-course-button-icon") }
                    },
                    fields: {
                        title: {
                            field: $("#create-course-title"),
                            icon: function() { return $("#create-course-title-feedback-icon") },
                            messages: {
                                api: $("#create-course-title-feedback-message-api"),
                            }
                        },
                        slug: {
                            field: $("#create-course-slug"),
                            icon: function() { return $("#create-course-slug-feedback-icon") },
                            messages: {
                                api: $("#create-course-slug-feedback-message-api"),
                            }
                        }
                    }
                },
                edit: {
                    button: {
                        button: $("#edit-course-button"),
                        icon: function() { return $("#edit-course-button-icon") }
                    },
                    fields: {
                        id: {
                            field: $("#edit-course-id"),
                        },
                        title: {
                            field: $("#edit-course-title"),
                            icon: function() { return $("#edit-course-title-feedback-icon") },
                            messages: {
                                api: $("#edit-course-title-feedback-message-api"),
                            }
                        },
                        slug: {
                            field: $("#edit-course-slug"),
                            icon: function() { return $("#edit-course-slug-feedback-icon") },
                            messages: {
                                api: $("#edit-course-slug-feedback-message-api"),
                            }
                        }
                    }
                },
                delete: {
                    button: {
                        button: $("#delete-course-button"),
                        icon: function() { return $("#delete-course-button-icon") }
                    },
                    fields: {
                        id: {
                            field: $("#delete-course-id")
                        },
                        title: {
                            field: $("#delete-course-title")
                        },
                        replace: {
                            field: $("#delete-course-replace")
                        }
                    }
                }
            },
            cuisines: {
                create: {
                    button: {
                        button: $("#create-cuisine-button"),
                        icon: function() { return $("#create-cuisine-button-icon") }
                    },
                    fields: {
                        title: {
                            field: $("#create-cuisine-title"),
                            icon: function() { return $("#create-cuisine-title-feedback-icon") },
                            messages: {
                                api: $("#create-cuisine-title-feedback-message-api"),
                            }
                        },
                        slug: {
                            field: $("#create-cuisine-slug"),
                            icon: function() { return $("#create-cuisine-slug-feedback-icon") },
                            messages: {
                                api: $("#create-cuisine-slug-feedback-message-api"),
                            }
                        }
                    }
                },
                edit: {
                    button: {
                        button: $("#edit-cuisine-button"),
                        icon: function() { return $("#edit-cuisine-button-icon") }
                    },
                    fields: {
                        id: {
                            field: $("#edit-cuisine-id"),
                        },
                        title: {
                            field: $("#edit-cuisine-title"),
                            icon: function() { return $("#edit-cuisine-title-feedback-icon") },
                            messages: {
                                api: $("#edit-cuisine-title-feedback-message-api"),
                            }
                        },
                        slug: {
                            field: $("#edit-cuisine-slug"),
                            icon: function() { return $("#edit-cuisine-slug-feedback-icon") },
                            messages: {
                                api: $("#edit-cuisine-slug-feedback-message-api"),
                            }
                        }
                    }
                },
                delete: {
                    button: {
                        button: $("#delete-cuisine-button"),
                        icon: function() { return $("#delete-cuisine-button-icon") }
                    },
                    fields: {
                        id: {
                            field: $("#delete-cuisine-id")
                        },
                        title: {
                            field: $("#delete-cuisine-title")
                        },
                        replace: {
                            field: $("#delete-cuisine-replace")
                        }
                    }
                }
            }
        },
        fields: {
            all: $("input[type=text]:not(.no-edit), select"),
        }
    }

    const slugify = function(string) {
        const a = 'àáâäæãåāăąçćčđďèéêëēėęěğǵḧîïíīįìłḿñńǹňôöòóœøōõőṕŕřßśšşșťțûüùúūǘůűųẃẍÿýžźż·/_,:;'
        const b = 'aaaaaaaaaacccddeeeeeeeegghiiiiiilmnnnnoooooooooprrsssssttuuuuuuuuuwxyyzzz------'
        const p = new RegExp(a.split('').join('|'), 'g')

        return string.toString().toLowerCase()
            .replace(/\s+/g, '-') // Replace spaces with -
            .replace(p, c => b.charAt(a.indexOf(c))) // Replace special characters
            .replace(/&/g, '-and-') // Replace & with 'and'
            .replace(/[^\w\-]+/g, '') // Remove all non-word characters
            .replace(/--+/g, '-') // Replace multiple - with single -
            .substr(0, 40) // Truncate to 40 characters
            .replace(/^-+/, '') // Trim - from start of text
            .replace(/-+$/, '') // Trim - from end of text
    }

    // Load CC tables
    const onLoadCCTables = function(response) {
        courses = response.data.courses;
        cuisines = response.data.cuisines;

        $.each(response.data.courses, function(idx, course) {
            elements.tables.courses.append(elements.templates.ccRow({
                type: "course",
                type_plural: "courses",
                item: course
            }));
        });

        $.each(response.data.cuisines, function(idx, cuisine) {
            elements.tables.cuisines.append(elements.templates.ccRow({
                type: "cuisine",
                type_plural: "cuisines",
                item: cuisine
            }));
        });

        elements.loadingWheels.tables.hide();
    };

    const onLoadCCTablesError = function() {
        alert("Could not load tables.");
    };

    const loadCCTables = function() {
        $.ajax({
            method: "GET",
            url: webRoot + "/api/v1/courses-cuisines",
            data: {
                csrf_token: csrfToken
            },
            success: onLoadCCTables,
            error: onLoadCCTablesError
        });
    };

    const clearFieldErrors = function() {
        $(this).removeClass('is-invalid');
        $(this).siblings('label').children('.field-feedback-icon').removeClass('fa-times field-feedback-red').hide();
    }

    const createCourse = function() {
        elements.fields.all.prop('disabled', true);
        elements.buttons.modalActions.prop('disabled', true);
        elements.forms.courses.create.button.icon().removeClass('fa-plus').addClass('fa-sync fa-spin');

        const onCreateCourseSuccess = function() {
            elements.forms.courses.create.button.icon().removeClass('fa-sync fa-spin').addClass('fa-check');
            location.reload();
        }

        const onCreateCourseError = function (response) {
            elements.fields.all.prop('disabled', false);
            elements.buttons.modalActions.prop('disabled', false);
            elements.forms.courses.create.button.icon().removeClass('fa-sync fa-spin').addClass('fa-plus');

            if (!response.responseJSON) {
                alert("Save failed. Unknown error.");
                console.log(response);
                return;
            }

            let error = response.responseJSON;
            if (error.data.issue && error.data.issue === "validation") {
                elements.forms.courses.create.fields[error.data.field].field.addClass('is-invalid');
                elements.forms.courses.create.fields[error.data.field].icon().addClass('fa-times field-feedback-red').show();
                elements.forms.courses.create.fields[error.data.field].messages.api.text(error.description).show();
            }
        };

        $.ajax({
            method: "POST",
            url: webRoot + "/api/v1/admin/courses/create",
            data: {
                csrf_token: csrfToken,
                title: elements.forms.courses.create.fields.title.field.val(),
                slug: elements.forms.courses.create.fields.slug.field.val()
            },
            success: onCreateCourseSuccess,
            error: onCreateCourseError
        });
    };

    const editCourse = function() {
        elements.fields.all.prop('disabled', true);
        elements.buttons.modalActions.prop('disabled', true);
        elements.forms.courses.edit.button.icon().removeClass('fa-save').addClass('fa-sync fa-spin');

        const onEditCourseSuccess = function() {
            elements.forms.courses.edit.button.icon().removeClass('fa-sync fa-spin').addClass('fa-check');
            location.reload();
        }

        const onEditCourseError = function (response) {
            elements.fields.all.prop('disabled', false);
            elements.buttons.modalActions.prop('disabled', false);
            elements.forms.courses.edit.button.icon().removeClass('fa-sync fa-spin').addClass('fa-save');

            if (!response.responseJSON) {
                alert("Save failed. Unknown error.");
                console.log(response);
                return;
            }

            let error = response.responseJSON;
            if (error.data.issue && error.data.issue === "validation") {
                elements.forms.courses.edit.fields[error.data.field].field.addClass('is-invalid');
                elements.forms.courses.edit.fields[error.data.field].icon().addClass('fa-times field-feedback-red').show();
                elements.forms.courses.edit.fields[error.data.field].messages.api.text(error.description).show();
                return;
            }

            elements.alerts.courses.edit.text(error.description).show();
        };

        $.ajax({
            method: "POST",
            url: webRoot + "/api/v1/admin/courses/edit",
            data: {
                csrf_token: csrfToken,
                id: elements.forms.courses.edit.fields.id.field.val(),
                title: elements.forms.courses.edit.fields.title.field.val(),
                slug: elements.forms.courses.edit.fields.slug.field.val()
            },
            success: onEditCourseSuccess,
            error: onEditCourseError
        });
    };

    const deleteCourse = function() {
        elements.fields.all.prop('disabled', true);
        elements.buttons.modalActions.prop('disabled', true);
        elements.forms.courses.delete.button.icon().removeClass('fa-trash-alt').addClass('fa-sync fa-spin');

        const onDeleteCourseSuccess = function() {
            elements.forms.courses.delete.button.icon().removeClass('fa-sync fa-spin').addClass('fa-check');
            location.reload();
        }

        const onDeleteCourseError = function (response) {
            elements.fields.all.prop('disabled', false);
            elements.buttons.modalActions.prop('disabled', false);
            elements.forms.courses.delete.button.icon().removeClass('fa-sync fa-spin').addClass('fa-trash-alt');

            if (!response.responseJSON) {
                alert("Delete failed. Unknown error.");
                console.log(response);
                return;
            }

            elements.alerts.courses.delete.text(response.responseJSON.description).show();
        };

        $.ajax({
            method: "POST",
            url: webRoot + "/api/v1/admin/courses/delete",
            data: {
                csrf_token: csrfToken,
                id: elements.forms.courses.delete.fields.id.field.val(),
                replace_id: elements.forms.courses.delete.fields.replace.field.val()
            },
            success: onDeleteCourseSuccess,
            error: onDeleteCourseError
        });
    };

    const createCuisine = function() {
        elements.fields.all.prop('disabled', true);
        elements.buttons.modalActions.prop('disabled', true);
        elements.forms.cuisines.create.button.icon().removeClass('fa-plus').addClass('fa-sync fa-spin');

        const onCreateCuisineSuccess = function() {
            elements.forms.cuisines.create.button.icon().removeClass('fa-sync fa-spin').addClass('fa-check');
            location.reload();
        }

        const onCreateCuisineError = function (response) {
            elements.fields.all.prop('disabled', false);
            elements.buttons.modalActions.prop('disabled', false);
            elements.forms.cuisines.create.button.icon().removeClass('fa-sync fa-spin').addClass('fa-plus');

            if (!response.responseJSON) {
                alert("Save failed. Unknown error.");
                console.log(response);
                return;
            }

            let error = response.responseJSON;
            if (error.data.issue && error.data.issue === "validation") {
                elements.forms.cuisines.create.fields[error.data.field].field.addClass('is-invalid');
                elements.forms.cuisines.create.fields[error.data.field].icon().addClass('fa-times field-feedback-red').show();
                elements.forms.cuisines.create.fields[error.data.field].messages.api.text(error.description).show();
            }
        };

        $.ajax({
            method: "POST",
            url: webRoot + "/api/v1/admin/cuisines/create",
            data: {
                csrf_token: csrfToken,
                title: elements.forms.cuisines.create.fields.title.field.val(),
                slug: elements.forms.cuisines.create.fields.slug.field.val()
            },
            success: onCreateCuisineSuccess,
            error: onCreateCuisineError
        });
    };

    const editCuisine = function() {
        elements.fields.all.prop('disabled', true);
        elements.buttons.modalActions.prop('disabled', true);
        elements.forms.cuisines.edit.button.icon().removeClass('fa-save').addClass('fa-sync fa-spin');

        const onEditCuisineSuccess = function() {
            elements.forms.cuisines.edit.button.icon().removeClass('fa-sync fa-spin').addClass('fa-check');
            location.reload();
        }

        const onEditCuisineError = function (response) {
            elements.fields.all.prop('disabled', false);
            elements.buttons.modalActions.prop('disabled', false);
            elements.forms.cuisines.edit.button.icon().removeClass('fa-sync fa-spin').addClass('fa-save');

            if (!response.responseJSON) {
                alert("Save failed. Unknown error.");
                console.log(response);
                return;
            }

            let error = response.responseJSON;
            if (error.data.issue && error.data.issue === "validation") {
                elements.forms.cuisines.edit.fields[error.data.field].field.addClass('is-invalid');
                elements.forms.cuisines.edit.fields[error.data.field].icon().addClass('fa-times field-feedback-red').show();
                elements.forms.cuisines.edit.fields[error.data.field].messages.api.text(error.description).show();
                return;
            }

            elements.alerts.cuisines.edit.text(error.description).show();
        };

        $.ajax({
            method: "POST",
            url: webRoot + "/api/v1/admin/cuisines/edit",
            data: {
                csrf_token: csrfToken,
                id: elements.forms.cuisines.edit.fields.id.field.val(),
                title: elements.forms.cuisines.edit.fields.title.field.val(),
                slug: elements.forms.cuisines.edit.fields.slug.field.val()
            },
            success: onEditCuisineSuccess,
            error: onEditCuisineError
        });
    };

    const deleteCuisine = function() {
        elements.fields.all.prop('disabled', true);
        elements.buttons.modalActions.prop('disabled', true);
        elements.forms.cuisines.delete.button.icon().removeClass('fa-trash-alt').addClass('fa-sync fa-spin');

        const onDeleteCuisineSuccess = function() {
            elements.forms.cuisines.delete.button.icon().removeClass('fa-sync fa-spin').addClass('fa-check');
            location.reload();
        }

        const onDeleteCuisineError = function (response) {
            elements.fields.all.prop('disabled', false);
            elements.buttons.modalActions.prop('disabled', false);
            elements.forms.cuisines.delete.button.icon().removeClass('fa-sync fa-spin').addClass('fa-trash-alt');

            if (!response.responseJSON) {
                alert("Delete failed. Unknown error.");
                console.log(response);
                return;
            }

            elements.alerts.cuisines.delete.text(response.responseJSON.description).show();
        };

        $.ajax({
            method: "POST",
            url: webRoot + "/api/v1/admin/cuisines/delete",
            data: {
                csrf_token: csrfToken,
                id: elements.forms.cuisines.delete.fields.id.field.val(),
                replace_id: elements.forms.cuisines.delete.fields.replace.field.val()
            },
            success: onDeleteCuisineSuccess,
            error: onDeleteCuisineError
        });
    };

    // Modal event handlers
    elements.modals.all.on('hidden.bs.modal', function() {
        elements.fields.all.val("").prop('disabled', true);
        elements.alerts.all.hide();
    });
    elements.modals.all.on('show.bs.modal', function() {
        elements.fields.all.prop('disabled', false);
    });
    elements.modals.all.on('shown.bs.modal', function(event) {
        let type = $(event.relatedTarget).data('type');
        let action = $(event.relatedTarget).data('action');
        elements.forms[type][action].fields.title.field.focus();
        $(document).on('keypress', function(e) {
            if ($(event.delegateTarget).hasClass('show') && e.which === 13) {
                elements.forms[type][action].button.button.click();
            }
        });
    });

    elements.modals.courses.edit.on('show.bs.modal', function(event) {
        elements.forms.courses.edit.fields.id.field.val($(event.relatedTarget).data('id'));
        elements.forms.courses.edit.fields.title.field.val($(event.relatedTarget).data('title'));
        elements.forms.courses.edit.fields.slug.field.val($(event.relatedTarget).data('slug'));
    });

    elements.modals.courses.delete.on('show.bs.modal', function(event) {
        let courseID = $(event.relatedTarget).data('id')
        elements.forms.courses.delete.fields.id.field.val(courseID);
        elements.forms.courses.delete.fields.title.field.val($(event.relatedTarget).data('title'));
        elements.forms.courses.delete.fields.replace.field.append(elements.templates.selectFieldNone());
        $.each(courses, function(idx, course) {
            if (course.id !== courseID) {
                elements.forms.courses.delete.fields.replace.field.append($('<option>').val(course.id).append(course.title));
            }
        });
    });

    elements.modals.courses.delete.on('hidden.bs.modal', function() {
        elements.forms.courses.delete.fields.replace.field.empty();
    });

    elements.modals.cuisines.edit.on('show.bs.modal', function(event) {
        elements.forms.cuisines.edit.fields.id.field.val($(event.relatedTarget).data('id'));
        elements.forms.cuisines.edit.fields.title.field.val($(event.relatedTarget).data('title'));
        elements.forms.cuisines.edit.fields.slug.field.val($(event.relatedTarget).data('slug'));
    });

    elements.modals.cuisines.delete.on('show.bs.modal', function(event) {
        let cuisineID = $(event.relatedTarget).data('id')
        elements.forms.cuisines.delete.fields.id.field.val(cuisineID);
        elements.forms.cuisines.delete.fields.title.field.val($(event.relatedTarget).data('title'));
        elements.forms.cuisines.delete.fields.replace.field.append(elements.templates.selectFieldNone());
        $.each(cuisines, function(idx, cuisine) {
            if (cuisine.id !== cuisineID) {
                elements.forms.cuisines.delete.fields.replace.field.append($('<option>').val(cuisine.id).append(cuisine.title));
            }
        });
    });

    elements.modals.cuisines.delete.on('hidden.bs.modal', function() {
        elements.forms.cuisines.delete.fields.replace.field.empty();
    });

    // Form event handlers
    elements.forms.courses.create.button.button.on('click', createCourse);
    elements.forms.courses.edit.button.button.on('click', editCourse);
    elements.forms.courses.delete.button.button.on('click', deleteCourse);
    elements.forms.cuisines.create.button.button.on('click', createCuisine);
    elements.forms.cuisines.edit.button.button.on('click', editCuisine);
    elements.forms.cuisines.delete.button.button.on('click', deleteCuisine);

    elements.forms.courses.create.fields.title.field.on('propertychange change keyup input paste', function() {
        elements.forms.courses.create.fields.slug.field.val(
            slugify(elements.forms.courses.create.fields.title.field.val())
        )
    });

    elements.forms.cuisines.create.fields.title.field.on('propertychange change keyup input paste', function() {
        elements.forms.cuisines.create.fields.slug.field.val(
            slugify(elements.forms.cuisines.create.fields.title.field.val())
        )
    });

    elements.fields.all.on('input propertychange', clearFieldErrors);

    // Page init
    loadCCTables();
});
