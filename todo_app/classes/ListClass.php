<?php
class ListClass {
    private $conn;
    private $table_name = "lists";

    public $id;
    public $user_id;
    public $name;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET user_id=:user_id, name=:name";
        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));

        $stmt->bindParam(":user_id", $this->user_id, PDO::PARAM_INT);
        $stmt->bindParam(":name", $this->name);

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
        // Verwijder eerst alle taken die bij deze lijst horen
        $taskQuery = "DELETE FROM tasks WHERE list_id = :list_id";
        $taskStmt = $this->conn->prepare($taskQuery);
        $taskStmt->bindParam(":list_id", $list_id, PDO::PARAM_INT);
        $taskStmt->execute();

        // Verwijder vervolgens de lijst zelf
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
}
?>