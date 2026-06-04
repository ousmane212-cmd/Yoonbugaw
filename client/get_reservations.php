<?php
require_once "../config/database.php";
session_start();

header('Content-Type: application/json');

$user = $_SESSION['user_name'] ?? null;

if(!$user){
    echo json_encode([]);
    exit;
}

$sql = "SELECT * FROM reservations 
        WHERE user_name = :user 
        ORDER BY id DESC LIMIT 10";

$stmt = $pdo->prepare($sql);
$stmt->execute(['user' => $user]);

echo json_encode($stmt->fetchAll());