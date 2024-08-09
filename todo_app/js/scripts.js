$(document).ready(function() {
    // AJAX-aanroep voor het bijwerken van de taakstatus
    $('.update-status').change(function() {
        var taskId = $(this).data('task-id');
        var isDone = $(this).is(':checked');

        $.ajax({
            url: 'dashboard.php',
            type: 'POST',
            data: {
                task_id: taskId,
                is_done: isDone
            },
            success: function(response) {
                console.log(response);
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    });

    // AJAX-aanroep voor het toevoegen van commentaren
    $('form').submit(function(event) {
        var form = $(this);
        if (form.find('textarea[name="comment"]').length) {
            event.preventDefault();

            $.ajax({
                url: 'dashboard.php',
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    console.log(response);
                    location.reload();  // Herlaad de pagina om nieuwe commentaren te tonen
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        }
    });
});