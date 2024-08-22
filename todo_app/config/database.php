<?php
class Database {
    private $host = "localhost"; // Database hostnaam
    private $db_name = "todo_app"; // Naam van de database
    private $username = "root"; // Database gebruikersnaam
    private $password = ""; // Database wachtwoord
    public $conn; // Databaseverbinding (publiek toegankelijk)

    // Constructor die automatisch de databaseverbinding opzet
    public function __construct() {
        $this->getConnection();
    }

    // Methode om een databaseverbinding op te zetten
    public function getConnection() {
        $this->conn = null;

        try {
            // Maak verbinding met de database met PDO
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Zet foutmodus op uitzondering
        } catch(PDOException $exception) {
            // Toon foutmelding als verbinding niet kan worden gemaakt
            echo $exception->getMessage();
        }

        return $this->conn; // Retourneer de databaseverbinding
    }

    // Methode om een bestand te uploaden
    public function uploadFile($file) {
        $uploadDir = "uploads/"; // Directory waar bestanden worden opgeslagen

        // Controleer of de upload directory bestaat; maak deze aan als dat niet het geval is
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = basename($file["name"]); // Verkrijg de naam van het bestand
        $targetFilePath = $uploadDir . $fileName; // Doelpad voor het geüploade bestand
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION); // Bestandsextensie

        // Lijst van toegestane bestandstypen
        $allowedTypes = array('jpg', 'png', 'jpeg', 'gif', 'pdf', 'doc', 'docx');

        // Controleer of het bestandstype is toegestaan
        if (in_array($fileType, $allowedTypes)) {
            // Verplaats het bestand naar de doelmap
            if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
                // Voer SQL-query uit om informatie over het bestand in de database in te voegen
                $query = "INSERT INTO files (file_name, file_path, uploaded_on) VALUES (:fileName, :filePath, NOW())";
                $stmt = $this->conn->prepare($query);

                // Bind parameters aan de SQL-query
                $stmt->bindParam(':fileName', $fileName);
                $stmt->bindParam(':filePath', $targetFilePath);

                $stmt->execute(); // Voer de query uit
            }
        }
    }

    // Methode om alle geüploade bestanden op te halen
    public function fetchFiles() {
        $query = "SELECT id, file_name, file_path, task_name FROM files"; // SQL-query om alle bestanden op te halen
        $stmt = $this->conn->prepare($query);
        $stmt->execute(); // Voer de query uit
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Retourneer alle rijen als een associatieve array
    }

    // Methode om een bestand te downloaden op basis van het bestand-ID
    public function downloadFile($fileId) {
        $query = "SELECT file_name, file_path FROM files WHERE id = :id"; // SQL-query om bestandspad en naam op te halen
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $fileId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $file = $stmt->fetch(PDO::FETCH_ASSOC); // Verkrijg bestandspad en naam
            $filePath = $file['file_path'];

            // Controleer of het bestand bestaat voordat je het verzendt
            if (file_exists($filePath)) {
                // Stel HTTP-headers in voor bestandsoverdracht
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filePath));
                readfile($filePath); // Lees het bestand en stuur het naar de gebruiker
                exit;
            }
        }
    }

    // Methode om een bestand te verwijderen op basis van het bestand-ID
    public function deleteFile($fileId) {
        // Verkrijg het bestandspad uit de database
        $query = "SELECT file_path FROM files WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $fileId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            $filePath = $file['file_path'];

            // Verwijder het bestand van de server
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Verwijder de bestandsinformatie uit de database
            $query = "DELETE FROM files WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $fileId);
            $stmt->execute();
        }
    }

    // Methode om taaknamen bij te werken voor alle bestanden
    public function updateTaskNames($task_names) {
        foreach ($task_names as $file_id => $task_name) {
            // Update taaknaam in de database op basis van bestand-ID
            $query = "UPDATE files SET task_name = :task_name WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':task_name', $task_name);
            $stmt->bindParam(':id', $file_id);
            $stmt->execute();
        }
    }
}

// Maak een nieuwe Database-object aan en verkrijg de databaseverbinding
$database = new Database();
$conn = $database->getConnection();

// Verwerk POST-aanvragen voor bestand uploads en taaknaam updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['uploaded_file'])) {
        $database->uploadFile($_FILES['uploaded_file']);
    }
    if (isset($_POST['update_tasks'])) {
        $task_names = $_POST['task_names'] ?? [];
        $database->updateTaskNames($task_names);
    }
}

// Verwerk GET-aanvragen voor bestand downloads en verwijderingen
if (isset($_GET['download_id'])) {
    $database->downloadFile($_GET['download_id']);
}

if (isset($_GET['delete_id'])) {
    $database->deleteFile($_GET['delete_id']);
}

// Haal alle bestanden op
$files = $database->fetchFiles();
?>