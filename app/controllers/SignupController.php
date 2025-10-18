<?php
session_start();

// Load DB config and Database core to obtain a PDO connection
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../core/Database.php';
$db = new Database();
$pdo = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../app/views/signup.php');
    exit;
}

$username   = trim($_POST['username'] ?? '');
$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$password   = $_POST['password'] ?? '';
$confirm    = $_POST['confirmPassword'] ?? '';

// Debug logging to verify incoming payload
error_log('Signup POST: ' . json_encode([
    'email' => $email,
    'username' => $username,
    'first_name' => $first_name,
    'last_name' => $last_name,
    'phone' => $phone
]));

// Basic validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['signup_error'] = 'Invalid email';
    header('Location: ../../app/views/signup.php');
    exit;
}
if ($password !== $confirm) {
    $_SESSION['signup_error'] = 'Passwords do not match';
    header('Location: ../../app/views/signup.php');
    exit;
}
if (strlen($password) < 6) {
    $_SESSION['signup_error'] = 'Password too short';
    header('Location: ../../app/views/signup.php');
    exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $sql = "INSERT INTO Users (username, first_name, last_name, email, phone, password_hash, created_at)
            VALUES (:username, :first_name, :last_name, :email, :phone, :pwd, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':username' => $username,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':email' => $email,
        ':phone' => $phone,
        ':pwd' => $password_hash
    ]);

    $newId = $pdo->lastInsertId();
    error_log("Signup saved: id={$newId} email_saved={$email}");

    // Log the user in
    $_SESSION['user_id'] = $newId;

    // If AJAX request, return JSON; otherwise redirect
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'user_id' => $newId]);
        exit;
    } else {
        header('Location: ../../app/views/myFeed.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Signup DB error: ' . $e->getMessage());
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Registration failed']);
        exit;
    }
    $_SESSION['signup_error'] = 'Registration failed';
    header('Location: ../../app/views/signup.php');
    exit;
}

?>
