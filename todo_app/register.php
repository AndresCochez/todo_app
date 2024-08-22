<?php
// Vereis de bestanden voor databaseverbinding en gebruikersfunctionaliteit
require 'config/database.php';
require 'classes/User.php';

// Maak een nieuwe databaseverbinding
$database = new Database();
$db = $database->getConnection();

// Controleer of de aanvraagmethode POST is (wat betekent dat het formulier is ingediend)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Maak een nieuw User-object aan met de databaseverbinding
    $user = new User($db);

    // Zet de gebruikersnaam en het wachtwoord van de POST-gegevens in het User-object
    $user->username = $_POST['username'];
    $user->password = $_POST['password'];

    // Probeer de gebruiker te registreren
    if ($user->register()) {
        // Bij succesvolle registratie, stuur de gebruiker door naar de loginpagina
        header("Location: login.php");
        exit(); // Stop verdere uitvoering van de code na de omleiding
    } else {
        // Als registratie mislukt, stel een foutmelding in
        $error_message = "Registration failed.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <!-- Link naar de CSS-bestanden voor registratiepagina styling -->
    <link rel="stylesheet" href="css/register.css">
</head>
<body>
    <div class="container">
        <h1>Register</h1>
        <!-- Toon een foutmelding als deze is ingesteld -->
        <?php if (isset($error_message)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <!-- Registratieformulier -->
        <form method="POST">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>

            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        <!-- Link naar de inlogpagina als de gebruiker al een account heeft -->
        <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
</body>
</html>