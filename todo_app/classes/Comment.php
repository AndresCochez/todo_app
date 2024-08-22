<?php
class Comment {
    private $conn; // Databaseverbinding
    private $table_name = "task_comments"; // Naam van de tabel waar opmerkingen worden opgeslagen

    private $id; // ID van de opmerking (indien nodig)
    private $task_id; // ID van de taak waar de opmerking betrekking op heeft
    private $user_id; // ID van de gebruiker die de opmerking heeft geplaatst
    private $comment; // De inhoud van de opmerking

    // Constructor die een databaseverbinding ontvangt en opslaat
    public function __construct($db) {
        $this->conn = $db;
    }

    // Zet de taak-ID en ontsmet deze om XSS-aanvallen te voorkomen
    public function setTaskId($task_id) {
        $this->task_id = htmlspecialchars(strip_tags($task_id));
    }

    // Zet de gebruiker-ID en ontsmet deze om XSS-aanvallen te voorkomen
    public function setUserId($user_id) {
        $this->user_id = htmlspecialchars(strip_tags($user_id));
    }

    // Zet de inhoud van de opmerking, controleert of deze niet leeg is en ontsmet deze
    public function setComment($comment) {
        if (empty($comment)) {
            throw new Exception("Comment cannot be empty."); // Gooit een uitzondering als de opmerking leeg is
        }
        $this->comment = htmlspecialchars(strip_tags($comment));
    }

    // Voegt een nieuwe opmerking toe aan de database
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET task_id=:task_id, user_id=:user_id, comment=:comment";
        $stmt = $this->conn->prepare($query); // Bereidt de SQL-query voor

        // Bind de parameters aan de SQL-query
        $stmt->bindParam(":task_id", $this->task_id, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $this->user_id, PDO::PARAM_INT);
        $stmt->bindParam(":comment", $this->comment);

        // Voer de query uit en controleer of deze succesvol was
        if ($stmt->execute()) {
            return true;
        } else {
            print_r($stmt->errorInfo()); // Print eventuele foutinformatie als de query mislukt
            return false;
        }
    }

    // Haalt alle opmerkingen op die bij een specifieke taak horen, samen met de gebruikersnaam
    public function fetchCommentsByTaskId($task_id) {
        $query = "SELECT c.*, u.username FROM " . $this->table_name . " c
                  INNER JOIN users u ON c.user_id = u.id
                  WHERE c.task_id = :task_id ORDER BY c.created_at DESC";
        $stmt = $this->conn->prepare($query); // Bereidt de SQL-query voor

        $stmt->bindParam(":task_id", $task_id, PDO::PARAM_INT); // Bind de taak-ID aan de SQL-query
        $stmt->execute(); // Voer de query uit

        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Retourneer alle resultaten als een associatieve array
    }

    // Verwijdert alle opmerkingen die bij een specifieke taak horen
    public function deleteByTaskId($task_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE task_id = :task_id";
        $stmt = $this->conn->prepare($query); // Bereidt de SQL-query voor

        $stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT); // Bind de taak-ID aan de SQL-query

        // Voer de query uit en controleer of deze succesvol was
        if ($stmt->execute()) {
            return true;
        } else {
            print_r($stmt->errorInfo()); // Print eventuele foutinformatie als de query mislukt
            return false;
        }
    }
}
?>