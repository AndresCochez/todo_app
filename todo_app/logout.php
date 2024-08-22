<?php
// Start de sessie om toegang te krijgen tot sessievariabelen
session_start();

// Verwijder alle sessievariabelen
session_unset();

// Vernietig de sessie
session_destroy();

// Stuur de gebruiker door naar de loginpagina
header("Location: login.php");

// Stop verdere uitvoering van de code na de omleiding
exit();
?>