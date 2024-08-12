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
        // Directory to upload files
        $uploadDir = "uploads/";

        // Ensure the directory exists and is writable
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = basename($file["name"]);
        $targetFilePath = $uploadDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        // Allowed file types
        $allowedTypes = array('jpg', 'png', 'jpeg', 'gif', 'pdf', 'doc', 'docx');

        if (in_array($fileType, $allowedTypes)) {
            // Move the file to the specified directory
            if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
                // Insert file metadata into the database
                $query = "INSERT INTO files (file_name, file_path, uploaded_on) VALUES (:fileName, :filePath, NOW())";
                $stmt = $this->conn->prepare($query);

                // Bind parameters
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
        $query = "SELECT id, file_name, file_path FROM files";
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

            // Check if the file exists on the server
            if (file_exists($filePath)) {
                // Set headers to initiate file download
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

            // Delete the file from the server
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete the file record from the database
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
}

// Instantiate the Database class
$database = new Database();
$conn = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['uploaded_file'])) {
        // Handle file upload
        $database->uploadFile($_FILES['uploaded_file']);
    }
    if (isset($_POST['update_tasks'])) {
        // Update task names
        $task_names = $_POST['task_names'] ?? [];
        foreach ($task_names as $file_id => $task_name) {
            $query = "UPDATE files SET task_name = :task_name WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':task_name', $task_name);
            $stmt->bindParam(':id', $file_id);
            $stmt->execute();
        }
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
