<?php
session_start(); // Start een nieuwe of bestaande sessie

require 'config/database.php'; // Importeer de database configuratiebestand
require 'classes/Task.php'; // Importeer de Task class die verantwoordelijk is voor taakgerelateerde bewerkingen

// Controleer of de gebruiker is ingelogd door te checken of 'user_id' in de sessie aanwezig is
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Als de gebruiker niet is ingelogd, stuur hem dan naar de login pagina
    exit(); // Stop verdere uitvoering van het script
}

// Controleer of de verzoekmethode POST is, wat betekent dat het formulier is ingediend
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database(); // Maak een nieuwe instantie van de Database klasse
    $db = $database->getConnection(); // Verkrijg een databaseverbinding

    $task = new Task($db); // Maak een nieuwe instantie van de Task klasse met de databaseverbinding

    $task_id = $_POST['task_id']; // Haal de task_id uit de POST data
    $is_done = $_POST['is_done']; // Haal de status is_done uit de POST data

    // Probeer de status van de taak bij te werken
    if ($task->updateStatus($task_id, $is_done)) {
        echo json_encode(['status' => 'success']); // Stuur een JSON-respons met een successtatus als de update is gelukt
    } else {
        echo json_encode(['status' => 'error']); // Stuur een JSON-respons met een foutstatus als de update is mislukt
    }
}
?>