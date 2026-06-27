<?php
session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Tous les champs sont obligatoires";
        header("Location: login.php");
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['error'] = "Email ou mot de passe incorrect";
        header("Location: login.php");
        exit;
    }

    // session
    $_SESSION['id']    = $user['id'];
    $_SESSION['nom']   = $user['nom'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role']  = $user['role'];
    $_SESSION['photo'] = $user['photo'];

    // redirect by role
    switch ($user['role']) {
        case 'admin':
            header("Location: ../admin/dashboard.php");
            break;
        case 'chauffeur':
            header("Location: ../chauffeur/dashboard.php");
            break;
        default:
            header("Location: ../client/dashboard.php");
            break;
    }
    exit;
}