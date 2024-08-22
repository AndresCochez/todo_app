<?php
class Task {
    private $conn; // Databaseverbinding
    private $table_name = "tasks"; // Naam van de tabel voor taken

    private $id; // ID van de taak (indien nodig)
    private $list_id; // ID van de lijst waartoe de taak behoort
    private $title; // Titel van de taak
    private $description; // Beschrijving van de taak
    private $deadline; // Deadline van de taak
    private $is_done; // Status van de taak (voltooid of niet)

    // Constructor die een databaseverbinding ontvangt en opslaat
    public function __construct($db) {
        $this->conn = $db;
    }

    // Setter voor list_id, ontsmet de input om XSS-aanvallen te voorkomen
    public function setListId($list_id) {
        $this->list_id = htmlspecialchars(strip_tags($list_id));
    }

    // Setter voor title, controleert of deze niet leeg is en ontsmet de input
    public function setTitle($title) {
        if (empty($title)) {
            throw new Exception("The task title cannot be empty."); // Gooit een uitzondering als de titel leeg is
        }
        $this->title = htmlspecialchars(strip_tags($title));
    }

    // Setter voor description, controleert of deze niet leeg is en ontsmet de input
    public function setDescription($description) {
        if (empty($description)) {
            throw new Exception("The task description cannot be empty."); // Gooit een uitzondering als de beschrijving leeg is
        }
        $this->description = htmlspecialchars(strip_tags($description));
    }

    // Setter voor deadline, ontsmet de input
    public function setDeadline($deadline) {
        $this->deadline = htmlspecialchars(strip_tags($deadline));
    }

    // Setter voor is_done, zet de status naar 1 (voltooid) of 0 (niet voltooid)
    public function setIsDone($is_done) {
        $this->is_done = $is_done ? 1 : 0;
    }

    // Methode om een nieuwe taak aan te maken in de database
    public function create() {
        // Controleer of list_id is ingesteld
        if (empty($this->list_id)) {
            throw new Exception("The list ID must be set before creating a task."); // Gooit een uitzondering als de lijst-ID niet is ingesteld
        }

        // Controleer of een taak met dezelfde titel al bestaat in de specifieke lijst
        if ($this->checkDuplicate($this->list_id, $this->title)) {
            throw new Exception("A task with this title already exists in the list."); // Gooit een uitzondering als een taak met dezelfde titel al bestaat
        }

        // Bereid de SQL-query voor
        $query = "INSERT INTO " . $this->table_name . " SET list_id=:list_id, title=:title, description=:description, deadline=:deadline, is_done=:is_done";
        $stmt = $this->conn->prepare($query);

        // Bind parameters aan de SQL-query
        $stmt->bindParam(":list_id", $this->list_id, PDO::PARAM_INT);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":deadline", $this->deadline);
        $stmt->bindParam(":is_done", $this->is_done, PDO::PARAM_INT);

        // Voer de query uit en controleer of deze succesvol was
        if ($stmt->execute()) {
            return true;
        } else {
            print_r($stmt->errorInfo()); // Foutmelding voor debugging
            return false;
        }
    }

    // Methode om te controleren of een taak met dezelfde titel al bestaat in de lijst
    public function checkDuplicate($list_id, $title) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE list_id = :list_id AND title = :title LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':list_id', $list_id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title);
        $stmt->execute();

        return $stmt->rowCount() > 0; // Retourneert true als er een taak met dezelfde titel bestaat
    }

    // Methode om taken op te halen met sortering
    public function fetchTasksWithSorting($user_id, $sort_type, $sort_order) {
        $query = "SELECT t.*, l.user_id FROM " . $this->table_name . " t
                  INNER JOIN lists l ON t.list_id = l.id
                  WHERE l.user_id = :user_id
                  ORDER BY t.$sort_type $sort_order";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT); // Bind de gebruiker-ID aan de SQL-query
        $stmt->execute(); // Voer de query uit

        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Retourneer alle resultaten als een associatieve array
    }

    // Methode om taken op te halen op basis van lijst-ID
    public function fetchTasksByListId($list_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE list_id = :list_id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":list_id", $list_id, PDO::PARAM_INT); // Bind de lijst-ID aan de SQL-query
        $stmt->execute(); // Voer de query uit

        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Retourneer alle resultaten als een associatieve array
    }

    // Methode om de status van een taak bij te werken
    public function updateStatus($task_id, $is_done) {
        $query = "UPDATE " . $this->table_name . " SET is_done = :is_done WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":is_done", $is_done, PDO::PARAM_INT); // Bind de is_done parameter aan de SQL-query
        $stmt->bindParam(":id", $task_id, PDO::PARAM_INT); // Bind de taak-ID aan de SQL-query

        // Voer de query uit en controleer of deze succesvol was
        if ($stmt->execute()) {
            return true;
        } else {
            print_r($stmt->errorInfo()); // Foutmelding voor debugging
            return false;
        }
    }

    // Methode om het aantal resterende dagen tot de deadline te berekenen
    public function getDaysRemaining($deadline) {
        $deadline_date = new DateTime($deadline); // Maak een DateTime object voor de deadline
        $current_date = new DateTime(); // Maak een DateTime object voor de huidige datum
        $interval = $current_date->diff($deadline_date); // Bereken het verschil tussen de huidige datum en de deadline
        return $interval->format('%r%a'); // Retourneer het aantal dagen, inclusief teken
    }

    // Methode om te controleren of een taak over de deadline heen is
    public function isOverdue($deadline) {
        $current_date = new DateTime(); // Maak een DateTime object voor de huidige datum
        $deadline_date = new DateTime($deadline); // Maak een DateTime object voor de deadline
        return $current_date > $deadline_date; // Retourneer true als de huidige datum na de deadline ligt
    }

    // Methode om een taak uit de database te verwijderen
    public function delete($task_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $task_id, PDO::PARAM_INT); // Bind de taak-ID aan de SQL-query

        // Voer de query uit en controleer of deze succesvol was
        if ($stmt->execute()) {
            return true;
        } else {
            print_r($stmt->errorInfo()); // Foutmelding voor debugging
            return false;
        }
    }

    // Methode om alle taken die bij een specifieke lijst horen te verwijderen
    public function deleteByListId($list_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE list_id = :list_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":list_id", $list_id, PDO::PARAM_INT); // Bind de lijst-ID aan de SQL-query

        // Voer de query uit en controleer of deze succesvol was
        if ($stmt->execute()) {
            return true;
        } else {
            print_r($stmt->errorInfo()); // Foutmelding voor debugging
            return false;
        }
    }
}
?>