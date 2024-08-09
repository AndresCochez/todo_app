<?php
class Comment {
    private $conn;
    private $table_name = "task_comments";

    private $id;
    private $task_id;
    private $user_id;
    private $comment;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function setTaskId($task_id) {
        $this->task_id = htmlspecialchars(strip_tags($task_id));
    }

    public function setUserId($user_id) {
        $this->user_id = htmlspecialchars(strip_tags($user_id));
    }

    public function setComment($comment) {
        if (empty($comment)) {
            throw new Exception("Commentaar mag niet leeg zijn.");
        }
        $this->comment = htmlspecialchars(strip_tags($comment));
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET task_id=:task_id, user_id=:user_id, comment=:comment";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":task_id", $this->task_id, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $this->user_id, PDO::PARAM_INT);
        $stmt->bindParam(":comment", $this->comment);

        if ($stmt->execute()) {
            return true;
        } else {
            print_r($stmt->errorInfo());
            return false;
        }
    }

    public function fetchCommentsByTaskId($task_id) {
        $query = "SELECT c.*, u.username FROM " . $this->table_name . " c
                  INNER JOIN users u ON c.user_id = u.id
                  WHERE c.task_id = :task_id ORDER BY c.created_at DESC";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":task_id", $task_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>