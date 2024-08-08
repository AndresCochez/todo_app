<?php
class Task {
    private $conn;
    private $table_name = "tasks";

    public $id;
    public $list_id;
    public $title;
    public $description;
    public $deadline;
    public $is_done;
    public $file_path;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET list_id=:list_id, title=:title, description=:description, deadline=:deadline, file_path=:file_path";
        $stmt = $this->conn->prepare($query);

        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->file_path = htmlspecialchars(strip_tags($this->file_path));

        $stmt->bindParam(":list_id", $this->list_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":deadline", $this->deadline);
        $stmt->bindParam(":file_path", $this->file_path);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function fetchTasksWithSorting($user_id, $sort_type = 'deadline', $sort_order = 'ASC') {
        $allowed_sort_types = ['title', 'deadline'];
        $allowed_sort_orders = ['ASC', 'DESC'];

        if (!in_array($sort_type, $allowed_sort_types)) {
            $sort_type = 'deadline';
        }
        if (!in_array($sort_order, $allowed_sort_orders)) {
            $sort_order = 'ASC';
        }

        $query = "SELECT tasks.* FROM " . $this->table_name . "
                  INNER JOIN lists ON tasks.list_id = lists.id
                  WHERE lists.user_id = :user_id
                  ORDER BY $sort_type $sort_order";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($task_id, $is_done) {
        $query = "UPDATE " . $this->table_name . " SET is_done=:is_done WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":is_done", $is_done, PDO::PARAM_INT);
        $stmt->bindParam(":id", $task_id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete($task_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $task_id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
?>