$(document).ready(function() {
    // Event handler for sorting tasks
    $('a[data-sort]').click(function(event) {
        event.preventDefault();

        let sortType = $(this).data('sort');
        let sortOrder = $(this).data('order') === 'ASC' ? 'DESC' : 'ASC';
        $(this).data('order', sortOrder);

        $.ajax({
            type: 'POST',
            url: 'dashboard.php',
            data: {
                sort_type: sortType,
                sort_order: sortOrder
            },
            success: function(response) {
                let data = JSON.parse(response);
                let tasks = data.tasks;
                let comments = data.comments;

                // Update the task list
                let taskList = $('#task-list');
                taskList.empty();
                tasks.forEach(function(task) {
                    let commentList = comments[task.id] || [];
                    let commentHtml = commentList.map(function(comment) {
                        return `<li><strong>${comment.username}:</strong> ${comment.comment} <small>${comment.created_at}</small></li>`;
                    }).join('');

                    taskList.append(`
                        <tr${task.is_overdue ? ' style="background-color: #f8d7da;"' : ''}>
                            <td>${task.title}</td>
                            <td>${task.deadline}</td>
                            <td>${task.description}</td>
                            <td>${task.is_overdue ? 'Overdue' : task.days_remaining + ' days remaining'}</td>
                            <td>
                                <input type="checkbox" class="update-status" data-task-id="${task.id}" ${task.is_done ? 'checked' : ''}>
                            </td>
                            <td>
                                <form class="comment-form" method="POST">
                                    <input type="hidden" name="task_id" value="${task.id}">
                                    <textarea name="comment" placeholder="Add a comment"></textarea>
                                    <button type="submit" class="btn btn-secondary">Add Comment</button>
                                </form>
                                <ul>${commentHtml}</ul>
                            </td>
                            <td>
                                <form method="POST" action="dashboard.php" style="display:inline;">
                                    <input type="hidden" name="delete_task_id" value="${task.id}">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this task?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    `);
                });
            }
        });
    });

    // Event handler for updating task status
    $(document).on('change', '.update-status', function() {
        let taskId = $(this).data('task-id');
        let isDone = $(this).is(':checked');
        
        $.ajax({
            type: 'POST',
            url: 'dashboard.php',
            data: {
                task_id: taskId,
                is_done: isDone
            },
            success: function(response) {
                alert(response);
            }
        });
    });

    // Event handler for adding comments
    $(document).on('submit', '.comment-form', function(event) {
        event.preventDefault();
        
        let form = $(this);
        let formData = form.serialize();
        
        $.ajax({
            type: 'POST',
            url: 'dashboard.php',
            data: formData,
            success: function(response) {
                let data = JSON.parse(response);
                if (data.status === 'success') {
                    let taskId = form.find('input[name="task_id"]').val();
                    $.ajax({
                        type: 'POST',
                        url: 'dashboard.php',
                        data: {
                            sort_type: 'deadline', // default sort type
                            sort_order: 'ASC' // default sort order
                        },
                        success: function(response) {
                            let data = JSON.parse(response);
                            let tasks = data.tasks;
                            let comments = data.comments;
                            
                            // Update the task list
                            let taskList = $('#task-list');
                            taskList.empty();
                            tasks.forEach(function(task) {
                                let commentList = comments[task.id] || [];
                                let commentHtml = commentList.map(function(comment) {
                                    return `<li><strong>${comment.username}:</strong> ${comment.comment} <small>${comment.created_at}</small></li>`;
                                }).join('');

                                taskList.append(`
                                    <tr${task.is_overdue ? ' style="background-color: #f8d7da;"' : ''}>
                                        <td>${task.title}</td>
                                        <td>${task.deadline}</td>
                                        <td>${task.description}</td>
                                        <td>${task.is_overdue ? 'Overdue' : task.days_remaining + ' days remaining'}</td>
                                        <td>
                                            <input type="checkbox" class="update-status" data-task-id="${task.id}" ${task.is_done ? 'checked' : ''}>
                                        </td>
                                        <td>
                                            <form class="comment-form" method="POST">
                                                <input type="hidden" name="task_id" value="${task.id}">
                                                <textarea name="comment" placeholder="Add a comment"></textarea>
                                                <button type="submit" class="btn btn-secondary">Add Comment</button>
                                            </form>
                                            <ul>${commentHtml}</ul>
                                        </td>
                                        <td>
                                            <form method="POST" action="dashboard.php" style="display:inline;">
                                                <input type="hidden" name="delete_task_id" value="${task.id}">
                                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this task?');">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                `);
                            });
                        }
                    });
                } else {
                    alert(data.message);
                }
            }
        });
    });
});