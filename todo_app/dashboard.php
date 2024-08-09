<?php
session_start();
require 'config/database.php';
require 'classes/ListClass.php';
require 'classes/Task.php';
require 'classes/Comment.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$listClass = new ListClass($db);
$taskClass = new Task($db);
$commentClass = new Comment($db);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['list_name']) && isset($_POST['list_description'])) {
    $listClass->setUserId($_SESSION['user_id']);
    
    try {
        $listClass->setName($_POST['list_name']);
        $listClass->setDescription($_POST['list_description']);

        if ($listClass->create()) {
            header("Location: dashboard.php");
            exit();
        } else {
            echo "Er is een fout opgetreden bij het aanmaken van de lijst.";
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_list_id'])) {
    $list_id = $_POST['delete_list_id'];
    
    if ($listClass->delete($list_id)) {
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Er is een fout opgetreden bij het verwijderen van de lijst.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_task_id'])) {
    $task_id = $_POST['delete_task_id'];
    
    if ($taskClass->delete($task_id)) {
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Er is een fout opgetreden bij het verwijderen van de taak.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['task_title']) && isset($_POST['task_description']) && isset($_POST['task_deadline']) && isset($_POST['list_id'])) {
    $taskClass->setListId($_POST['list_id']);
    $taskClass->setTitle($_POST['task_title']);
    $taskClass->setDescription($_POST['task_description']);
    $taskClass->setDeadline($_POST['task_deadline']);
    
    try {
        if ($taskClass->create()) {
            header("Location: dashboard.php");
            exit();
        } else {
            echo "Er is een fout opgetreden bij het aanmaken van de taak.";
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['task_id']) && isset($_POST['is_done'])) {
    $task_id = $_POST['task_id'];
    $is_done = $_POST['is_done'] == 'true' ? 1 : 0;

    if ($taskClass->updateStatus($task_id, $is_done)) {
        echo "Status bijgewerkt";
    } else {
        echo "Er is een fout opgetreden bij het bijwerken van de status.";
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['task_id']) && isset($_POST['comment'])) {
    $commentClass->setTaskId($_POST['task_id']);
    $commentClass->setUserId($_SESSION['user_id']);
    $commentClass->setComment($_POST['comment']);
    
    try {
        if ($commentClass->create()) {
            header("Location: dashboard.php");
            exit();
        } else {
            echo "Er is een fout opgetreden bij het toevoegen van het commentaar.";
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

$lists = $listClass->fetchAll($_SESSION['user_id']);

$sort_type = isset($_GET['type']) ? $_GET['type'] : 'deadline';
$sort_order = isset($_GET['sort']) ? strtoupper($_GET['sort']) : 'ASC';

$tasks = $taskClass->fetchTasksWithSorting($_SESSION['user_id'], $sort_type, $sort_order);

$tasks_with_comments = [];
foreach ($tasks as $task) {
    $comments = $commentClass->fetchCommentsByTaskId($task['id']);
    $tasks_with_comments[$task['id']] = $comments;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Your Todo Lists</h1>
        
        <!-- Formulier voor het toevoegen van lijsten -->
        <form method="POST" class="form-inline">
            <input type="text" name="list_name" placeholder="New List Name" required>
            <textarea name="list_description" placeholder="List Description" required></textarea>
            <button type="submit" class="btn btn-primary">Create List</button>
        </form>
        
        <!-- Lijsten Weergeven -->
        <?php if (count($lists) > 0): ?>
            <?php foreach ($lists as $list): ?>
                <div>
                    <h2><?php echo htmlspecialchars($list['name']); ?></h2>
                    <p><?php echo htmlspecialchars($list['description']); ?></p>
                    
                    <!-- Formulier voor het toevoegen van taken aan de lijst -->
                    <form method="POST" class="form-inline">
                        <input type="hidden" name="list_id" value="<?php echo $list['id']; ?>">
                        <input type="text" name="task_title" placeholder="Task Title" required>
                        <textarea name="task_description" placeholder="Task Description" required></textarea>
                        <input type="date" name="task_deadline">
                        <button type="submit" class="btn btn-primary">Add Task</button>
                    </form>

                    <!-- Taken Weergeven -->
                    <table class="table mt-3">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Deadline</th>
                                <th>Remaining Days</th>
                                <th>Status</th>
                                <th>Comments</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($taskClass->fetchTasksByListId($list['id']) as $task): ?>
                                <?php
                                $days_remaining = $taskClass->getDaysRemaining($task['deadline']);
                                $is_overdue = $taskClass->isOverdue($task['deadline']);
                                ?>
                                <tr<?php echo $is_overdue ? ' style="background-color: #f8d7da;"' : ''; ?>>
                                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                                    <td><?php echo htmlspecialchars($task['description']); ?></td>
                                    <td><?php echo htmlspecialchars($task['deadline']); ?></td>
                                    <td><?php echo $is_overdue ? 'Overdue' : $days_remaining . ' days remaining'; ?></td>
                                    <td>
                                        <input type="checkbox" class="update-status" data-task-id="<?php echo $task['id']; ?>" <?php echo $task['is_done'] ? 'checked' : ''; ?>>
                                    </td>
                                    <td>
                                        <!-- Formulier voor het toevoegen van commentaren -->
                                        <form method="POST" class="form-inline">
                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                            <textarea name="comment" placeholder="Add a comment"></textarea>
                                            <button type="submit" class="btn btn-secondary">Add Comment</button>
                                        </form>
                                        
                                        <!-- Commentaren weergeven -->
                                        <?php if (isset($tasks_with_comments[$task['id']])): ?>
                                            <ul>
                                                <?php foreach ($tasks_with_comments[$task['id']] as $comment): ?>
                                                    <li><strong><?php echo htmlspecialchars($comment['username']); ?>:</strong> <?php echo htmlspecialchars($comment['comment']); ?> <small><?php echo htmlspecialchars($comment['created_at']); ?></small></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <!-- Verwijderknop voor de taak -->
                                        <form method="POST" action="dashboard.php" style="display:inline;">
                                            <input type="hidden" name="delete_task_id" value="<?php echo $task['id']; ?>">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('Weet je zeker dat je deze taak wilt verwijderen?');">Verwijder</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Verwijderlijstknop -->
                    <form method="POST" action="dashboard.php" style="display:inline;">
                        <input type="hidden" name="delete_list_id" value="<?php echo $list['id']; ?>">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Weet je zeker dat je deze lijst wilt verwijderen?');">Verwijder</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Je hebt nog geen lijsten.</p>
        <?php endif; ?>

    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/scripts.js"></script>
</body>
</html>