<?php
// Start de sessie om toegang tot sessievariabelen te krijgen
session_start();

// Vereist de benodigde bestanden voor database-verbinding en class-definities
require 'config/database.php';
require 'classes/ListClass.php';
require 'classes/Task.php';
require 'classes/Comment.php';

// Controleer of de gebruiker is ingelogd, anders doorverwijzen naar de inlogpagina
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Maak een verbinding met de database
$database = new Database();
$db = $database->getConnection();

// Initialiseer de benodigde classes
$listClass = new ListClass($db);
$taskClass = new Task($db);
$commentClass = new Comment($db);

// Verwerk POST-verzoeken voor verschillende acties
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Verwerk het maken van een nieuwe lijst
        if (isset($_POST['list_name']) && isset($_POST['list_description'])) {
            $listClass->setUserId($_SESSION['user_id']);
            $listClass->setName($_POST['list_name']);
            $listClass->setDescription($_POST['list_description']);
            if ($listClass->create()) {
                header("Location: dashboard.php");
                exit();
            } else {
                echo "Error creating list.";
            }
        }

        // Verwerk het verwijderen van een lijst
        if (isset($_POST['delete_list_id'])) {
            $list_id = $_POST['delete_list_id'];
            if ($listClass->delete($list_id)) {
                // Verwijder alle taken en opmerkingen die aan deze lijst zijn gekoppeld
                $tasks = $taskClass->fetchTasksByListId($list_id);
                foreach ($tasks as $task) {
                    $commentClass->deleteByTaskId($task['id']);
                }
                $taskClass->deleteByListId($list_id);
                header("Location: dashboard.php");
                exit();
            } else {
                echo "Error deleting list.";
            }
        }

        // Verwerk het maken van een nieuwe taak
        if (isset($_POST['task_title']) && isset($_POST['task_description']) && isset($_POST['task_deadline']) && isset($_POST['list_id'])) {
            $taskClass->setListId($_POST['list_id']);
            $taskClass->setTitle($_POST['task_title']);
            $taskClass->setDescription($_POST['task_description']);
            $taskClass->setDeadline($_POST['task_deadline']);
            if ($taskClass->create()) {
                header("Location: dashboard.php");
                exit();
            } else {
                echo "Error creating task.";
            }
        }

        // Verwerk het verwijderen van een taak
        if (isset($_POST['delete_task_id'])) {
            $task_id = $_POST['delete_task_id'];
            if ($commentClass->deleteByTaskId($task_id)) {
                if ($taskClass->delete($task_id)) {
                    header("Location: dashboard.php");
                    exit();
                } else {
                    echo "Error deleting task.";
                }
            } else {
                echo "Error deleting comments.";
            }
        }

        // Verwerk het bijwerken van de taakstatus
        if (isset($_POST['task_id']) && isset($_POST['is_done'])) {
            $task_id = $_POST['task_id'];
            $is_done = $_POST['is_done'] == 'true' ? 1 : 0;
            if ($taskClass->updateStatus($task_id, $is_done)) {
                echo "Status updated";
            } else {
                echo "Error updating status.";
            }
            exit();
        }

        // Verwerk het toevoegen van opmerkingen
        if (isset($_POST['task_id']) && isset($_POST['comment'])) {
            $commentClass->setTaskId($_POST['task_id']);
            $commentClass->setUserId($_SESSION['user_id']);
            $commentClass->setComment($_POST['comment']);
            if ($commentClass->create()) {
                // Retourneer een succesvolle reactie in JSON-formaat
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error adding comment.']);
            }
            exit();
        }

        // Verwerk AJAX-verzoeken voor het sorteren van taken
        if (isset($_POST['sort_type']) && isset($_POST['sort_order'])) {
            $sort_type = $_POST['sort_type'];
            $sort_order = $_POST['sort_order'];

            // Haal gesorteerde taken op
            $tasks = $taskClass->fetchTasksWithSorting($_SESSION['user_id'], $sort_type, $sort_order);

            // Haal opmerkingen voor de taken op
            $tasks_with_comments = [];
            foreach ($tasks as $task) {
                $comments = $commentClass->fetchCommentsByTaskId($task['id']);
                $tasks_with_comments[$task['id']] = $comments;
            }

            // Retourneer gesorteerde taken met opmerkingen in JSON-formaat
            echo json_encode(['tasks' => $tasks, 'comments' => $tasks_with_comments]);
            exit();
        }
    } catch (Exception $e) {
        // Toon eventuele fouten die zijn opgetreden
        echo $e->getMessage();
    }
}

// Stel standaard sorteerinstellingen in
$sort_type = 'deadline';
$sort_order = 'ASC';

if (isset($_GET['sort_type'])) {
    $sort_type = $_GET['sort_type'];
}

if (isset($_GET['sort_order'])) {
    $sort_order = $_GET['sort_order'];
}

// Haal alle lijsten en gesorteerde taken op
$lists = $listClass->fetchAll($_SESSION['user_id']);
$tasks = $taskClass->fetchTasksWithSorting($_SESSION['user_id'], $sort_type, $sort_order);

// Haal opmerkingen voor alle taken op
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
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        /* Stijl voor de header-container */
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-container h1 {
            margin: 0;
        }

        .header-container form {
            margin: 0;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header met een logout-knop -->
    <div class="header-container">
        <h1>Your Todo Lists</h1>
        <form method="POST" action="login.php">
            <button type="submit" name="logout" class="btn btn-secondary">Logout</button>
        </form>
    </div>

    <!-- Formulier voor het toevoegen van lijsten -->
    <form method="POST" class="form-inline">
        <input type="text" name="list_name" placeholder="List Name" required>
        <textarea name="list_description" placeholder="List Description" required></textarea>
        <button type="submit" class="btn btn-primary">Create List</button>
    </form>

    <!-- Weergave van lijsten -->
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
                    <br>
                    <button type="submit" class="btn btn-primary">Create Task</button>
                </form>

                <!-- Weergave van taken -->
                <?php if (count($tasks) > 0): ?>
                    <table class="table mt-3">
                        <thead>
                            <tr>
                                <th><a href="#" data-sort="title">Title</a></th>
                                <th><a href="#" data-sort="deadline">Deadline</a></th>
                                <th>Description</th>
                                <th>Remaining Days</th>
                                <th>Status</th>
                                <th>Comments</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="task-list">
                            <?php foreach ($tasks as $task): ?>
                                <?php
                                $days_remaining = $taskClass->getDaysRemaining($task['deadline']);
                                $is_overdue = $taskClass->isOverdue($task['deadline']);
                                ?>
                                <tr<?php echo $is_overdue ? ' style="background-color: #f8d7da;"' : ''; ?>>
                                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                                    <td><?php echo htmlspecialchars($task['deadline']); ?></td>
                                    <td><?php echo htmlspecialchars($task['description']); ?></td>
                                    <td><?php echo $is_overdue ? 'Overdue' : $days_remaining . ' days remaining'; ?></td>
                                    <td>
                                        <input type="checkbox" class="update-status" data-task-id="<?php echo $task['id']; ?>" <?php echo $task['is_done'] ? 'checked' : ''; ?>>
                                    </td>
                                    <td>
                                        <!-- Opmerking Formulier -->
                                        <form class="comment-form" method="POST">
                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                            <textarea name="comment" placeholder="Add a comment"></textarea>
                                            <button type="submit" class="btn btn-secondary">Add Comment</button>
                                        </form>
                                        
                                        <!-- Weergave van opmerkingen -->
                                        <?php if (isset($tasks_with_comments[$task['id']])): ?>
                                            <ul>
                                                <?php foreach ($tasks_with_comments[$task['id']] as $comment): ?>
                                                    <li><strong><?php echo htmlspecialchars($comment['username']); ?>:</strong> <?php echo htmlspecialchars($comment['comment']); ?> <small><?php echo htmlspecialchars($comment['created_at']); ?></small></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <!-- Verwijder Taak Knop -->
                                        <form method="POST" action="dashboard.php" style="display:inline;">
                                            <input type="hidden" name="delete_task_id" value="<?php echo $task['id']; ?>">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this task?');">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No tasks available.</p>
                <?php endif; ?>

                <!-- Verwijder Lijst Knop -->
                <form method="POST" action="dashboard.php" style="display:inline;">
                    <input type="hidden" name="delete_list_id" value="<?php echo $list['id']; ?>">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this list?');">Delete List</button>
                </form>

                <?php if (count($tasks) > 0): ?>
                    <!-- Knoppen voor bestand uploaden en tonen -->
                    <button id="show-upload-btn" class="btn btn-primary">Upload File</button>
                    <button id="show-files-btn" class="btn btn-secondary">Uploaded Files</button>

                    <!-- Bestanden uploaden -->
                    <div id="upload-section" style="display: none;">
                        <h3 class="mb-3">Upload File</h3>
                        <form method="POST" action="" enctype="multipart/form-data" class="mb-4">
                            <div class="form-group">
                                <input type="file" name="uploaded_file" class="form-control" required>
                            </div>
                            <br>
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </form>
                    </div>

                    <!-- Geüploade bestanden tonen -->
                    <div id="files-section" style="display: none;">
                        <h3 class="mb-3">Uploaded Files</h3>
                        <form method="POST" action="" class="mb-4">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Task Name</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($files as $file): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($file['file_name']); ?></td>
                                            <td>
                                                <input type="text" name="task_names[<?php echo $file['id']; ?>]" value="<?php echo htmlspecialchars($file['task_name'] ?? ''); ?>" class="form-control">
                                            </td>
                                            <td>
                                                <a href="?download_id=<?php echo $file['id']; ?>" class="btn btn-success btn-sm">Download</a>
                                                <a href="?delete_id=<?php echo $file['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <br>
                            <button type="submit" name="update_tasks" class="btn btn-warning">Update Task Names</button>
                        </form>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const showUploadBtn = document.getElementById('show-upload-btn');
                            const uploadSection = document.getElementById('upload-section');
                            const showFilesBtn = document.getElementById('show-files-btn');
                            const filesSection = document.getElementById('files-section');

                            // Toggle de zichtbaarheid van de upload-sectie
                            showUploadBtn.addEventListener('click', function () {
                                if (uploadSection.style.display === 'none') {
                                    uploadSection.style.display = 'block';
                                    filesSection.style.display = 'none'; // Verberg de bestanden sectie als deze getoond wordt
                                } else {
                                    uploadSection.style.display = 'none';
                                }
                            });

                            // Toggle de zichtbaarheid van de bestanden-sectie
                            showFilesBtn.addEventListener('click', function () {
                                if (filesSection.style.display === 'none') {
                                    filesSection.style.display = 'block';
                                    uploadSection.style.display = 'none'; // Verberg de upload sectie als deze getoond wordt
                                } else {
                                    filesSection.style.display = 'none';
                                }
                            });
                        });
                    </script>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No lists available.</p>
    <?php endif; ?>

    <!-- Weergave van berichten -->
    <?php if (isset($message)) echo "<p>$message</p>"; ?>
</div>

<!-- Invoegen van JavaScript voor AJAX-handling -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/dashboard.js"></script>
</body>
</html>