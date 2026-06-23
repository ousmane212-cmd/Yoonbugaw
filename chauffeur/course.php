<?php
session_start();
require_once "../config/database.php";

$id = $_GET['id'];
$action = $_GET['action'];

if($action == 'start'){

    $stmt = $pdo->prepare("
        UPDATE reservations
        SET statut_course='en_cours'
        WHERE id=:id
    ");

    $stmt->execute([':id'=>$id]);
}

if($action == 'end'){

    $stmt = $pdo->prepare("
        UPDATE reservations
        SET statut_course='terminee'
        WHERE id=:id
    ");

    $stmt->execute([':id'=>$id]);
}

header("Location: dashboard.php");