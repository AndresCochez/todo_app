$(document).ready(function() {
    $('.update-status').change(function() {
        var task_id = $(this).data('task-id');
        var is_done = $(this).is(':checked') ? 1 : 0;

        $.ajax({
            url: 'update_status.php',
            method: 'POST',
            data: {
                task_id: task_id,
                is_done: is_done
            },
            success: function(response) {
                console.log(response);
            },
            error: function(xhr, status, error) {
                console.error(error);
            }
        });
    });
});