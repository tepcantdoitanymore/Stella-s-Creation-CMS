<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$servername = "localhost";
$username   = "u983768004_teptep";
$password   = "YOUR_MYSQL_PASSWORD";
$dbname     = "u983768004_stellasystem";
$password   = "Tiffanytiff0225";

try {
    $dsn = "mysql:host={$servername};dbname={$dbname};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, $username, $password, $options);

} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}
