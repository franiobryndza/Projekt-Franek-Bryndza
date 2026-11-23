<?php
// Połączenie z MySQL (domyślne ustawienia XAMPP: user root, brak hasła).
// Baza i tabele tworzą się automatycznie przy pierwszym uruchomieniu.
$host = 'localhost'; $user = 'root'; $pass = ''; $dbname = 'football_manager';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Nie można połączyć się z MySQL. Uruchom MySQL w panelu XAMPP.');
}

$pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_polish_ci");
$pdo->exec("USE `$dbname`");
$pdo->exec("CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    position ENUM('Bramkarz','Obrońca','Pomocnik','Napastnik') NOT NULL,
    number TINYINT UNSIGNED NOT NULL UNIQUE
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opponent VARCHAR(100) NOT NULL,
    match_date DATE NOT NULL,
    goals_for TINYINT UNSIGNED NOT NULL,
    goals_against TINYINT UNSIGNED NOT NULL
)");

const TEAM = 'Moja drużyna';
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
