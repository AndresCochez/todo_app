<?php
session_start();
require 'config/database.php';
require 'classes/Task.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    $task = new Task($db);

    $task_id = $_POST['task_id'];
    $is_done = $_POST['is_done'];

    if ($task->updateStatus($task_id, $is_done)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
?>