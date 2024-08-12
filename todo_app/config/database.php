<?php
class Database {
    private $host = "localhost";
    private $db_name = "todo_app";
    private $username = "root";
    private $password = "";
    public $conn;

    public function __construct() {
        $this->getConnection();
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }

    // Method to handle file uploads and insert file metadata into the database
    public function uploadFile($file) {
        $uploadDir = "uploads/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = basename($file["name"]);
        $targetFilePath = $uploadDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        $allowedTypes = array('jpg', 'png', 'jpeg', 'gif', 'pdf', 'doc', 'docx');

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
                $query = "INSERT INTO files (file_name, file_path, uploaded_on) VALUES (:fileName, :filePath, NOW())";
                $stmt = $this->conn->prepare($query);

                $stmt->bindParam(':fileName', $fileName);
                $stmt->bindParam(':filePath', $targetFilePath);

                if ($stmt->execute()) {
                    echo "The file " . htmlspecialchars($fileName) . " has been uploaded and saved to the database.";
                } else {
                    echo "Database insertion error.";
                }
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        } else {
            echo "Sorry, only JPG, JPEG, PNG, GIF, PDF, DOC, & DOCX files are allowed.";
        }
    }

    // Method to fetch all files from the database
    public function fetchFiles() {
        $query = "SELECT id, file_name, file_path, task_name FROM files";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Method to download a file
    public function downloadFile($fileId) {
        $query = "SELECT file_name, file_path FROM files WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $fileId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            $filePath = $file['file_path'];

            if (file_exists($filePath)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filePath));
                readfile($filePath);
                exit;
            } else {
                echo "File not found.";
            }
        } else {
            echo "Invalid file ID.";
        }
    }

    // Method to delete a file
    public function deleteFile($fileId) {
        $query = "SELECT file_path FROM files WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $fileId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            $filePath = $file['file_path'];

            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $query = "DELETE FROM files WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $fileId);
            if ($stmt->execute()) {
                echo "File deleted successfully.";
            } else {
                echo "Error deleting file from the database.";
            }
        } else {
            echo "Invalid file ID.";
        }
    }

    // Method to update task names
    public function updateTaskNames($task_names) {
        foreach ($task_names as $file_id => $task_name) {
            $query = "UPDATE files SET task_name = :task_name WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':task_name', $task_name);
            $stmt->bindParam(':id', $file_id);
            $stmt->execute();
        }
    }
}

// Instantiate the Database class
$database = new Database();
$conn = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['uploaded_file'])) {
        $database->uploadFile($_FILES['uploaded_file']);
    }
    if (isset($_POST['update_tasks'])) {
        $task_names = $_POST['task_names'] ?? [];
        $database->updateTaskNames($task_names);
    }
}

// Handle download request
if (isset($_GET['download_id'])) {
    $database->downloadFile($_GET['download_id']);
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $database->deleteFile($_GET['delete_id']);
}

// Fetch all files to display
$files = $database->fetchFiles();
?>