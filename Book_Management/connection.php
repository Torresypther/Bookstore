<?php
// Database configuration
$host = 'localhost';
$dbname = 'bookstore_management';
$username = 'root';
$password = '';

try {
    // Establish PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}


