<?php
class ListClass {
    private $conn;
    private $table_name = "lists";

    private $id;
    private $user_id;
    private $name;
    private $description;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Setter voor user_id
    public function setUserId($user_id) {
        $this->user_id = htmlspecialchars(strip_tags($user_id));
    }

    // Getter voor user_id
    public function getUserId() {
        return $this->user_id;
    }

    // Setter voor de naam van de lijst
    public function setName($name) {
        if (empty($name)) {
            throw new Exception("De naam van de lijst mag niet leeg zijn.");
        }
        $this->name = htmlspecialchars(strip_tags($name));
    }

    // Getter voor de naam van de lijst
    public function getName() {
        return $this->name;
    }

    // Setter voor de beschrijving van de lijst
    public function setDescription($description) {
        if (empty($description)) {
            throw new Exception("De beschrijving van de lijst mag niet leeg zijn.");
        }
        $this->description = htmlspecialchars(strip_tags($description));
    }

    // Getter voor de beschrijving van de lijst
    public function getDescription() {
        return $this->description;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET user_id=:user_id, name=:name, description=:description";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $this->user_id, PDO::PARAM_INT);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);

        if ($stmt->execute()) {
            return true;
        } else {
            // Foutmelding voor debugging
            print_r($stmt->errorInfo());
            return false;
        }
    }

    public function fetchAll($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($list_id) {
        // Verwijder alle taken die bij deze lijst horen
        $taskQuery = "DELETE FROM tasks WHERE list_id = :list_id";
        $taskStmt = $this->conn->prepare($taskQuery);
        $taskStmt->bindParam(":list_id", $list_id, PDO::PARAM_INT);
        $taskStmt->execute();

        // Verwijder de lijst zelf
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $list_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        } else {
            // Foutmelding voor debugging
            print_r($stmt->errorInfo());
            return false;
        }
    }

    // Verwijder een specifieke taak uit de lijst
    public function deleteTask($task_id) {
        $task = new Task($this->conn);
        return $task->delete($task_id);
    }
}
?>