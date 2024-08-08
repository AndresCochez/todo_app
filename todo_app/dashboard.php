<?php
session_start();
require 'config/database.php';
require 'classes/ListClass.php';
require 'classes/Task.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$listClass = new ListClass($db);
$taskClass = new Task($db);

// Verwerking van lijst creatie
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['list_name'])) {
    $listClass->user_id = $_SESSION['user_id'];
    $listClass->name = $_POST['list_name'];

    if ($listClass->create()) {
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Er is een fout opgetreden bij het aanmaken van de lijst.";
    }
}

// Verwerking van lijst verwijdering
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_list_id'])) {
    $list_id = $_POST['delete_list_id'];
    
    if ($listClass->delete($list_id)) {
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Er is een fout opgetreden bij het verwijderen van de lijst.";
    }
}

// Haal de lijsten en taken op
$lists = $listClass->fetchAll($_SESSION['user_id']);

$sort_type = isset($_GET['type']) ? $_GET['type'] : 'deadline';
$sort_order = isset($_GET['sort']) ? strtoupper($_GET['sort']) : 'ASC';

$tasks = $taskClass->fetchTasksWithSorting($_SESSION['user_id'], $sort_type, $sort_order);
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
        <form method="POST" class="form-inline">
            <input type="text" name="list_name" placeholder="New List Name" required>
            <button type="submit" class="btn btn-primary">Create List</button>
        </form>

        <!-- Lijsten Weergeven -->
        <?php if (count($lists) > 0): ?>
            <ul>
                <?php foreach ($lists as $list): ?>
                    <li>
                        <?php echo htmlspecialchars($list['name']); ?>
                        <form method="POST" action="dashboard.php" style="display:inline;">
                            <input type="hidden" name="delete_list_id" value="<?php echo $list['id']; ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Weet je zeker dat je deze lijst wilt verwijderen?');">Verwijder</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Je hebt nog geen lijsten.</p>
        <?php endif; ?>

        <!-- Taken Weergeven -->
        <table class="table mt-3">
            <thead>
                <tr>
                    <th><a href="?sort=ascending&type=title">Title ↑</a> | <a href="?sort=descending&type=title">Title ↓</a></th>
                    <th><a href="?sort=ascending&type=deadline">Deadline ↑</a> | <a href="?sort=descending&type=deadline">Deadline ↓</a></th>
                    <th>Done</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($task['title']); ?></td>
                        <td><?php echo htmlspecialchars($task['deadline']); ?></td>
                        <td>
                            <input type="checkbox" class="update-status" data-task-id="<?php echo $task['id']; ?>" <?php echo $task['is_done'] ? 'checked' : ''; ?>>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/scripts.js"></script>
</body>
</html>