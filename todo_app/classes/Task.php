<?php
class Task {
    private $conn;
    private $table_name = "tasks";

    private $id;
    private $list_id;
    private $title;
    private $description;
    private $deadline;
    private $is_done;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Setter for list_id
    public function setListId($list_id) {
        $this->list_id = htmlspecialchars(strip_tags($list_id));
    }

    // Setter for title
    public function setTitle($title) {
        if (empty($title)) {
            throw new Exception("The task title cannot be empty.");
        }
        $this->title = htmlspecialchars(strip_tags($title));
    }

    // Setter for description
    public function setDescription($description) {
        if (empty($description)) {
            throw new Exception("The task description cannot be empty.");
        }
        $this->description = htmlspecialchars(strip_tags($description));
    }

    // Setter for deadline
    public function setDeadline($deadline) {
        $this->deadline = htmlspecialchars(strip_tags($deadline));
    }

    // Setter for is_done
    public function setIsDone($is_done) {
        $this->is_done = $is_done ? 1 : 0;
    }

    // Method to create a task
    public function create() {
        // Check if a task with the same title already exists in the list
        if ($this->checkDuplicate($this->list_id, $this->title)) {
            throw new Exception("A task with this title already exists in the list.");
        }

        $query = "INSERT INTO " . $this->table_name . " SET list_id=:list_id, title=:title, description=:description, deadline=:deadline, is_done=:is_done";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":list_id", $this->list_id, PDO::PARAM_INT);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":deadline", $this->deadline);
        $stmt->bindParam(":is_done", $this->is_done, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        } else {
            print_r($stmt->errorInfo());
            return false;
        }
    }

    // Method to check if a task with the same title already exists in the list
    public function checkDuplicate($list_id, $title) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE list_id = :list_id AND title = :title LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':list_id', $list_id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Method to fetch tasks with sorting
    public function fetchTasksWithSorting($user_id, $sort_type, $sort_order) {
        $query = "SELECT t.*, l.user_id FROM " . $this->table_name . " t
                  INNER JOIN lists l ON t.list_id = l.id
                  WHERE l.user_id = :user_id
                  ORDER BY t.$sort_type $sort_order";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Method to fetch tasks by list ID
    public function fetchTasksByListId($list_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE list_id = :list_id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":list_id", $list_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Method to update task status
    public function updateStatus($task_id, $is_done) {
        $query = "UPDATE " . $this->table_name . " SET is_done = :is_done WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":is_done", $is_done, PDO::PARAM_INT);
        $stmt->bindParam(":id", $task_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        } else {
            print_r($stmt->errorInfo());
            return false;
        }
    }

    // Method to get remaining days
    public function getDaysRemaining($deadline) {
        $deadline_date = new DateTime($deadline);
        $current_date = new DateTime();
        $interval = $current_date->diff($deadline_date);
        return $interval->format('%r%a'); // Returns the number of days, with sign
    }

    // Method to check if task is overdue
    public function isOverdue($deadline) {
        $current_date = new DateTime();
        $deadline_date = new DateTime($deadline);
        return $current_date > $deadline_date;
    }

    // Method to delete a task
    public function delete($task_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $task_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        } else {
            print_r($stmt->errorInfo());
            return false;
        }
    }

    // Method to delete all tasks by list ID
    public function deleteByListId($list_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE list_id = :list_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":list_id", $list_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        } else {
            print_r($stmt->errorInfo());
            return false;
        }
    }
}
?>