<?php
class ListClass {
    private $conn; // Databaseverbinding
    private $table_name = "lists"; // Naam van de tabel voor lijsten

    private $id; // ID van de lijst (indien nodig)
    private $user_id; // ID van de gebruiker die de lijst bezit
    private $name; // Naam van de lijst
    private $description; // Beschrijving van de lijst

    // Constructor die een databaseverbinding ontvangt en opslaat
    public function __construct($db) {
        $this->conn = $db;
    }

    // Setter voor user_id, ontsmet de input om XSS-aanvallen te voorkomen
    public function setUserId($user_id) {
        $this->user_id = htmlspecialchars(strip_tags($user_id));
    }

    // Getter voor user_id
    public function getUserId() {
        return $this->user_id;
    }

    // Setter voor de naam van de lijst, controleert of deze niet leeg is en ontsmet de input
    public function setName($name) {
        if (empty($name)) {
            throw new Exception("De naam van de lijst mag niet leeg zijn."); // Gooit een uitzondering als de naam leeg is
        }
        $this->name = htmlspecialchars(strip_tags($name));
    }

    // Getter voor de naam van de lijst
    public function getName() {
        return $this->name;
    }

    // Setter voor de beschrijving van de lijst, controleert of deze niet leeg is en ontsmet de input
    public function setDescription($description) {
        if (empty($description)) {
            throw new Exception("De beschrijving van de lijst mag niet leeg zijn."); // Gooit een uitzondering als de beschrijving leeg is
        }
        $this->description = htmlspecialchars(strip_tags($description));
    }

    // Getter voor de beschrijving van de lijst
    public function getDescription() {
        return $this->description;
    }

    // Maakt een nieuwe lijst aan in de database
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET user_id=:user_id, name=:name, description=:description";
        $stmt = $this->conn->prepare($query); // Bereidt de SQL-query voor

        // Bind de parameters aan de SQL-query
        $stmt->bindParam(":user_id", $this->user_id, PDO::PARAM_INT);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);

        // Voer de query uit en controleer of deze succesvol was
        if ($stmt->execute()) {
            return true;
        } else {
            // Foutmelding voor debugging
            print_r($stmt->errorInfo());
            return false;
        }
    }

    // Haalt alle lijsten op die bij een specifieke gebruiker horen
    public function fetchAll($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query); // Bereidt de SQL-query voor

        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT); // Bind de gebruiker-ID aan de SQL-query
        $stmt->execute(); // Voer de query uit

        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Retourneer alle resultaten als een associatieve array
    }

    // Verwijdert een lijst en alle bijbehorende taken
    public function delete($list_id) {
        // Verwijder alle taken die bij deze lijst horen
        $taskQuery = "DELETE FROM tasks WHERE list_id = :list_id";
        $taskStmt = $this->conn->prepare($taskQuery); // Bereidt de SQL-query voor
        $taskStmt->bindParam(":list_id", $list_id, PDO::PARAM_INT); // Bind de lijst-ID aan de SQL-query
        $taskStmt->execute(); // Voer de query uit

        // Verwijder de lijst zelf
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query); // Bereidt de SQL-query voor
        $stmt->bindParam(":id", $list_id, PDO::PARAM_INT); // Bind de lijst-ID aan de SQL-query

        // Voer de query uit en controleer of deze succesvol was
        if ($stmt->execute()) {
            return true;
        } else {
            // Foutmelding voor debugging
            print_r($stmt->errorInfo());
            return false;
        }
    }

    // Verwijdert een specifieke taak uit de lijst door de Task class te gebruiken
    public function deleteTask($task_id) {
        $task = new Task($this->conn); // Maak een nieuwe instantie van de Task klasse met de databaseverbinding
        return $task->delete($task_id); // Verwijder de taak en retourneer het resultaat
    }
}
?>