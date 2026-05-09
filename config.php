<?php
$db_host = 'localhost';
$db_name = 'u289267434_ecofut';
$db_user = 'u289267434_ecofut';
$db_pass = 'Zonete@13';

$pdo = null;
$db_connected = false;

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db_connected = true;
} catch (PDOException $e) {
    $db_connected = false;
}
