/*
* Common JS is used to write code which is generally used for all the UI components
* Specific component related code won't be written here
*/

"use strict";
$(document).ready(function () {
    $('#table_list').on('all.bs.table', function () {
        $('#toolbar').parent().addClass('col-12  col-md-7 col-lg-7 p-0');
    })
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })

    if ($('.permission-tree').length > 0) {
        $(function () {
            $('.permission-tree').on('changed.jstree', function (e, data) {
                // let i, j = [];
                let html = "";
                for (let i = 0, j = data.selected.length; i < j; i++) {
                    let permissionName = data.instance.get_node(data.selected[i]).data.name;
                    if (permissionName) {
                        html += "<input type='hidden' name='permission[]' value='" + permissionName + "'/>"
                    }
                }
                $('#permission-list').html(html);
            }).jstree({
                "plugins": ["checkbox"],
            });
        });
    }
})
//Setup CSRF Token default in AJAX Request
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Global variables
var delete_confirmation_text = 'Are you sure you want to delete this item?';

$('#create-form,.create-form,.create-form-without-reset').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');

    let data = new FormData(this);
    let preSubmitFunction = $(this).data('pre-submit-function');
    if (preSubmitFunction) {
        //If custom function name is set in the Form tag then call that function using eval
        if (eval(preSubmitFunction + "()") == false) {
            return false;
        }
    }
    let customSuccessFunction = $(this).data('success-function');

    // noinspection JSUnusedLocalSymbols

    function successCallback(response) {
        if (!$(formElement).hasClass('create-form-without-reset')) {
            formElement[0].reset();
            $(".select2").val("").trigger('change');
            $('.filepond').filepond('removeFile')
        }
        $('#table_list').bootstrapTable('refresh');
        if (customSuccessFunction) {
            //If custom function name is set in the Form tag then call that function using eval
            eval(customSuccessFunction + "(response)");
        }
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})

$('#edit-form,.edit-form').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let data = new FormData(this);
    $(formElement).parents('modal').modal('hide');
    // let url = $(this).attr('action') + "/" + data.get('edit_id');
    let url = $(this).attr('action');
    let preSubmitFunction = $(this).data('pre-submit-function');
    if (preSubmitFunction) {
        //If custom function name is set in the Form tag then call that function using eval
        eval(preSubmitFunction + "()");
    }
    let customSuccessFunction = $(this).data('success-function');

    // noinspection JSUnusedLocalSymbols
    function successCallback(response) {
        $('#table_list').bootstrapTable('refresh');
        setTimeout(function () {
            $('#editModal').modal('hide');
            $(formElement).parents('.modal').modal('hide');
        }, 1000)
        if (customSuccessFunction) {
            //If custom function name is set in the Form tag then call that function using eval
            eval(customSuccessFunction + "(response)");
        }
    }

    formAjaxRequest('PUT', url, data, formElement, submitButtonElement, successCallback);
})

$(document).on('click', '.delete-form', function (e) {
    e.preventDefault();
    showDeletePopupModal($(this).attr('href'), {
        successCallBack: function () {
            $('#table_list').bootstrapTable('refresh');
        }, errorCallBack: function (response) {
            // showErrorToast(response.message);
        }
    })
})

$(document).on('click', '.restore-data', function (e) {
    e.preventDefault();
    showRestorePopupModal($(this).attr('href'), {
        successCallBack: function () {
            $('#table_list').bootstrapTable('refresh');
        }
    })
})

$(document).on('click', '.trash-data', function (e) {
    e.preventDefault();
    showPermanentlyDeletePopupModal($(this).attr('href'), {
        successCallBack: function () {
            $('#table_list').bootstrapTable('refresh');
        }
    })
})

$(document).on('click', '.set-form-url', function (e) {
    //This event will be called when user clicks on the edit button of the bootstrap table
    e.preventDefault();
    $('#edit-form,.edit-form').attr('action', $(this).attr('href'));
})

$(document).on('click', '.delete-form-reload', function (e) {
    e.preventDefault();
    showDeletePopupModal($(this).attr('href'), {
        successCallBack: function () {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    })
})

// Handler for ajax delete buttons
$(document).on('click', '.delete-btn', function(e) {
    e.preventDefault();
    const url = $(this).attr('href');
    
    if (confirm(delete_confirmation_text)) {
        $.ajax({
            url: url,
            type: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message || 'Item deleted successfully');
                    // Refresh the page instead of just the table
                    window.location.reload();
                } else {
                    alert(response.message || 'Failed to delete item');
                }
            },
            error: function(xhr) {
                alert('An error occurred while deleting the item.');
            }
        });
    }
});

// Change event for Status toggle change in Bootstrap-table
$(document).on('change', '.update-status', function () {
    let tableElement = $(this).parents('table');
    let url = $(tableElement).data('custom-status-change-url') || window.baseurl + "common/change-status";
    ajaxRequest('PUT', url, {
        id: $(this).attr('id'),
        table: $(tableElement).data('table'),
        column: $(tableElement).data('status-column') || "",
        status: $(this).is(':checked') ? 1 : 0
    }, null, function (response) {
        showSuccessToast(response.message);
    }, function (error) {
        showErrorToast(error.message);
    })
})


//Fire Ajax request when the Bootstrap-table rows are rearranged
$('#table_list').on('reorder-row.bs.table', function (element, rows) {
    let url = $(element.currentTarget).data('custom-reorder-row-url') || window.baseurl + "common/change-row-order";
    ajaxRequest('PUT', url, {
        table: $(element.currentTarget).data('table'),
        column: $(element.currentTarget).data('reorder-column') || "",
        data: rows
    }, null, function (success) {
        $('#table_list').bootstrapTable('refresh');
        showSuccessToast(success.message);
    }, function (error) {
        showErrorToast(error.message);
    })
})


$('.img_input').click(function () {
    $('#edit_cs_image').click();
});
$('.preview-image-file').on('change', function () {
    const [file] = this.files
    if (file) {
        $('.preview-image').attr('src', URL.createObjectURL(file));
    }
})


$('.form-redirection').on('submit', function (e) {
    let parsley = $(this).parsley({
        excluded: 'input[type=button], input[type=submit], input[type=reset], :hidden'
    });
    parsley.validate();
    if (parsley.isValid()) {
        $(this).find(':submit').attr('disabled', true);
    }
})

$('#editlanguage-form,.editlanguage-form').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let data = new FormData(this);
    $(formElement).parents('modal').modal('hide');
    // let url = $(this).attr('action') + "/" + data.get('edit_id');
    let url = $(this).attr('action');
    let preSubmitFunction = $(this).data('pre-submit-function');
    if (preSubmitFunction) {
        //If custom function name is set in the Form tag then call that function using eval
        eval(preSubmitFunction + "()");
    }
    let customSuccessFunction = $(this).data('success-function');

    // noinspection JSUnusedLocalSymbols
    function successCallback(response) {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
    }
    formAjaxRequest('PUT', url, data, formElement, submitButtonElement, successCallback);
})
