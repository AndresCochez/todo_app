<?php
class User {
    private $conn; // Databaseverbinding
    private $table_name = "users"; // Naam van de tabel voor gebruikers

    public $id; // ID van de gebruiker (publiek toegankelijk)
    public $username; // Gebruikersnaam (publiek toegankelijk)
    public $password; // Wachtwoord (publiek toegankelijk)

    // Constructor die een databaseverbinding ontvangt en opslaat
    public function __construct($db) {
        $this->conn = $db;
    }

    // Methode om een nieuwe gebruiker te registreren
    public function register() {
        // Controleer of de gebruikersnaam al bestaat in de database
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username LIMIT 0,1";
        $stmt = $this->conn->prepare($query);

        // Ontsmet de gebruikersnaam om XSS-aanvallen te voorkomen
        $this->username = htmlspecialchars(strip_tags($this->username));
        $stmt->bindParam(":username", $this->username);
        $stmt->execute();

        // Als de gebruikersnaam al bestaat, retourneer false
        if ($stmt->rowCount() > 0) {
            // Gebruikersnaam bestaat al
            return false; // Of je kunt een aangepaste foutmelding instellen
        }

        // Als de gebruikersnaam niet bestaat, ga verder met registratie
        $query = "INSERT INTO " . $this->table_name . " SET username=:username, password=:password";
        $stmt = $this->conn->prepare($query);

        // Hash het wachtwoord voor veilige opslag in de database
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);

        // Bind parameters aan de SQL-query
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $this->password);

        // Voer de query uit en controleer of deze succesvol was
        if ($stmt->execute()) {
            return true; // Registratie succesvol
        }
        return false; // Registratie mislukt
    }

    // Methode om een gebruiker in te loggen
    public function login() {
        // Zoek de gebruiker op basis van de gebruikersnaam en haal het wachtwoord op
        $query = "SELECT id, password FROM " . $this->table_name . " WHERE username = :username LIMIT 0,1";
        $stmt = $this->conn->prepare($query);

        // Ontsmet de gebruikersnaam om XSS-aanvallen te voorkomen
        $this->username = htmlspecialchars(strip_tags($this->username));
        $stmt->bindParam(":username", $this->username);

        // Voer de query uit
        $stmt->execute();
        // Als de gebruiker bestaat, controleer het wachtwoord
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            // Vergelijk het ingevoerde wachtwoord met het gehashte wachtwoord in de database
            if (password_verify($this->password, $row['password'])) {
                $this->id = $row['id']; // Zet de gebruikers-ID op basis van de database
                return true; // Inloggen succesvol
            }
        }
        return false; // Inloggen mislukt
    }
}
?>