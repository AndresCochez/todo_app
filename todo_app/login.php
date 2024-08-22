<?php
// Start de sessie om sessievariabelen te kunnen gebruiken
session_start();

// Inclusie van de databaseconfiguratie en de User-klasse
require 'config/database.php';
require 'classes/User.php';

// Maak een databaseverbinding
$database = new Database();
$db = $database->getConnection();

// Controleer of het formulier is ingediend via de POST-methode
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Controleer of de 'username' en 'password' keys aanwezig zijn in de POST-data
    if (isset($_POST['username']) && isset($_POST['password'])) {
        // Maak een nieuw User-object aan met de databaseverbinding
        $user = new User($db);
        // Stel de gebruikersnaam en het wachtwoord in voor het User-object
        $user->username = $_POST['username'];
        $user->password = $_POST['password'];

        // Probeer in te loggen met de opgegeven gebruikersnaam en wachtwoord
        if ($user->login()) {
            // Als de login succesvol is, sla de gebruikers-ID op in de sessie
            $_SESSION['user_id'] = $user->id;
            // Stuur de gebruiker door naar de dashboard-pagina
            header("Location: dashboard.php");
            exit(); // Stop verdere uitvoering van de code na de omleiding
        } else {
            // Als de login niet succesvol is, stel een foutmelding in
            $error_message = "Invalid username or password.";
        }
    } else {
        // Als gebruikersnaam of wachtwoord niet zijn opgegeven, stel een foutmelding in
        $error_message = "Username or password not provided.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <!-- Link naar de CSS-stylesheet voor de loginpagina -->
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        <!-- Toon foutmelding als deze is ingesteld -->
        <?php if (isset($error_message)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <!-- Formulier voor gebruikersnaam en wachtwoord -->
        <form method="POST" action="">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <!-- Link naar de registratiepagina voor nieuwe gebruikers -->
        <p>Don't have an account? <a href="register.php">Register</a></p>
    </div>
</body>
</html>